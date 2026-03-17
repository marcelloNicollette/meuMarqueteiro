<?php

namespace App\Services\DataIngestion;

use App\Models\DocumentEmbedding;
use App\Models\Municipality;
use App\Models\SystemSetting;
use App\Services\RAG\RAGService;
use Illuminate\Support\Facades\Log;

/**
 * Orquestrador de ingestão de dados públicos.
 *
 * Fluxo:
 * 1. Verifica quais APIs estão ativas no painel
 * 2. Chama cada serviço de ingestão
 * 3. Apaga embeddings antigos das mesmas fontes
 * 4. Indexa chunks novos via RAGService (com retry em 429)
 * 5. Atualiza data_last_synced_at do município
 */
class DataIngestionOrchestrator
{
    public function __construct(
        private RAGService             $rag,
        private IBGEService            $ibge,
        private SICONFIService         $siconfi,
        private FNDEService            $fnde,
        private TransparenciaService   $transparencia,
        private DATASUSService         $datasus,
        private CaptacaoService        $captacao,
        private InfraestruturaService  $infraestrutura,
    ) {}

    public function ingest(Municipality $municipality): array
    {
        $report    = ['municipio' => $municipality->name, 'chunks' => [], 'erros' => []];
        $allChunks = [];

        // Mapa: chave da API → closure que retorna chunks
        // APIs que compartilham serviço usam métodos distintos (sem duplicata)
        $apis = [
            // Socioeconômico
            'ibge_municipios' => fn() => $this->ibge->getMunicipalityData($municipality),
            'ibge_populacao'  => fn() => [], // coberto pelo ibge_municipios
            'atlas_brasil'    => fn() => [], // sem API pública REST
            'ipea_data'       => fn() => [], // sem API pública REST

            // Fiscal
            'siconfi'         => fn() => $this->siconfi->getMunicipalityData($municipality),
            'finbra'          => fn() => [], // coberto pelo siconfi
            'transparencia'   => fn() => $this->transparencia->getMunicipalityData($municipality),

            // Saúde
            'datasus'         => fn() => $this->datasus->getMunicipalityData($municipality),
            'fns'             => fn() => [], // coberto pelo datasus

            // Educação
            'fnde'            => fn() => $this->fnde->getFNDEData($municipality),
            'inep_censo'      => fn() => $this->fnde->getINEPData($municipality),
            'inep_ideb'       => fn() => [], // coberto pelo inep_censo

            // Infraestrutura
            'snis'            => fn() => $this->infraestrutura->getMunicipalityData($municipality),
            'aneel'           => fn() => [], // coberto pelo snis (InfraestruturaService)

            // Captação
            'transferegov'    => fn() => $this->captacao->getMunicipalityData($municipality),
            'bndes'           => fn() => [], // coberto pelo transferegov (CaptacaoService)
        ];

        foreach ($apis as $key => $fetcher) {
            $ativo = SystemSetting::get("integration_{$key}_ativo", false);
            if (!$ativo || $ativo === '0' || $ativo === 0) {
                Log::debug("Ingestão [{$key}] pulada — inativa");
                continue;
            }

            Log::debug("Ingestão [{$key}] iniciando para {$municipality->name}");
            try {
                $chunks = $fetcher();
                Log::debug("Ingestão [{$key}] retornou " . count($chunks) . " chunks");
                foreach ($chunks as $chunk) {
                    $allChunks[] = array_merge($chunk, ['api_key' => $key]);
                }
                $report['chunks'][$key] = count($chunks);
            } catch (\Exception $e) {
                $report['erros'][$key] = $e->getMessage();
                Log::error("Ingestão [{$key}] falhou: " . $e->getMessage());
            }
        }

        if (empty($allChunks)) {
            $report['status'] = 'nenhuma_api_ativa';
            return $report;
        }

        // ── Indexar no pgvector ───────────────────────────────────
        $indexed = 0;
        $fontes  = collect($allChunks)->pluck('source')->unique()->values()->toArray();

        // Remove embeddings antigos das mesmas fontes
        DocumentEmbedding::where('municipality_id', $municipality->id)
            ->whereIn('source', $fontes)
            ->where('layer', 'public_data')
            ->delete();

        foreach ($allChunks as $i => $chunk) {
            try {
                $subChunks = $this->rag->chunkText($chunk['content'], 600, 80);
                Log::debug("Indexando chunk {$i} [{$chunk['source']}]: " . count($subChunks) . " sub-chunks");

                foreach ($subChunks as $j => $subContent) {
                    $this->indexWithRetry(
                        content: $subContent,
                        layer: 'public_data',
                        category: $chunk['category'],
                        source: $chunk['source'],
                        chunkIndex: $j,
                        metadata: array_merge($chunk['metadata'] ?? [], ['parent_chunk' => $i]),
                        municipalityId: $municipality->id,
                    );
                    $indexed++;
                    Log::debug("  sub-chunk {$j} indexado OK (total: {$indexed})");
                    sleep(20); // respeita rate limit Voyage free tier (~3 req/min)
                }
            } catch (\Exception $e) {
                $report['erros']['indexacao_' . $i] = $e->getMessage();
                Log::error("Falha ao indexar chunk {$i}: " . $e->getMessage() . " | Classe: " . get_class($e));
            }
        }

        $municipality->update(['data_last_synced_at' => now()]);

        $report['status']          = 'ok';
        $report['total_indexados'] = $indexed;

        return $report;
    }

    private function indexWithRetry(
        string $content,
        string $layer,
        string $category,
        string $source,
        int    $chunkIndex,
        array  $metadata,
        ?int   $municipalityId
    ): void {
        $maxTries = 3;
        $delay    = 60; // Voyage free tier: janela de 1 minuto

        for ($try = 1; $try <= $maxTries; $try++) {
            try {
                Log::debug("indexWithRetry tentativa {$try}/{$maxTries} para [{$source}]");
                $this->rag->indexChunk(
                    content: $content,
                    layer: $layer,
                    category: $category,
                    source: $source,
                    chunkIndex: $chunkIndex,
                    metadata: $metadata,
                    municipalityId: $municipalityId,
                );
                return;
            } catch (\Exception $e) {
                $is429 = str_contains($e->getMessage(), '429')
                    || str_contains($e->getMessage(), 'rate limit')
                    || str_contains($e->getMessage(), 'Too Many');

                if ($is429 && $try < $maxTries) {
                    Log::debug("Rate limit (tentativa {$try}/{$maxTries}), aguardando {$delay}s...");
                    sleep($delay);
                } else {
                    throw $e;
                }
            }
        }
    }
}
