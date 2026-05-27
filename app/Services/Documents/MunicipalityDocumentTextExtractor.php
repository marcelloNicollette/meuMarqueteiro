<?php

namespace App\Services\Documents;

use App\Models\MunicipalityDocument;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class MunicipalityDocumentTextExtractor
{
    public function extract(MunicipalityDocument $document): string
    {
        $fullPath = Storage::disk($document->disk)->path($document->path);
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeType = strtolower((string) $document->mime_type);

        $text = match (true) {
            in_array($extension, ['txt', 'md', 'csv', 'json']) => Storage::disk($document->disk)->get($document->path),
            in_array($extension, ['html', 'htm']) => strip_tags(Storage::disk($document->disk)->get($document->path)),
            $extension === 'docx' || str_contains($mimeType, 'wordprocessingml') => $this->extractDocx($fullPath),
            $extension === 'doc' || str_contains($mimeType, 'msword') || str_contains($mimeType, 'rtf') => $this->extractViaTextutil($fullPath),
            $extension === 'pdf' || str_contains($mimeType, 'pdf') => $this->extractPdf($fullPath),
            default => $this->extractViaTextutil($fullPath),
        };

        return $this->normalizeText($text);
    }

    private function extractDocx(string $fullPath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($fullPath) !== true) {
            return '';
        }

        $content = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        $content = str_replace(['</w:p>', '</w:tr>', '</w:tc>'], ["\n", "\n", ' '], $content);

        return strip_tags($content);
    }

    private function extractViaTextutil(string $fullPath): string
    {
        if (!$this->commandExists('textutil')) {
            return '';
        }

        return (string) shell_exec('textutil -convert txt -stdout ' . escapeshellarg($fullPath) . ' 2>/dev/null');
    }

    private function extractPdf(string $fullPath): string
    {
        if ($this->commandExists('pdftotext')) {
            $text = (string) shell_exec('pdftotext ' . escapeshellarg($fullPath) . ' - 2>/dev/null');
            if (trim($text) !== '') {
                return $text;
            }
        }

        if ($this->commandExists('mdls')) {
            $text = (string) shell_exec('mdls -raw -name kMDItemTextContent ' . escapeshellarg($fullPath) . ' 2>/dev/null');
            if (trim($text) !== '(null)' && trim($text) !== '') {
                return trim($text, "\" \n\r\t");
            }
        }

        if ($this->commandExists('strings')) {
            return (string) shell_exec('strings ' . escapeshellarg($fullPath) . ' 2>/dev/null');
        }

        return '';
    }

    private function commandExists(string $command): bool
    {
        $result = shell_exec('command -v ' . escapeshellarg($command) . ' 2>/dev/null');

        return !empty($result);
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
