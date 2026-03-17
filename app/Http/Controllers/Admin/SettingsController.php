<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SettingsController extends Controller
{
    public function index()
    {
        $defaults = SystemSetting::defaults();

        $ai = [
            'ai_default_provider' => SystemSetting::get('ai_default_provider', $defaults['ai_default_provider']),
            'anthropic_model'     => SystemSetting::get('anthropic_model',     $defaults['anthropic_model']),
            'anthropic_api_key'   => SystemSetting::get('anthropic_api_key',   $defaults['anthropic_api_key']),
            'openai_model'        => SystemSetting::get('openai_model',        $defaults['openai_model']),
            'openai_api_key'      => SystemSetting::get('openai_api_key',      $defaults['openai_api_key']),
            'gemini_model'        => SystemSetting::get('gemini_model',        $defaults['gemini_model']),
            'gemini_api_key'      => SystemSetting::get('gemini_api_key',      $defaults['gemini_api_key']),
            'voyage_api_key'      => SystemSetting::get('voyage_api_key',      $defaults['voyage_api_key']),
        ];

        return view('admin.settings.index', compact('ai'));
    }

    public function saveAI(Request $request)
    {
        $request->validate([
            'ai_default_provider' => 'required|in:anthropic,openai,gemini',
            'anthropic_model'     => 'required|string',
            'anthropic_api_key'   => 'nullable|string',
            'openai_model'        => 'required|string',
            'openai_api_key'      => 'nullable|string',
            'gemini_model'        => 'required|string',
            'gemini_api_key'      => 'nullable|string',
            'voyage_api_key'      => 'nullable|string',
        ]);

        SystemSetting::set('ai_default_provider', $request->ai_default_provider, 'string', 'ai', 'Provider padrão');
        SystemSetting::set('anthropic_model',     $request->anthropic_model,     'string', 'ai', 'Modelo Anthropic');
        SystemSetting::set('openai_model',        $request->openai_model,        'string', 'ai', 'Modelo OpenAI');
        SystemSetting::set('gemini_model',        $request->gemini_model,        'string', 'ai', 'Modelo Gemini');

        if ($request->filled('anthropic_api_key')) {
            SystemSetting::set('anthropic_api_key', $request->anthropic_api_key, 'secret', 'ai', 'Chave Anthropic');
        }
        if ($request->filled('openai_api_key')) {
            SystemSetting::set('openai_api_key', $request->openai_api_key, 'secret', 'ai', 'Chave OpenAI');
        }
        if ($request->filled('gemini_api_key')) {
            SystemSetting::set('gemini_api_key', $request->gemini_api_key, 'secret', 'ai', 'Chave Gemini');
        }
        if ($request->filled('voyage_api_key')) {
            SystemSetting::set('voyage_api_key', $request->voyage_api_key, 'secret', 'ai', 'Chave Voyage AI');
        }

        Artisan::call('config:clear');

        return back()->with('success', 'Configurações de IA salvas com sucesso.');
    }

    public function testConnection(Request $request)
    {
        $provider = $request->provider ?? SystemSetting::get('ai_default_provider', 'anthropic');

        try {
            $service  = app(\App\Services\AI\AIProviderService::class)->withProvider($provider);
            $response = $service->chat([
                ['role' => 'user', 'content' => 'Responda apenas: ok'],
            ], ['max_tokens' => 10]);

            return response()->json([
                'success'  => true,
                'provider' => $provider,
                'model'    => $response->model,
                'response' => $response->content,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ─── Integrações Externas ─────────────────────────────────────────────

    private function getIntegracoes(): array
    {
        return [
            'ibge_municipios' => ['grupo' => 'socioeconomico', 'nome' => 'IBGE — Cidades e MUNIC', 'descricao' => 'População, domicílios, renda, escolaridade e estrutura de gestão municipal.', 'url' => 'https://servicodados.ibge.gov.br/api/docs', 'gratuita' => true, 'requer_chave' => false],
            'ibge_populacao'  => ['grupo' => 'socioeconomico', 'nome' => 'IBGE — Estimativas populacionais', 'descricao' => 'Atualização anual de população por município.', 'url' => 'https://servicodados.ibge.gov.br/api/docs/agregados', 'gratuita' => true, 'requer_chave' => false],
            'atlas_brasil'    => ['grupo' => 'socioeconomico', 'nome' => 'Atlas Brasil (PNUD)', 'descricao' => 'IDH municipal, vulnerabilidade social e índices de desenvolvimento por dimensão.', 'url' => 'http://www.atlasbrasil.org.br', 'gratuita' => true, 'requer_chave' => false],
            'ipea_data'       => ['grupo' => 'socioeconomico', 'nome' => 'IPEA Data', 'descricao' => 'Indicadores regionais e séries históricas socioeconômicas.', 'url' => 'http://www.ipeadata.gov.br', 'gratuita' => true, 'requer_chave' => false],
            'siconfi'         => ['grupo' => 'fiscal', 'nome' => 'SICONFI (STN)', 'descricao' => 'Balanços, RREO, RGF, receitas e despesas por função e subfunção.', 'url' => 'https://siconfi.tesouro.gov.br', 'gratuita' => true, 'requer_chave' => false],
            'finbra'          => ['grupo' => 'fiscal', 'nome' => 'FINBRA (STN)', 'descricao' => 'Comparativo fiscal entre municípios — benchmark orçamentário.', 'url' => 'https://www.tesourotransparente.gov.br/ckan/dataset/finbra', 'gratuita' => true, 'requer_chave' => false],
            'transparencia'   => ['grupo' => 'fiscal', 'nome' => 'Portal da Transparência Federal', 'descricao' => 'Transferências voluntárias, convênios e emendas parlamentares.', 'url' => 'https://api.portaldatransparencia.gov.br/swagger-ui.html', 'gratuita' => true, 'requer_chave' => true],
            'datasus'         => ['grupo' => 'saude', 'nome' => 'DATASUS', 'descricao' => 'Mortalidade, produção ambulatorial e hospitalar, cobertura vacinal.', 'url' => 'https://datasus.saude.gov.br', 'gratuita' => true, 'requer_chave' => false],
            'fns'             => ['grupo' => 'saude', 'nome' => 'FNS — Fundo Nacional de Saúde', 'descricao' => 'Repasses por bloco de financiamento e tetos de MAC.', 'url' => 'https://www.fns.saude.gov.br', 'gratuita' => true, 'requer_chave' => false],
            'fnde'            => ['grupo' => 'educacao', 'nome' => 'FNDE', 'descricao' => 'Repasses de FUNDEB, PNAE, PNATE e obras do PAR.', 'url' => 'https://www.fnde.gov.br', 'gratuita' => true, 'requer_chave' => false],
            'inep_censo'      => ['grupo' => 'educacao', 'nome' => 'INEP — Censo Escolar', 'descricao' => 'Matrículas, docentes e infraestrutura escolar por unidade.', 'url' => 'https://www.gov.br/inep/pt-br/acesso-a-informacao/dados-abertos/microdados', 'gratuita' => true, 'requer_chave' => false],
            'inep_ideb'       => ['grupo' => 'educacao', 'nome' => 'INEP — IDEB', 'descricao' => 'Resultados de aprendizagem por escola e rede.', 'url' => 'https://www.gov.br/inep/pt-br/areas-de-atuacao/pesquisas-estatisticas-e-indicadores/ideb', 'gratuita' => true, 'requer_chave' => false],
            'snis'            => ['grupo' => 'infraestrutura', 'nome' => 'SNIS — Saneamento', 'descricao' => 'Indicadores de saneamento básico: água, esgoto e resíduos sólidos.', 'url' => 'https://www.gov.br/mdr/pt-br/assuntos/saneamento/snis', 'gratuita' => true, 'requer_chave' => false],
            'aneel'           => ['grupo' => 'infraestrutura', 'nome' => 'ANEEL / SIGEL', 'descricao' => 'Energia elétrica, concessões e iluminação pública.', 'url' => 'https://dadosabertos.aneel.gov.br', 'gratuita' => true, 'requer_chave' => false],
            'transferegov'    => ['grupo' => 'captacao', 'nome' => 'Transferegov (antigo +Brasil)', 'descricao' => 'Convênios ativos, propostas e emendas parlamentares.', 'url' => 'https://api.transferegov.sistema.gov.br/api-docs', 'gratuita' => true, 'requer_chave' => true],
            'bndes'           => ['grupo' => 'captacao', 'nome' => 'BNDES — Linhas municipais', 'descricao' => 'Crédito para infraestrutura, saneamento e mobilidade.', 'url' => 'https://www.bndes.gov.br/wps/portal/site/home/transparencia/dados-abertos', 'gratuita' => true, 'requer_chave' => false],
        ];
    }

    public function integrations()
    {
        $todasApis = $this->getIntegracoes();

        $integrations = [];
        foreach ($todasApis as $key => $api) {
            $api['ativo'] = (bool) SystemSetting::get("integration_{$key}_ativo", false);
            $api['chave'] = SystemSetting::get("integration_{$key}_chave", '');
            $integrations[$key] = $api;
        }

        $grupos = [];
        foreach ($integrations as $key => $api) {
            $grupos[$api['grupo']][$key] = $api;
        }

        $grupoLabels = [
            'socioeconomico' => 'Dados Socioeconômicos e Demográficos',
            'fiscal'         => 'Dados Fiscais e Orçamentários',
            'saude'          => 'Saúde',
            'educacao'       => 'Educação',
            'infraestrutura' => 'Infraestrutura, Saneamento e Meio Ambiente',
            'captacao'       => 'Captação de Recursos e Programas Federais',
        ];

        return view('admin.settings.integrations', compact('integrations', 'grupos', 'grupoLabels'));
    }

    public function saveIntegrations(Request $request)
    {
        $ativos = $request->input('ativos', []);
        $chaves = $request->input('chaves', []);

        foreach ($this->getIntegracoes() as $key => $api) {
            SystemSetting::set("integration_{$key}_ativo", in_array($key, $ativos) ? '1' : '0', 'boolean', 'integrations', $api['nome']);
            if (!empty($chaves[$key])) {
                SystemSetting::set("integration_{$key}_chave", $chaves[$key], 'secret', 'integrations', $api['nome'] . ' — chave');
            }
        }

        return back()->with('success', 'Integrações salvas com sucesso.');
    }
}
