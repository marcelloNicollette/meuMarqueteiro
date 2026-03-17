<?php

namespace App\Services\FederalPrograms;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para a API de Dados Abertos do Transferegov.br
 *
 * Módulo: Transferências Especiais (emendas pix)
 * Docs:   https://docs.api.transferegov.gestao.gov.br/transferenciasespeciais/
 * Auth:   Sem autenticação — API pública de dados abertos
 *
 * O que retorna: transferências especiais já realizadas (emendas parlamentares
 * do tipo "pix") por município executor. Esses dados mostram quais municípios
 * recebem recursos e de quais parlamentares/programas, servindo como base
 * histórica para o Claude inferir oportunidades recorrentes.
 */
class TransferegovClient
{
    private const BASE_URL  = 'https://api.transferegov.gestao.gov.br';
    private const CACHE_TTL = 3600; // 1 hora

    /**
     * Busca transferências especiais recebidas por um município (código IBGE).
     * Retorna os últimos 2 anos para ter histórico suficiente.
     */
    public function fetchByMunicipality(string $ibgeCode): array
    {
        $cacheKey = "transferegov:especiais:{$ibgeCode}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($ibgeCode) {
            return $this->doFetchEspeciais($ibgeCode);
        });
    }

    private function doFetchEspeciais(string $ibgeCode): array
    {
        $programs = [];
        $anoAtual = (int) date('Y');
        $anos     = [$anoAtual, $anoAtual - 1]; // últimos 2 anos

        foreach ($anos as $ano) {
            $fetched  = $this->fetchEspeciaisByAno($ibgeCode, $ano);
            $programs = array_merge($programs, $fetched);
        }

        Log::info("Transferegov Especiais [{$ibgeCode}]: " . count($programs) . " registros coletados.");
        return $programs;
    }

    private function fetchEspeciaisByAno(string $ibgeCode, int $ano): array
    {
        $programs = [];
        $pagina   = 1;
        $limite   = 50;

        do {
            try {
                $response = Http::timeout(30)
                    ->retry(2, 2000)
                    ->get(self::BASE_URL . '/transferencias-especiais/executor_especial', [
                        'codigoIbge'       => $ibgeCode,
                        'anoTransferencia' => $ano,
                        'pagina'           => $pagina,
                        'limite'           => $limite,
                    ]);

                if ($response->status() === 404) {
                    // Município sem registros naquele ano — normal
                    Log::info("Transferegov Especiais [{$ibgeCode}] ano {$ano}: sem dados (404).");
                    break;
                }

                if (!$response->successful()) {
                    Log::warning("Transferegov Especiais [{$ibgeCode}] ano {$ano}: HTTP {$response->status()} pág {$pagina}");
                    break;
                }

                $body  = $response->json();
                $items = $body['data'] ?? $body['content'] ?? (is_array($body) ? $body : []);

                if (empty($items)) break;

                foreach ($items as $item) {
                    $normalized = $this->normalizeEspecial($item, $ano);
                    if ($normalized) $programs[] = $normalized;
                }

                // Paginação: se veio menos que o limite, é última página
                $total  = $body['totalRegistros'] ?? $body['total'] ?? null;
                $isLast = count($items) < $limite
                    || ($total !== null && $pagina * $limite >= $total);
                $pagina++;
            } catch (\Exception $e) {
                Log::error("Transferegov Especiais [{$ibgeCode}] ano {$ano} pág {$pagina}: " . $e->getMessage());
                break;
            }
        } while (!$isLast && $pagina <= 10);

        return $programs;
    }

    private function normalizeEspecial(array $item, int $ano): ?array
    {
        // Campos conforme documentação da API de Transferências Especiais
        $objeto = $item['descricaoObjeto']
            ?? $item['objeto']
            ?? $item['finalidade']
            ?? null;

        $nomePrograma = $item['nomePrograma']
            ?? $item['descricaoAcao']
            ?? $item['descricaoFuncional']
            ?? 'Transferência Especial';

        $name = $objeto
            ? mb_substr(trim($objeto), 0, 200)
            : mb_substr(trim($nomePrograma), 0, 200);

        if (!$name) return null;

        $valor = null;
        foreach (['valorTransferido', 'valorEmpenhado', 'valorPago', 'valor'] as $campo) {
            if (!empty($item[$campo]) && is_numeric($item[$campo])) {
                $valor = (float) $item[$campo];
                break;
            }
        }

        $parlamentar = $item['nomeAutor']
            ?? $item['nomeParlamentar']
            ?? $item['autorEmenda']
            ?? null;

        $ministerio = $item['nomeOrgaoSuperior']
            ?? $item['nomeMinisterio']
            ?? $item['orgaoConcedente']
            ?? null;

        $codigoEmenda = $item['codigoEmenda']
            ?? $item['numeroEmenda']
            ?? $item['identificadorEmenda']
            ?? null;

        $codigo = 'TRG-ESP-' . ($codigoEmenda ?? substr(md5($name . $ano), 0, 8));

        return [
            'source'          => 'transferegov',
            'program_name'    => $name,
            'program_code'    => $codigo,
            'ministry'        => $ministerio ? mb_substr(trim($ministerio), 0, 255) : null,
            'description'     => $objeto ?? $nomePrograma,
            'max_value'       => $valor,
            'funding_type'    => 'emenda',
            'deadline'        => null, // transferências especiais não têm prazo de inscrição
            'source_url'      => 'https://www.transferegov.sistema.gov.br/voluntarias/principal/principal.do',
            'source_platform' => 'transferegov',
            'status'          => 'historical', // dado histórico — base para o Claude inferir recorrência
            'area'            => FederalProgramSyncService::inferArea(
                $name . ' ' . ($ministerio ?? '') . ' ' . ($objeto ?? '')
            ),
            'reference_year'  => $ano,
            'parlamentar'     => $parlamentar,
            '_raw'            => $item,
        ];
    }
}
