<?php

namespace App\Services\FederalPrograms;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para a API do Portal da Transparência (CGU).
 *
 * ENDPOINTS CONFIRMADOS (testados em março/2026):
 *
 * /convenios?codigoIBGE=XXXXXXX
 *   ✅ Filtra por município — retorna convênios onde o município é convenente
 *   ✅ Funciona sem filtro de data quando codigoIBGE está presente
 *   Campos úteis: objeto, situacao, convenente, valorTotal, valorLiberado,
 *                 dataInicioVigencia, dataFinalVigencia, orgaoSuperior
 *
 * /emendas?ano=YYYY
 *   ✅ Retorna emendas do ano, ordenadas por valor
 *   ❌ NÃO filtra por município (parâmetro municipio/codigoIbge/uf ignorado)
 *   → Usamos apenas emendas com localidadeDoGasto = "Nacional"
 *      pois essas são as que qualquer município pode captar
 *
 * /despesas/recursos-recebidos
 *   ❌ Retorna [] para todos os municípios testados — descartado
 */
class TransparenciaClient
{
    private const BASE_URL  = 'https://api.portaldatransparencia.gov.br/api-de-dados';
    private const CACHE_TTL = 3600; // 1 hora

    private function apiKey(): ?string
    {
        return \App\Models\SystemSetting::get('integration_transparencia_chave')
            ?: \App\Models\SystemSetting::get('transparencia_api_key');
    }

    private function headers(): array
    {
        return [
            'chave-api-dados' => $this->apiKey(),
            'Accept'          => 'application/json',
        ];
    }

    public function fetchTransfers(string $ibgeCode): array
    {
        $key = $this->apiKey();
        if (!$key) {
            Log::info('Transparência: chave de API não configurada — pulando.');
            return [];
        }

        return Cache::remember("transparencia:{$ibgeCode}", self::CACHE_TTL, function () use ($ibgeCode) {
            $programs = [];

            // 1. Convênios do município (filtro real por IBGE, sem data obrigatória)
            $this->fetchConvenios($ibgeCode, $programs);

            // 2. Emendas nacionais (sem filtro geográfico — retorna oportunidades abertas a todos)
            $this->fetchEmendasNacionais($ibgeCode, $programs);

            Log::info("Transparência [{$ibgeCode}]: " . count($programs) . " registros coletados.");
            return $programs;
        });
    }

    /**
     * /convenios?codigoIBGE=XXXXXXX
     *
     * Retorna convênios onde o município é convenente (executor).
     * Histórico completo — mostra quais tipos de convênios o município já captou,
     * sinalizando onde tem capacidade técnica e relacionamento.
     */
    private function fetchConvenios(string $ibgeCode, array &$programs): void
    {
        $pagina  = 1;
        $maxPags = 10; // máx 1.000 convênios por município

        do {
            try {
                $response = Http::timeout(20)
                    ->retry(2, 1500)
                    ->withHeaders($this->headers())
                    ->get(self::BASE_URL . '/convenios', [
                        'codigoIBGE' => $ibgeCode,
                        'pagina'     => $pagina,
                        'itens'      => 100,
                    ]);

                if ($response->status() === 401) {
                    Log::warning('Transparência: chave inválida (401).');
                    return;
                }

                if (!$response->successful()) {
                    Log::warning("Transparência /convenios [{$ibgeCode}] p{$pagina}: HTTP " . $response->status());
                    break;
                }

                $items = $response->json();
                if (!is_array($items) || empty($items)) break;

                foreach ($items as $item) {
                    $norm = $this->normalizeConvenio($item);
                    if ($norm) $programs[] = $norm;
                }

                $isLast = count($items) < 100;
                $pagina++;
            } catch (\Exception $e) {
                Log::warning("Transparência /convenios [{$ibgeCode}] p{$pagina}: " . $e->getMessage());
                break;
            }
        } while (!$isLast && $pagina <= $maxPags);
    }

