<?php

namespace App\Services\DataIngestion;

use App\Models\Municipality;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Captação de recursos federais — Transferegov, BNDES, convênios.
 */
class CaptacaoService
{
    public function getMunicipalityData(Municipality $municipality): array
    {
        $chunks = [];

        // Transferências via Portal da Transparência (se tiver chave)
        $chunks = array_merge($chunks, $this->getTransferencias($municipality));

        // Contexto de captação (sempre disponível)
        $chunks[] = $this->getContextoCaptacao($municipality);

        return array_filter($chunks);
    }

    private function getTransferencias(Municipality $municipality): array
    {
        $apiKey = SystemSetting::get(
            'integration_transparencia_chave',
            SystemSetting::get(
                'transparencia_api_key',
                env('TRANSPARENCIA_API_KEY', '')
            )
        );

        if (empty($apiKey)) return [];

        $chunks = [];
        $code   = $municipality->ibge_code;
        $ano    = date('Y');

        // Transferências recentes
        try {
            $res = Http::timeout(20)
                ->withHeaders(['chave-api-dados' => $apiKey])
                ->get('https://api.portaldatransparencia.gov.br/api-de-dados/transferencias/municipios', [
                    'codigoIbge' => $code,
                    'ano'        => $ano,
                    'pagina'     => 1,
                ]);

            if ($res->ok()) {
                $items = $res->json() ?? [];
                if (!empty($items)) {
                    $total = collect($items)->sum('valor');
                    $lines = [
                        "Transferências Federais — {$municipality->name} — {$ano}:",
                        "Total: R$ " . number_format($total, 2, ',', '.'),
                        "",
                        "Detalhamento:",
                    ];
                    foreach (array_slice($items, 0, 10) as $t) {
                        $prog  = $t['descricaoPrograma'] ?? $t['nomeFavorecido'] ?? '—';
                        $val   = 'R$ ' . number_format($t['valor'] ?? 0, 2, ',', '.');
                        $lines[] = "  - {$prog}: {$val}";
                    }
                    $chunks[] = [
                        'content'  => implode("\n", $lines),
                        'category' => 'captacao',
                        'source'   => 'Portal da Transparência — Transferências',
                        'metadata' => ['ibge_code' => $code, 'ano' => $ano],
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning("Transparência transferências: " . $e->getMessage());
        }

        // Convênios vigentes
        try {
            $res = Http::timeout(20)
                ->withHeaders(['chave-api-dados' => $apiKey])
                ->get('https://api.portaldatransparencia.gov.br/api-de-dados/convenios', [
                    'codigoIbge' => $code,
                    'situacao'   => 'Vigente',
                    'pagina'     => 1,
                ]);

            if ($res->ok()) {
                $items = $res->json() ?? [];
                if (!empty($items)) {
                    $lines = ["Convênios Federais Vigentes — {$municipality->name}:"];
                    foreach (array_slice($items, 0, 10) as $c) {
                        $orgao  = $c['nomeOrgaoSuperior'] ?? '—';
                        $objeto = substr($c['objeto'] ?? '—', 0, 80);
                        $valor  = 'R$ ' . number_format($c['valorConvenioCedente'] ?? 0, 2, ',', '.');
                        $lines[] = "  - {$orgao}: {$objeto} ({$valor})";
                    }
                    $chunks[] = [
                        'content'  => implode("\n", $lines),
                        'category' => 'captacao',
                        'source'   => 'Portal da Transparência — Convênios',
                        'metadata' => ['ibge_code' => $code, 'tipo' => 'convenios'],
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning("Transparência convênios: " . $e->getMessage());
        }

        return $chunks;
    }

    private function getContextoCaptacao(Municipality $municipality): array
    {
        $tier = $municipality->subscription_tier ?? 'essencial';

        return [
            'content'  => "Captação de Recursos Federais — {$municipality->name}\n"
                . "Principais fontes de captação disponíveis para municípios:\n\n"
                . "TRANSFEREGOV (antigo Siconv):\n"
                . "  - Proposta de plano de trabalho para convênios com União\n"
                . "  - Programas prioritários: infraestrutura, saúde, educação, esporte\n"
                . "  - Emendas parlamentares individuais e de bancada\n\n"
                . "BNDES:\n"
                . "  - BNDES Finem: projetos de infraestrutura acima de R$ 10 milhões\n"
                . "  - BNDES Automático: via agentes financeiros, até R$ 150 milhões\n"
                . "  - Programa de Modernização da Administração Tributária (PMAT)\n"
                . "  - Programa Cidades Sustentáveis\n\n"
                . "FNDE:\n"
                . "  - PAR (Plano de Ações Articuladas): obras e equipamentos escolares\n"
                . "  - PROINFÂNCIA: construção de creches\n\n"
                . "Acesso: https://transferegov.sistema.gov.br | https://www.bndes.gov.br",
            'category' => 'captacao',
            'source'   => 'Captação — Guia de Fontes',
            'metadata' => ['municipio' => $municipality->name, 'tier' => $tier],
        ];
    }
}
