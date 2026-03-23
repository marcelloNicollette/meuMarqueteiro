<?php

namespace App\Console\Commands;

use App\Models\DocumentEmbedding;
use App\Models\KnowledgeBaseDocument;
use App\Services\AI\AIProviderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class IndexKnowledgeBase extends Command
{
    protected $signature   = 'marqueteiro:index-knowledge-base
                                {--id= : ID específico do documento (omitir = todos pendentes)}
                                {--force : Re-indexar mesmo os já indexados}';

    protected $description = 'Indexa documentos da Base de Conhecimento no RAG (embeddings)';

    private int $chunkSize = 800; // palavras por chunk

    public function __construct(private AIProviderService $ai)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = KnowledgeBaseDocument::where('is_active', true);

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        } elseif (!$this->option('force')) {
            $query->where('indexing_status', 'pending');
        }

        $documents = $query->get();

        if ($documents->isEmpty()) {
            $this->info('Nenhum documento pendente para indexar.');
            return 0;
        }

        $this->info("Indexando {$documents->count()} documento(s)...");
        $bar = $this->output->createProgressBar($documents->count());

        foreach ($documents as $doc) {
            try {
                $this->indexDocument($doc);
                $bar->advance();
            } catch (\Throwable $e) {
                $this->newLine();
                $this->error("Erro ao indexar '{$doc->title}': " . $e->getMessage());
                $doc->update([
                    'indexing_status' => 'failed',
                    'indexing_error'  => $e->getMessage(),
                ]);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Indexação concluída.');

        return 0;
    }

    private function indexDocument(KnowledgeBaseDocument $doc): void
    {
        $doc->update(['indexing_status' => 'processing', 'indexing_error' => null]);

        // Extrair texto do documento
        $text = $this->extractText($doc);

        if (empty(trim($text))) {
            $doc->update(['indexing_status' => 'failed', 'indexing_error' => 'Nenhum texto extraído do documento.']);
            return;
        }

        // Dividir em chunks
        $chunks = $this->splitIntoChunks($text);

        // Remover embeddings antigos deste documento
        DocumentEmbedding::whereNull('municipality_id')
            ->where('layer', 'knowledge_base')
            ->where(function ($q) use ($doc) {
                $q->where('metadata->document_id', $doc->id)
                    ->orWhere('document_id', $doc->id);
            })
            ->delete();

        // Gerar e salvar embeddings
        $count = 0;
        foreach ($chunks as $index => $chunk) {
            if (empty(trim($chunk))) continue;

            $vectorArray = $this->ai->embed($chunk);
            // Converter array para string no formato pgvector: [0.1,0.2,...]
            $vectorStr = '[' . implode(',', $vectorArray) . ']';

            DocumentEmbedding::create([
                'municipality_id' => null, // base de conhecimento geral — compartilhada
                'document_id'     => null,
                'layer'           => 'knowledge_base',
                'category'        => $doc->category,
                'source'          => $doc->title,
                'chunk_index'     => $index,
                'content'         => $chunk,
                'embedding'       => $vectorStr,
                'metadata'        => [
                    'document_id'    => $doc->id,
                    'category'       => $doc->category,
                    'reference_year' => $doc->reference_year,
                    'tags'           => $doc->tags,
                ],
                'token_count'     => str_word_count($chunk),
            ]);

            $count++;
        }

        $doc->update([
            'indexing_status' => 'done',
            'indexed_at'      => now(),
            'chunks_count'    => $count,
            'indexing_error'  => null,
        ]);

        $this->line("  ✓ '{$doc->title}' — {$count} chunks indexados");
    }

    /**
     * Extrai texto do documento (PDF, TXT, DOCX, ou conteúdo inline).
     */
    private function extractText(KnowledgeBaseDocument $doc): string
    {
        if ($doc->content_raw) {
            return $this->sanitizeUtf8($doc->content_raw);
        }

        if (!$doc->path) {
            throw new \Exception('Documento sem arquivo e sem conteúdo inline.');
        }

        $disk    = $doc->disk ?: 'local';
        $content = Storage::disk($disk)->get($doc->path);

        if (!$content) {
            throw new \Exception("Arquivo não encontrado: {$doc->path}");
        }

        $mime = $doc->mime_type ?: '';

        if (str_contains($mime, 'text/plain') || str_ends_with($doc->path, '.txt')) {
            return $content;
        }

        if (str_contains($mime, 'pdf') || str_ends_with($doc->path, '.pdf')) {
            return $this->extractPdfText($content);
        }

        if (str_contains($mime, 'word') || str_ends_with($doc->path, '.docx')) {
            return $this->extractDocxText($content);
        }

        if (
            str_contains($mime, 'spreadsheet')
            || str_ends_with($doc->path, '.xlsx')
            || str_ends_with($doc->path, '.xlsm')
        ) {
            return $this->extractXlsxText($content);
        }

        return strip_tags($content);
    }

    private function extractPdfText(string $content): string
    {
        $byTool = $this->extractPdfTextWithPdftotext($content);
        if ($byTool !== null) {
            $byTool = $this->sanitizeUtf8($byTool);
            if (!$this->looksLikePdfGarbage($byTool) && str_word_count($byTool) >= 30) {
                return $byTool;
            }
        }

        $fromStreams = $this->extractPdfTextFromStreams($content);
        $fromStreams = $this->sanitizeUtf8($fromStreams);
        if (!$this->looksLikePdfGarbage($fromStreams) && str_word_count($fromStreams) >= 30) {
            return $fromStreams;
        }

        $fromOperators = $this->extractPdfTextFromOperators($content);
        $fromOperators = $this->sanitizeUtf8($fromOperators);
        if (!$this->looksLikePdfGarbage($fromOperators) && str_word_count($fromOperators) >= 20) {
            return $fromOperators;
        }

        throw new \Exception('Não foi possível extrair texto legível do PDF. Se for um PDF escaneado (imagem), é necessário OCR.');
    }

    private function extractDocxText(string $content): string
    {
        // Salvar temporariamente
        $tmpFile = tempnam(sys_get_temp_dir(), 'docx_');
        file_put_contents($tmpFile, $content);

        try {
            $zip = new \ZipArchive();
            if ($zip->open($tmpFile) !== true) {
                throw new \Exception('Não foi possível abrir o arquivo DOCX.');
            }

            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            if (!$xml) {
                throw new \Exception('word/document.xml não encontrado no DOCX.');
            }

            // Extrair texto do XML
            $text = strip_tags(str_replace(['</w:p>', '</w:tr>'], "\n", $xml));
            return $this->sanitizeUtf8(preg_replace('/\s+/', ' ', $text));
        } finally {
            unlink($tmpFile);
        }
    }

    private function extractXlsxText(string $content): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        file_put_contents($tmpFile, $content);

        try {
            $zip = new \ZipArchive();
            if ($zip->open($tmpFile) !== true) {
                throw new \Exception('Não foi possível abrir o arquivo XLSX.');
            }

            $parts = [];

            $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedStrings) {
                $parts[] = strip_tags(str_replace(['</t>', '</si>', '</r>'], "\n", $sharedStrings));
            }

            for ($i = 1; $i <= 20; $i++) {
                $sheetXml = $zip->getFromName("xl/worksheets/sheet{$i}.xml");
                if (!$sheetXml) continue;
                $parts[] = strip_tags(str_replace(['</v>', '</t>', '</c>', '</row>'], "\n", $sheetXml));
            }

            $zip->close();

            $text = implode("\n", array_filter($parts));
            $text = preg_replace('/\s+/', ' ', $text);

            return $this->sanitizeUtf8(trim($text));
        } finally {
            unlink($tmpFile);
        }
    }

    private function extractPdfTextFromStreams(string $pdf): string
    {
        if (!str_contains($pdf, '%PDF-')) {
            return '';
        }

        $textParts = [];

        preg_match_all('/<<(.*?)>>\s*stream\s*[\r\n]+(.*?)\s*endstream/s', $pdf, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $dict = $m[1] ?? '';
            $stream = $m[2] ?? '';

            $data = $stream;
            if (stripos($dict, 'FlateDecode') !== false) {
                $inflated = $this->inflatePdfStream($stream);
                if ($inflated !== null) {
                    $data = $inflated;
                }
            }

            $extracted = $this->extractTextOperatorsFromContentStream($data);
            if (!empty($extracted)) {
                $textParts[] = $extracted;
            }
        }

        $text = implode("\n", $textParts);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function extractPdfTextFromOperators(string $pdf): string
    {
        preg_match_all('/BT\s*(.*?)\s*ET/s', $pdf, $blocks);
        $text = '';
        foreach ($blocks[1] ?? [] as $block) {
            $text .= ' ' . $this->extractTextOperatorsFromContentStream($block);
        }
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    private function extractTextOperatorsFromContentStream(string $stream): string
    {
        $out = [];

        preg_match_all('/\((?:\\\\.|[^\\\\\)])*\)\s*Tj/s', $stream, $tj);
        foreach ($tj[0] ?? [] as $match) {
            if (preg_match('/^\((.*)\)\s*Tj/s', $match, $mm)) {
                $out[] = $this->decodePdfLiteralString($mm[1] ?? '');
            }
        }

        preg_match_all('/\[(.*?)\]\s*TJ/s', $stream, $tjs);
        foreach ($tjs[1] ?? [] as $arr) {
            preg_match_all('/\((?:\\\\.|[^\\\\\)])*\)/s', $arr, $strings);
            foreach ($strings[0] ?? [] as $s) {
                $inner = substr($s, 1, -1);
                $out[] = $this->decodePdfLiteralString($inner);
            }
        }

        $text = implode(' ', array_filter($out, fn($t) => trim($t) !== ''));
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function inflatePdfStream(string $data): ?string
    {
        $data = preg_replace("/^\r?\n/", '', $data);
        $data = preg_replace("/\r?\n$/", '', $data);

        $out = @gzuncompress($data);
        if (is_string($out) && $out !== '') return $out;

        $out = @gzinflate($data);
        if (is_string($out) && $out !== '') return $out;

        if (strlen($data) > 2) {
            $out = @gzinflate(substr($data, 2));
            if (is_string($out) && $out !== '') return $out;
        }

        return null;
    }

    private function decodePdfLiteralString(string $s): string
    {
        $s = preg_replace("/\\\\\r?\n/", '', $s);

        $s = str_replace(
            ['\\n', '\\r', '\\t', '\\b', '\\f', '\\(', '\\)', '\\\\'],
            ["\n", "\r", "\t", "\x08", "\x0C", '(', ')', '\\'],
            $s
        );

        $s = preg_replace_callback('/\\\\([0-7]{1,3})/', function ($m) {
            $code = octdec($m[1]);
            return chr($code);
        }, $s);

        if (str_starts_with($s, "\xFE\xFF")) {
            $s = mb_convert_encoding($s, 'UTF-8', 'UTF-16BE');
        } elseif (str_starts_with($s, "\xFF\xFE")) {
            $s = mb_convert_encoding($s, 'UTF-8', 'UTF-16LE');
        }

        return $s;
    }

    private function looksLikePdfGarbage(string $text): bool
    {
        $trim = trim($text);
        if ($trim === '') return true;

        $lower = mb_strtolower($trim);
        if (
            str_contains($lower, 'endstream')
            || str_contains($lower, 'endobj')
            || str_contains($lower, 'flatedecode')
        ) {
            return true;
        }

        if (preg_match('/\b\d+\s+\d+\s+obj\b/u', $lower)) {
            return true;
        }

        $len = mb_strlen($trim);
        if ($len < 40) return true;

        preg_match_all('/[A-Za-zÀ-ÿ]/u', $trim, $letters);
        $letterCount = count($letters[0] ?? []);
        $ratio = $letterCount / max(1, $len);

        return $ratio < 0.12;
    }

    private function extractPdfTextWithPdftotext(string $content): ?string
    {
        $bin = trim((string) @shell_exec('command -v pdftotext'));
        if ($bin === '' && is_executable('/usr/bin/pdftotext')) $bin = '/usr/bin/pdftotext';
        if ($bin === '' && is_executable('/bin/pdftotext')) $bin = '/bin/pdftotext';
        if ($bin === '') return null;

        $tmpPdf = tempnam(sys_get_temp_dir(), 'pdf_');
        file_put_contents($tmpPdf, $content);

        try {
            $cmd = escapeshellcmd($bin)
                . ' -layout -nopgbrk '
                . escapeshellarg($tmpPdf) . ' -';

            $text = (string) @shell_exec($cmd);
            if (trim($text) === '') return null;

            $text = preg_replace('/\s+/', ' ', $text);
            return trim($text);
        } finally {
            @unlink($tmpPdf);
        }
    }

    private function sanitizeUtf8(string $text): string
    {
        // Converter para UTF-8 se necessário
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');
        }

        // Remover caracteres inválidos para JSON/UTF-8
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        // Remover caracteres de controle exceto newline e tab
        $text = preg_replace('/[ --]/u', '', $text);

        // Fallback: remover tudo que não seja ASCII + Latin1 estendido
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = preg_replace('/[^ -~À-ÿ\s]/u', '', $text);
        }

        return trim($text);
    }

    /**
     * Divide texto em chunks de ~800 palavras com overlap de 100 palavras.
     */
    private function splitIntoChunks(string $text): array
    {
        $words    = preg_split('/\s+/', trim($text));
        $total    = count($words);
        $overlap  = 100;
        $chunks   = [];
        $i        = 0;

        while ($i < $total) {
            $slice    = array_slice($words, $i, $this->chunkSize);
            $chunks[] = implode(' ', $slice);
            $i       += ($this->chunkSize - $overlap);
        }

        return array_filter($chunks, fn($c) => str_word_count($c) >= 20);
    }
}
