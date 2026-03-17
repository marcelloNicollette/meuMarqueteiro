<?php

namespace App\Services\DataIngestion;

use App\Models\Municipality;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * IBGE — Dados básicos, população, PIB e área territorial.
 *
 * Endpoints usados:
 *  v1/municipios/{code}                                  — dados básicos (instável, fallback local)
 *  v1/pesquisas/indicadores/29171/resultados/{code}      — estimativas populacionais (estável)
 *  v3/agregados/5938/periodos/{ano}/variaveis/37         — PIB municipal
 *  v3/agregados/1301/periodos/{ano}/variaveis/614        — área territorial
 */
class IBGEService
{
    private string $base1 = 'https://servicodados.ibge.gov.br/api/v1';
    private string $base3 = 'https://servicodados.ibge.gov.br/api/v3';

    public function getMunicipalityData(Municipality $municipality): array
    {
        $code = $municipality->ibge_code;
        if (!$code) {
            Log::warning("IBGEService: {$municipality->name} sem ibge_code");
            return [];
        }

        return array_filter([
            $this->getBasicData($municipality),
            $this->getPopulationData($municipality, $code),
            $this->getPIBData($municipality, $code),
        ]);
    }

    private function getBasicData(Municipality $municipality): ?array
    {
        $extra = '';
        try {
            $res = Http::timeout(10)->get("{$this->base1}/municipios/{$municipality->ibge_code}");
            if ($res->ok()) {
                $d     = $res->json();
                $extra = "\nMesorregião: " . ($d['microrregiao']['mesorregiao']['nome'] ?? '—')
                    . "\nMicrorregião: " . ($d['microrregiao']['nome'] ?? '—')
                    . "\nUF: " . ($d['microrregiao']['mesorregiao']['UF']['nome'] ?? $municipality->state);
            }
        } catch (\Exception $e) {
            Log::debug("IBGE /municipios/ indisponível: " . $e->getMessage());
        }

        return [
            'content'  => "Dados do Município: {$municipality->name}\n"
                . "Estado: {$municipality->state} ({$municipality->state_code})\n"
                . "Código IBGE: {$municipality->ibge_code}"
                . $extra,
            'category' => 'socioeconomico',
            'source'   => 'IBGE — Dados Básicos',
            'metadata' => ['ibge_code' => $municipality->ibge_code, 'tipo' => 'dados_basicos'],
        ];
    }

    private function getPopulationData(Municipality $municipality, string $code): ?array
    {
        try {
            $res = Http::timeout(15)
                ->get("{$this->base1}/pesquisas/indicadores/29171/resultados/{$code}");

            if (!$res->ok()) return null;

            $serie = $res->json()[0]['res'][0]['res'] ?? [];
            if (empty($serie)) return null;

            krsort($serie);
            $lines = ["Estimativa Populacional — {$municipality->name} ({$municipality->state_code}):"];
            foreach (array_slice($serie, 0, 6, true) as $ano => $pop) {
                $lines[] = "  {$ano}: " . number_format((int)$pop, 0, ',', '.') . " habitantes";
            }
            // População mais recente em destaque
            reset($serie);
            $anoAtual = key($serie);
            $popAtual = current($serie);
            $lines[]  = "\nPopulação estimada ({$anoAtual}): " . number_format((int)$popAtual, 0, ',', '.') . " habitantes";

            return [
                'content'  => implode("\n", $lines),
                'category' => 'socioeconomico',
                'source'   => 'IBGE — Estimativas Populacionais',
                'metadata' => ['ibge_code' => $code, 'tipo' => 'populacao', 'ano_ref' => $anoAtual],
            ];
        } catch (\Exception $e) {
            Log::warning("IBGE população falhou para {$code}: " . $e->getMessage());
            return null;
        }
    }

    private function getPIBData(Municipality $municipality, string $code): ?array
    {
        // Tenta os últimos 3 anos disponíveis
        foreach ([date('Y') - 2, date('Y') - 3, date('Y') - 4] as $ano) {
            try {
                $res = Http::timeout(15)->get(
                    "{$this->base3}/agregados/5938/periodos/{$ano}/variaveis/37",
                    ['localidades' => "N6[{$code}]"]
                );

                if (!$res->ok()) continue;

                $valor = $res->json()[0]['resultados'][0]['series'][0]['serie'][$ano] ?? null;
                if (!$valor) continue;

                $pibFormatado = 'R$ ' . number_format((float)$valor * 1000, 2, ',', '.');

                return [
                    'content'  => "PIB Municipal — {$municipality->name} ({$ano}):\n"
                        . "  Produto Interno Bruto: {$pibFormatado}\n"
                        . "  Fonte: IBGE — Produto Interno Bruto dos Municípios",
                    'category' => 'socioeconomico',
                    'source'   => 'IBGE — PIB Municipal',
                    'metadata' => ['ibge_code' => $code, 'ano' => $ano, 'tipo' => 'pib'],
                ];
            } catch (\Exception $e) {
                Log::debug("IBGE PIB {$ano} falhou: " . $e->getMessage());
            }
        }
        return null;
    }
}
