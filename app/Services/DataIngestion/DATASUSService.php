<?php

namespace App\Services\DataIngestion;

use App\Models\Municipality;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DATASUS / FNS — Indicadores de saúde municipal.
 *
 * Usa a API do e-Gestor AB (Atenção Básica) e SIOPS (finanças da saúde).
 * Fallback: contexto informativo sobre programas de saúde disponíveis.
 */
class DATASUSService
{
    public function getMunicipalityData(Municipality $municipality): array
    {
        return array_filter([
            $this->getIndicadoresSaude($municipality),
            $this->getContextoSaude($municipality),
        ]);
    }

    private function getIndicadoresSaude(Municipality $municipality): ?array
    {
        $code = $municipality->ibge_code;
        if (!$code) return null;

        try {
            // SIOPS — Sistema de Informações sobre Orçamentos Públicos em Saúde
            $res = Http::timeout(15)->get(
                "https://apidadosabertos.saude.gov.br/siops/despesas/municipio/{$code}"
            );

            if ($res->ok()) {
                $data  = $res->json();
                $items = $data['items'] ?? $data ?? [];
                if (!empty($items)) {
                    $item  = is_array($items[0]) ? $items[0] : $items;
                    $lines = ["SIOPS — Gastos em Saúde — {$municipality->name}:"];
                    foreach (array_slice((array)$item, 0, 8) as $k => $v) {
                        if (is_scalar($v) && $v) $lines[] = "  {$k}: {$v}";
                    }
                    return [
                        'content'  => implode("\n", $lines),
                        'category' => 'saude',
                        'source'   => 'DATASUS — SIOPS',
                        'metadata' => ['ibge_code' => $code, 'tipo' => 'gastos_saude'],
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::debug("DATASUS SIOPS falhou: " . $e->getMessage());
        }
        return null;
    }

    private function getContextoSaude(Municipality $municipality): array
    {
        return [
            'content'  => "Saúde Pública — {$municipality->name} ({$municipality->state_code})\n"
                . "Programas e transferências federais disponíveis:\n"
                . "- PAB Fixo: Piso de Atenção Básica — per capita mensal\n"
                . "- ESF: Equipes de Saúde da Família — financiamento federal por equipe\n"
                . "- NASF: Núcleo de Apoio à Saúde da Família\n"
                . "- Atenção Especializada: recursos para média e alta complexidade\n"
                . "- Vigilância Sanitária e Epidemiológica: transferências regulares\n"
                . "- Farmácia Básica: componente básico da assistência farmacêutica\n"
                . "Indicadores acompanhados pelo Ministério da Saúde:\n"
                . "  Mortalidade infantil, cobertura vacinal, pré-natal, ESF\n"
                . "Consulta: https://datasus.saude.gov.br | https://egestorab.saude.gov.br",
            'category' => 'saude',
            'source'   => 'DATASUS — Contexto de Saúde',
            'metadata' => ['municipio' => $municipality->name, 'tipo' => 'contexto_saude'],
        ];
    }
}
