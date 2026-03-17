<?php

namespace App\Services\DataIngestion;

use App\Models\Municipality;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SICONFI — Relatórios fiscais do município (STN/Tesouro Nacional).
 *
 * A API usa co_municipio_ibge para identificar o ente.
 * Fallback: gera contexto informativo quando a API não retorna dados.
 */
class SICONFIService
{
    private string $base = 'https://apidatalake.tesouro.gov.br/ords/siconfi/tt';

    public function getMunicipalityData(Municipality $municipality): array
    {
        $code = $municipality->ibge_code;
        if (!$code) return [];

        return array_filter([
            $this->getRGF($municipality, $code),
            $this->getRREO($municipality, $code),
            $this->getContextoFiscal($municipality),
        ]);
    }

    private function getRGF(Municipality $municipality, string $code): ?array
    {
        // Tenta exercícios recentes
        foreach ([date('Y') - 1, date('Y') - 2] as $ano) {
            try {
                $res = Http::timeout(20)->get("{$this->base}/rgf", [
                    'an_exercicio'          => $ano,
                    'in_periodicidade'      => 'Q',
                    'nr_periodo'            => 3,
                    'co_tipo_demonstrativo' => 'RGF',
                    'co_municipio_ibge'     => $code,
                ]);

                if (!$res->ok()) continue;
                $items = $res->json()['items'] ?? [];
                if (empty($items)) continue;

                $lines = ["Relatório de Gestão Fiscal (RGF) — {$municipality->name} — {$ano}:"];
                foreach (array_slice($items, 0, 12) as $item) {
                    $conta = $item['no_conta'] ?? $item['rotulo'] ?? '';
                    $valor = isset($item['vl_conta'])
                        ? 'R$ ' . number_format((float)$item['vl_conta'], 2, ',', '.')
                        : '—';
                    if ($conta) $lines[] = "  {$conta}: {$valor}";
                }

                return [
                    'content'  => implode("\n", $lines),
                    'category' => 'fiscal',
                    'source'   => 'SICONFI — RGF',
                    'metadata' => ['ibge_code' => $code, 'ano' => $ano, 'tipo' => 'rgf'],
                ];
            } catch (\Exception $e) {
                Log::warning("SICONFI RGF {$ano} falhou: " . $e->getMessage());
            }
        }
        return null;
    }

    private function getRREO(Municipality $municipality, string $code): ?array
    {
        foreach ([date('Y') - 1, date('Y') - 2] as $ano) {
            try {
                $res = Http::timeout(20)->get("{$this->base}/rreo", [
                    'an_exercicio'          => $ano,
                    'in_periodicidade'      => 'B',
                    'nr_periodo'            => 6,
                    'co_tipo_demonstrativo' => 'RREO',
                    'co_municipio_ibge'     => $code,
                ]);

                if (!$res->ok()) continue;
                $items = $res->json()['items'] ?? [];
                if (empty($items)) continue;

                $lines = ["RREO — Execução Orçamentária — {$municipality->name} — {$ano}:"];
                foreach (array_slice($items, 0, 12) as $item) {
                    $conta = $item['no_conta'] ?? '';
                    $valor = isset($item['vl_conta'])
                        ? 'R$ ' . number_format((float)$item['vl_conta'], 2, ',', '.')
                        : '—';
                    if ($conta) $lines[] = "  {$conta}: {$valor}";
                }

                return [
                    'content'  => implode("\n", $lines),
                    'category' => 'fiscal',
                    'source'   => 'SICONFI — RREO',
                    'metadata' => ['ibge_code' => $code, 'ano' => $ano, 'tipo' => 'rreo'],
                ];
            } catch (\Exception $e) {
                Log::warning("SICONFI RREO {$ano} falhou: " . $e->getMessage());
            }
        }
        return null;
    }

    private function getContextoFiscal(Municipality $municipality): array
    {
        return [
            'content'  => "Contexto Fiscal — {$municipality->name}\n"
                . "Fontes de receita típicas de municípios baianos:\n"
                . "- FPM (Fundo de Participação dos Municípios): principal transferência federal\n"
                . "- ICMS-cota parte: repasse estadual (25% do ICMS arrecadado no estado)\n"
                . "- FUNDEB: financiamento da educação básica\n"
                . "- ISS: Imposto Sobre Serviços (receita própria)\n"
                . "- IPTU: Imposto Predial e Territorial Urbano (receita própria)\n"
                . "- SUS/FNS: transferências para saúde\n"
                . "Consultar dados específicos: https://siconfi.tesouro.gov.br",
            'category' => 'fiscal',
            'source'   => 'SICONFI — Contexto Fiscal',
            'metadata' => ['municipio' => $municipality->name, 'tipo' => 'contexto'],
        ];
    }
}
