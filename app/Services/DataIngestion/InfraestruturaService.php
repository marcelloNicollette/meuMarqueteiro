<?php

namespace App\Services\DataIngestion;

use App\Models\Municipality;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SNIS / ANEEL — Saneamento e energia elétrica.
 */
class InfraestruturaService
{
    public function getMunicipalityData(Municipality $municipality): array
    {
        return array_filter([
            $this->getSNIS($municipality),
            $this->getContextoInfraestrutura($municipality),
        ]);
    }

    private function getSNIS(Municipality $municipality): ?array
    {
        $code = $municipality->ibge_code;
        if (!$code) return null;

        try {
            // SNIS API aberta
            $res = Http::timeout(15)->get(
                "http://app4.mdr.gov.br/api/data/waterAndSewageData/{$code}"
            );

            if ($res->ok()) {
                $data  = $res->json();
                $lines = ["SNIS — Saneamento Básico — {$municipality->name}:"];
                $map   = [
                    'IN023' => 'Cobertura de abastecimento de água (%)',
                    'IN015' => 'Cobertura de coleta de esgoto (%)',
                    'IN046' => 'Cobertura de tratamento de esgoto (%)',
                    'IN053' => 'Atendimento total de água (%)',
                ];
                foreach ($map as $key => $label) {
                    if (isset($data[$key])) {
                        $lines[] = "  {$label}: {$data[$key]}";
                    }
                }
                if (count($lines) > 1) {
                    return [
                        'content'  => implode("\n", $lines),
                        'category' => 'infraestrutura',
                        'source'   => 'SNIS — Saneamento',
                        'metadata' => ['ibge_code' => $code, 'tipo' => 'saneamento'],
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::debug("SNIS falhou: " . $e->getMessage());
        }
        return null;
    }

    private function getContextoInfraestrutura(Municipality $municipality): array
    {
        return [
            'content'  => "Infraestrutura — {$municipality->name} ({$municipality->state_code})\n\n"
                . "Saneamento Básico (SNIS):\n"
                . "  - Municípios baianos têm índices variados de cobertura de água e esgoto\n"
                . "  - Programas federais: PAC Saneamento, Novo PAC, FUNASA\n"
                . "  - Consulta: http://app.mdr.gov.br/serieHistorica\n\n"
                . "Energia Elétrica (ANEEL):\n"
                . "  - Programa Luz para Todos: atendimento a comunidades rurais\n"
                . "  - Agência reguladora: fiscalização da distribuidora local (COELBA na Bahia)\n"
                . "  - Consulta de distribuidoras: https://sigel.aneel.gov.br\n\n"
                . "Telecomunicações:\n"
                . "  - Programa Conecta Brasil: expansão de conectividade\n"
                . "  - PGMU: Plano Geral de Metas de Universalização",
            'category' => 'infraestrutura',
            'source'   => 'Infraestrutura — Contexto',
            'metadata' => ['municipio' => $municipality->name, 'tipo' => 'contexto_infra'],
        ];
    }
}