    /**
     * /emendas?ano=YYYY
     *
     * Retorna emendas parlamentares. Como o endpoint NÃO filtra por município,
     * coletamos apenas emendas com localidadeDoGasto = "Nacional",
     * que representam oportunidades abertas a qualquer município do Brasil.
     * Isso é útil para mostrar tendências de investimento federal por área.
     */
    private function fetchEmendasNacionais(string $ibgeCode, array &$programs): void
    {
        $anoAtual = (int) date('Y');

        foreach ([$anoAtual, $anoAtual - 1] as $ano) {
            $pagina  = 1;
            $maxPags = 20; // máx 2.000 emendas por ano

            do {
                try {
                    $response = Http::timeout(20)
                        ->retry(2, 1500)
                        ->withHeaders($this->headers())
                        ->get(self::BASE_URL . '/emendas', [
                            'ano'    => $ano,
                            'pagina' => $pagina,
                            'itens'  => 100,
                        ]);

                    if (!$response->successful()) break;

                    $items = $response->json();
                    if (!is_array($items) || empty($items)) break;

                    foreach ($items as $item) {
                        $localidade = mb_strtoupper($item['localidadeDoGasto'] ?? '');
                        if (!str_contains($localidade, 'NACIONAL')) continue;

                        $norm = $this->normalizeEmenda($item);
                        if ($norm) $programs[] = $norm;
                    }

                    // Emendas são ordenadas por valor crescente.
                    // Quando chegamos a valores altos (>50mi) a maioria não é captável por municípios pequenos.
                    // Paramos na página 20 para evitar excesso de requisições.
                    $isLast = count($items) < 100;
                    $pagina++;
                } catch (\Exception $e) {
                    Log::warning("Transparência /emendas ano {$ano} p{$pagina}: " . $e->getMessage());
                    break;
                }
            } while (!$isLast && $pagina <= $maxPags);
        }
    }

    // ─── Normalizadores ───────────────────────────────────────────────────────

    private function normalizeConvenio(array $item): ?array
    {
        $conv   = $item['dimConvenio'] ?? [];
        $objeto = $conv['objeto'] ?? null;
        if (!$objeto) return null;

        $orgao    = $item['orgaoSuperior']['nome']  ?? $item['orgao']['nome'] ?? null;
        $valor    = $this->parseMoney($item['valorTotal'] ?? $item['valorGlobal'] ?? null);
        $situacao = $item['situacao'] ?? 'DESCONHECIDA';

        // Datas
        $inicio     = $item['dataInicioVigencia'] ?? null;
        $fim        = $item['dataFinalVigencia']  ?? null;
        $ano        = $inicio ? (int) substr($inicio, 0, 4) : (int) date('Y');

        $numero = $conv['numero'] ?? $conv['codigo'] ?? null;

        return [
            'source'          => 'transparencia_convenio',
            'program_name'    => mb_substr(trim($objeto), 0, 255),
            'program_code'    => 'TRP-CVN-' . ($numero ?? substr(md5($objeto . $ano), 0, 8)),
            'ministry'        => $orgao,
            'description'     => "Convênio {$situacao}" . ($orgao ? " — {$orgao}" : '') . ": {$objeto}",
            'max_value'       => $valor,
            'funding_type'    => 'convenio',
            'deadline'        => $fim,
            'source_url'      => 'https://portaldatransparencia.gov.br/convenios/consulta',
            'source_platform' => 'transparencia',
            'status'          => 'historical',
            'area'            => FederalProgramSyncService::inferArea($objeto),
            'reference_year'  => $ano,
            '_raw'            => $item,
        ];
    }

    private function normalizeEmenda(array $item): ?array
    {
        $funcao    = $item['funcao']    ?? '';
        $subfuncao = $item['subfuncao'] ?? '';
        $autor     = $item['nomeAutor'] ?? $item['autor'] ?? 'Parlamentar';
        $tipo      = $item['tipoEmenda'] ?? '';
        $ano       = (int) ($item['ano'] ?? date('Y'));

        $name = trim("{$funcao}" . ($subfuncao ? " — {$subfuncao}" : ''));
        if (!$name) return null;

        // Valor: preferir o maior valor disponível
        $valor = null;
        foreach (['valorEmpenhado', 'valorLiquidado', 'valorPago', 'valorRestoInscrito'] as $k) {
            $v = $this->parseMoney($item[$k] ?? null);
            if ($v && $v > 0) {
                $valor = $v;
                break;
            }
        }

        return [
            'source'          => 'transparencia_emenda',
            'program_name'    => mb_substr("Emenda Nacional: {$funcao}", 0, 255),
            'program_code'    => 'TRP-EMD-' . ($item['codigoEmenda'] ?? substr(md5($name . $autor . $ano), 0, 8)),
            'ministry'        => null,
            'description'     => "{$tipo} de {$autor} — {$funcao}" . ($subfuncao ? " / {$subfuncao}" : '') . " (abrangência nacional)",
            'max_value'       => $valor,
            'funding_type'    => 'emenda',
            'deadline'        => null,
            'source_url'      => 'https://portaldatransparencia.gov.br/emendas/consulta',
            'source_platform' => 'transparencia',
            'status'          => 'historical',
            'area'            => FederalProgramSyncService::inferArea("{$funcao} {$subfuncao}"),
            'reference_year'  => $ano,
            '_raw'            => $item,
        ];
    }

    /**
     * Converte valor monetário brasileiro "1.234.567,89" para float.
     */
    private function parseMoney($value): ?float
    {
        if ($value === null || $value === '' || $value === '0,00') return null;
        $clean = str_replace(['.', ','], ['', '.'], (string) $value);
        $float = (float) $clean;
        return $float > 0 ? $float : null;
    }
}
