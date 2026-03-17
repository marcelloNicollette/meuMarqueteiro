<?php

namespace App\Services\DataIngestion;

use App\Models\Municipality;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Busca transferências e convênios via Portal da Transparência Federal.
 * Endpoint: https://api.portaldatransparencia.gov.br/api-de-dados
 * Requer chave de API cadastrada em: https://portaldatransparencia.gov.br/api-de-dados/cadastrar
 */
class TransparenciaService
{
    private string $baseUrl = 'https://api.portaldatransparencia.gov.br/api-de-dados';

    public function getMunicipalityData(Municipality $municipality): array
    {
        $apiKey = SystemSetting::get('integration_transparencia_chave', env('TRANSPARENCIA_API_KEY', ''));

        if (empty($apiKey)) {
            return [[
                'content'  => "Portal da Transparência — {$municipality->name}\nChave de API não configurada. Configure em Configurações → APIs Externas.",
                'category' => 'fiscal',
                'source'   => 'Portal da Transparência',
                'metadata' => ['status' => 'sem_chave'],
            ]];
        }

        $chunks   = [];
        $ibgeCode = $municipality->ibge_code;
        $ano      = date('Y');

        // ── Transferências ao município ────────────────────────────
        try {
            $res = Http::timeout(20)
                ->withHeaders(['chave-api-dados' => $apiKey])
                ->get("{$this->baseUrl}/transferencias/municipios", [
                    'codigoIbge' => $ibgeCode,
                    'ano'        => $ano,
                    'pagina'     => 1,
                ]);

            if ($res->ok()) {
                $items = $res->json() ?? [];
                if (!empty($items)) {
                    $total = collect($items)->sum('valor');
                    $lines = [
                        "Transferências Federais para {$municipality->name} — {$ano}:",
                        "Total: R$ " . number_format($total, 2, ',', '.'),
                        "",
                        "Últimas transferências:",
                    ];
                    foreach (array_slice($items, 0, 8) as $t) {
                        $lines[] = "  - {$t['descricaoPrograma']}: R$ " . number_format($t['valor'], 2, ',', '.');
                    }
                    $chunks[] = [
                        'content'  => implode("\n", $lines),
                        'category' => 'fiscal',
                        'source'   => 'Portal da Transparência — Transferências',
                        'metadata' => ['ibge_code'=>$ibgeCode, 'ano'=>$ano],
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning("Transparência transferências falhou: " . $e->getMessage());
        }

        // ── Convênios ativos ───────────────────────────────────────
        try {
            $res = Http::timeout(20)
                ->withHeaders(['chave-api-dados' => $apiKey])
                ->get("{$this->baseUrl}/convenios", [
                    'codigoIbge'    => $ibgeCode,
                    'situacao'      => 'Vigente',
                    'pagina'        => 1,
                ]);

            if ($res->ok()) {
                $items = $res->json() ?? [];
                if (!empty($items)) {
                    $lines = ["Convênios Federais Vigentes — {$municipality->name}:"];
                    foreach (array_slice($items, 0, 10) as $c) {
                        $valor   = 'R$ ' . number_format($c['valorConvenioCedente'] ?? 0, 2, ',', '.');
                        $orgao   = $c['nomeOrgaoSuperior'] ?? '—';
                        $objeto  = substr($c['objeto'] ?? '—', 0, 100);
                        $lines[] = "  - {$orgao}: {$objeto} ({$valor})";
                    }
                    $chunks[] = [
                        'content'  => implode("\n", $lines),
                        'category' => 'captacao',
                        'source'   => 'Portal da Transparência — Convênios',
                        'metadata' => ['ibge_code'=>$ibgeCode, 'tipo'=>'convenios_vigentes'],
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning("Transparência convênios falhou: " . $e->getMessage());
        }

        return $chunks;
    }
}
