<?php

namespace App\Services\FederalPrograms;

use App\Models\Municipality;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Usa o Claude para avaliar elegibilidade de programas federais
 * em relação ao perfil de um município.
 *
 * Processa em lotes de 10 para não ultrapassar o context window.
 */
class ClaudeMatchingService
{
    private const BATCH_SIZE = 10;
    private const MODEL      = 'claude-sonnet-4-6';
    private const MAX_TOKENS = 2000;

    public function evaluateBatch(Municipality $municipality, array $programs): array
    {
        if (empty($programs)) return [];

        $profile  = $this->buildMunicipalityProfile($municipality);
        $chunks   = array_chunk($programs, self::BATCH_SIZE);
        $results  = [];

        foreach ($chunks as $i => $chunk) {
            Log::info("Claude matching: lote " . ($i + 1) . "/" . count($chunks) . " para {$municipality->name}");

            try {
                $evaluated = $this->evaluateChunk($profile, $chunk);
                $results   = array_merge($results, $evaluated);

                // Respeitar rate limit (evitar 429)
                if ($i < count($chunks) - 1) sleep(1);
            } catch (\Exception $e) {
                Log::error("Claude matching erro no lote {$i}: " . $e->getMessage());
                // Em caso de erro, retorna os programas com score neutro
                foreach ($chunk as $p) {
                    $results[] = $this->fallback($p, $municipality);
                }
            }
        }

        return $results;
    }

    private function evaluateChunk(string $profile, array $programs): array
    {
        $programsJson = json_encode(
            array_map(fn($p) => [
                'program_code' => $p['program_code'],
                'program_name' => $p['program_name'],
                'ministry'     => $p['ministry'] ?? null,
                'description'  => mb_substr($p['description'] ?? '', 0, 300),
                'area'         => $p['area'] ?? null,
                'max_value'    => $p['max_value'] ?? null,
                'funding_type' => $p['funding_type'] ?? null,
                'deadline'     => $p['deadline'] ?? null,
            ], $programs),
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        $prompt = <<<PROMPT
Você é um especialista em captação de recursos federais para municípios brasileiros.

CONTEXTO IMPORTANTE:
Os registros abaixo são transferências e convênios HISTÓRICOS já recebidos pelo município
(fontes: Transferegov — Transferências Especiais, e Portal da Transparência — Convênios/Emendas).
Eles representam o que o município já recebeu nos últimos anos, servindo como base para
identificar programas recorrentes que o município pode pleitear novamente.

PERFIL DO MUNICÍPIO:
{$profile}

REGISTROS HISTÓRICOS A ANALISAR:
{$programsJson}

Para cada registro, avalie:
1. A probabilidade de este tipo de recurso ser recorrente/disponível novamente
2. O grau de relevância para o perfil atual do município (match_score de 0.00 a 1.00)
3. Uma recomendação objetiva em 1-2 frases (match_reason) indicando se vale monitorar/pleitear novamente
4. Status inferido: "open" (provável que abra chamada), "monitoring" (acompanhar), "low_priority" (baixa prioridade)

Retorne APENAS um array JSON com os seguintes campos por registro:
- program_code (igual ao recebido)
- match_score (float 0.00-1.00, onde 1.00 = altíssima relevância e recorrência esperada)
- match_reason (string, em português, 1-2 frases objetivas com recomendação prática)
- status (string: open | monitoring | low_priority)
- area (string: confirme ou corrija a área temática)

Critérios de match_score:
- 0.90-1.00: programa altamente recorrente, município claramente elegível, vale priorizar
- 0.70-0.89: provável recorrência, município elegível, vale monitorar editais
- 0.50-0.69: recorrência possível, impacto moderado para o município
- 0.30-0.49: recorrência incerta ou baixa compatibilidade atual
- 0.00-0.29: programa pontual ou não relevante para o perfil atual

Retorne SOMENTE o JSON, sem explicações, sem markdown.
PROMPT;

        $response = Http::withHeaders([
            'Content-Type'      => 'application/json',
            'x-api-key'         => env('ANTHROPIC_API_KEY'),
            'anthropic-version' => '2023-06-01',
        ])
            ->timeout(60)
            ->post('https://api.anthropic.com/v1/messages', [
                'model'      => self::MODEL,
                'max_tokens' => self::MAX_TOKENS,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Claude API error: HTTP " . $response->status());
        }

        $content = $response->json('content.0.text', '');
        $content = preg_replace('/^```json\s*/m', '', $content);
        $content = preg_replace('/^```\s*/m', '', $content);
        $content = trim($content);

        $evaluations = json_decode($content, true);
        if (!is_array($evaluations)) {
            Log::error("Claude matching: resposta inválida: " . substr($content, 0, 200));
            throw new \RuntimeException("Resposta inválida do Claude");
        }

        // Mesclar avaliação com os dados originais do programa
        $evalByCode = collect($evaluations)->keyBy('program_code');
        $merged     = [];

        foreach ($programs as $program) {
            $eval  = $evalByCode->get($program['program_code']);
            $merged[] = array_merge(
                $this->cleanForStorage($program),
                [
                    'match_score'  => (float) ($eval['match_score']  ?? 0.50),
                    'match_reason' => $eval['match_reason'] ?? null,
                    'status'       => $eval['status']       ?? $program['status'] ?? 'open',
                    'area'         => $eval['area']         ?? $program['area']   ?? 'outros',
                ]
            );
        }

        return $merged;
    }

    private function buildMunicipalityProfile(Municipality $municipality): string
    {
        $parts = [
            "Nome: {$municipality->name}",
            "Estado: {$municipality->state_code}",
            "Região: " . ($municipality->region ?? 'não informada'),
            "IBGE: {$municipality->ibge_code}",
        ];

        if ($municipality->population) {
            $parts[] = "População: " . number_format($municipality->population, 0, ',', '.') . " hab.";
        }
        if ($municipality->gdp) {
            $parts[] = "PIB total: R$ " . number_format($municipality->gdp / 1000000, 1, ',', '.') . " milhões";
            if ($municipality->population) {
                $percapita = $municipality->gdp / $municipality->population;
                $parts[] = "PIB per capita: R$ " . number_format($percapita, 0, ',', '.');
            }
        }
        if ($municipality->idhm)     $parts[] = "IDHM: {$municipality->idhm}";
        if ($municipality->area_km2) $parts[] = "Área: {$municipality->area_km2} km²";

        // Dados extras do campo settings
        $settings = is_array($municipality->settings) ? $municipality->settings : [];
        if (!empty($settings['economia_principal']))   $parts[] = "Economia: {$settings['economia_principal']}";
        if (!empty($settings['desafios']))             $parts[] = "Desafios: {$settings['desafios']}";
        if (!empty($settings['potenciais']))           $parts[] = "Potenciais: {$settings['potenciais']}";
        if (!empty($settings['saneamento_cobertura'])) $parts[] = "Cobertura de esgoto: {$settings['saneamento_cobertura']}%";
        if (!empty($settings['esf_cobertura']))        $parts[] = "Cobertura ESF: {$settings['esf_cobertura']}%";

        return implode("\n", $parts);
    }

    private function cleanForStorage(array $program): array
    {
        unset($program['_raw'], $program['source']);
        return $program;
    }

    private function fallback(array $program, Municipality $municipality): array
    {
        $clean = $this->cleanForStorage($program);
        return array_merge($clean, [
            'match_score'  => 0.50,
            'match_reason' => 'Análise automática indisponível — verificar elegibilidade manualmente.',
        ]);
    }
}
