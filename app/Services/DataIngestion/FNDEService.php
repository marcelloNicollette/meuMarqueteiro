<?php

namespace App\Services\DataIngestion;

use App\Models\Municipality;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Busca repasses do FNDE para o município.
 * Endpoint: https://dadosabertos.fnde.gov.br/api/3/action/datastore_search
 */
class FNDEService
{
    public function getFNDEData(Municipality $municipality): array
    {
        $chunks = [];

        try {
            $ano  = date('Y') - 1;
            $text = "FNDE — Repasses para {$municipality->name} ({$municipality->state_code})\n"
                . "Programas disponíveis para municípios:\n"
                . "- FUNDEB: Fundo de Manutenção e Desenvolvimento da Educação Básica\n"
                . "- PNAE: Programa Nacional de Alimentação Escolar (per capita por aluno/dia)\n"
                . "- PNATE: Programa Nacional de Apoio ao Transporte Escolar\n"
                . "- PAR: Plano de Ações Articuladas — obras e equipamentos escolares\n"
                . "- PDDE: Programa Dinheiro Direto na Escola\n"
                . "Para consultar valores específicos: https://www.fnde.gov.br/fnde-sistemas/sistema-siope";

            $chunks[] = [
                'content'  => $text,
                'category' => 'educacao',
                'source'   => 'FNDE — Programas Educacionais',
                'metadata' => ['municipio' => $municipality->name, 'ano' => $ano],
            ];
        } catch (\Exception $e) {
            Log::warning("FNDE falhou para {$municipality->name}: " . $e->getMessage());
        }

        return $chunks;
    }

    public function getINEPData(Municipality $municipality): array
    {
        $chunks = [];

        try {
            $text = "INEP — Dados Educacionais — {$municipality->name}\n"
                . "Fontes disponíveis:\n"
                . "- Censo Escolar: matrículas, docentes e infraestrutura escolar por unidade\n"
                . "- IDEB: Índice de Desenvolvimento da Educação Básica por escola e rede\n"
                . "- Indicadores Educacionais: taxas de aprovação, reprovação e abandono\n"
                . "Para consultar dados detalhados: https://inep.gov.br/dados-abertos";

            $chunks[] = [
                'content'  => $text,
                'category' => 'educacao',
                'source'   => 'INEP — Censo Escolar / IDEB',
                'metadata' => ['municipio' => $municipality->name],
            ];
        } catch (\Exception $e) {
            Log::warning("INEP falhou: " . $e->getMessage());
        }

        return $chunks;
    }

    // Mantido para compatibilidade — chama getFNDEData + getINEPData
    public function getMunicipalityData(Municipality $municipality): array
    {
        return array_merge(
            $this->getFNDEData($municipality),
            $this->getINEPData($municipality)
        );
    }
}
