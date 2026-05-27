<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RadarSyncSnapshotMail;
use App\Models\SystemSetting;
use App\Services\Radar\RadarSyncSnapshotService;
use App\Services\Support\RadarOperationalSettingsService;
use App\Services\Support\RuntimeMailConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Spatie\Activitylog\Models\Activity;

class SettingsController extends Controller
{
    public function __construct(
        private readonly RuntimeMailConfigService $runtimeMail,
        private readonly RadarSyncSnapshotService $radarSyncSnapshot,
        private readonly RadarOperationalSettingsService $radarOperationalSettings,
    ) {}

    public function index()
    {
        $defaults = SystemSetting::defaults();

        $ai = [
            'ai_default_provider' => SystemSetting::get('ai_default_provider', $defaults['ai_default_provider']),
            'anthropic_model'     => SystemSetting::get('anthropic_model',     $defaults['anthropic_model']),
            'anthropic_api_key'   => SystemSetting::get('anthropic_api_key',   $defaults['anthropic_api_key']),
            'openai_model'        => SystemSetting::get('openai_model',        $defaults['openai_model']),
            'openai_api_key'      => SystemSetting::get('openai_api_key',      $defaults['openai_api_key']),
            'openai_audio_transcription_model' => SystemSetting::get('openai_audio_transcription_model', $defaults['openai_audio_transcription_model']),
            'openai_audio_speech_model' => SystemSetting::get('openai_audio_speech_model', $defaults['openai_audio_speech_model']),
            'openai_audio_voice'  => SystemSetting::get('openai_audio_voice',  $defaults['openai_audio_voice']),
            'openai_audio_cache_ttl_minutes' => SystemSetting::get('openai_audio_cache_ttl_minutes', $defaults['openai_audio_cache_ttl_minutes']),
            'gemini_model'        => SystemSetting::get('gemini_model',        $defaults['gemini_model']),
            'gemini_api_key'      => SystemSetting::get('gemini_api_key',      $defaults['gemini_api_key']),
            'voyage_api_key'      => SystemSetting::get('voyage_api_key',      $defaults['voyage_api_key']),
        ];

        $mail = [
            'mail_runtime_enabled' => (bool) SystemSetting::get('mail_runtime_enabled', $defaults['mail_runtime_enabled']),
            'mail_runtime_host' => SystemSetting::get('mail_runtime_host', $defaults['mail_runtime_host']),
            'mail_runtime_port' => SystemSetting::get('mail_runtime_port', $defaults['mail_runtime_port']),
            'mail_runtime_username' => SystemSetting::get('mail_runtime_username', $defaults['mail_runtime_username']),
            'mail_runtime_password' => SystemSetting::get('mail_runtime_password', $defaults['mail_runtime_password']),
            'mail_runtime_encryption' => SystemSetting::get('mail_runtime_encryption', $defaults['mail_runtime_encryption']),
            'mail_runtime_from_address' => SystemSetting::get('mail_runtime_from_address', $defaults['mail_runtime_from_address']),
            'mail_runtime_from_name' => SystemSetting::get('mail_runtime_from_name', $defaults['mail_runtime_from_name']),
            'mail_runtime_ehlo_domain' => SystemSetting::get('mail_runtime_ehlo_domain', $defaults['mail_runtime_ehlo_domain']),
            'mail_runtime_timeout' => SystemSetting::get('mail_runtime_timeout', $defaults['mail_runtime_timeout']),
            'mail_runtime_test_recipient' => SystemSetting::get('mail_runtime_test_recipient', $defaults['mail_runtime_test_recipient']),
        ];

        $radarOps = [
            'radar_sync_snapshot_enabled' => (bool) SystemSetting::get('radar_sync_snapshot_enabled', $defaults['radar_sync_snapshot_enabled']),
            'radar_sync_snapshot_daily_enabled' => (bool) SystemSetting::get('radar_sync_snapshot_daily_enabled', $defaults['radar_sync_snapshot_daily_enabled']),
            'radar_sync_snapshot_weekly_enabled' => (bool) SystemSetting::get('radar_sync_snapshot_weekly_enabled', $defaults['radar_sync_snapshot_weekly_enabled']),
            'radar_sync_snapshot_recipients' => implode(', ', SystemSetting::get('radar_sync_snapshot_recipients', $defaults['radar_sync_snapshot_recipients'])),
            'radar_sync_snapshot_daily_time' => SystemSetting::get('radar_sync_snapshot_daily_time', $defaults['radar_sync_snapshot_daily_time']),
            'radar_sync_snapshot_weekly_day' => (int) SystemSetting::get('radar_sync_snapshot_weekly_day', $defaults['radar_sync_snapshot_weekly_day']),
            'radar_sync_snapshot_weekly_time' => SystemSetting::get('radar_sync_snapshot_weekly_time', $defaults['radar_sync_snapshot_weekly_time']),
        ];

        $mailRuntimeStatus = [
            'active_mailer' => $this->runtimeMail->activeMailerName(),
            'runtime_enabled' => $this->runtimeMail->shouldUseRuntimeSmtp(),
        ];
        $radarOperationalHistory = $this->radarOperationalSettings->history();

        return view('admin.settings.index', compact('ai', 'mail', 'radarOps', 'mailRuntimeStatus', 'radarOperationalHistory'));
    }

    public function saveAI(Request $request)
    {
        $request->validate([
            'ai_default_provider' => 'required|in:anthropic,openai,gemini',
            'anthropic_model'     => 'required|string',
            'anthropic_api_key'   => 'nullable|string',
            'openai_model'        => 'required|string',
            'openai_api_key'      => 'nullable|string',
            'openai_audio_transcription_model' => 'required|string',
            'openai_audio_speech_model' => 'required|string',
            'openai_audio_voice'  => 'required|string',
            'openai_audio_cache_ttl_minutes' => 'required|integer|min:5|max:1440',
            'gemini_model'        => 'required|string',
            'gemini_api_key'      => 'nullable|string',
            'voyage_api_key'      => 'nullable|string',
        ]);

        SystemSetting::set('ai_default_provider', $request->ai_default_provider, 'string', 'ai', 'Provider padrão');
        SystemSetting::set('anthropic_model',     $request->anthropic_model,     'string', 'ai', 'Modelo Anthropic');
        SystemSetting::set('openai_model',        $request->openai_model,        'string', 'ai', 'Modelo OpenAI');
        SystemSetting::set('openai_audio_transcription_model', $request->openai_audio_transcription_model, 'string', 'ai', 'Modelo de transcricao OpenAI');
        SystemSetting::set('openai_audio_speech_model', $request->openai_audio_speech_model, 'string', 'ai', 'Modelo de fala OpenAI');
        SystemSetting::set('openai_audio_voice',  $request->openai_audio_voice,  'string', 'ai', 'Voz OpenAI do chat');
        SystemSetting::set('openai_audio_cache_ttl_minutes', (string) $request->integer('openai_audio_cache_ttl_minutes'), 'string', 'ai', 'TTL do cache de audio');
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

    public function saveOperational(Request $request)
    {
        $validated = $request->validate([
            'mail_runtime_enabled' => 'nullable|boolean',
            'mail_runtime_host' => 'required|string|max:255',
            'mail_runtime_port' => 'required|integer|min:1|max:65535',
            'mail_runtime_username' => 'nullable|string|max:255',
            'mail_runtime_password' => 'nullable|string|max:255',
            'mail_runtime_encryption' => 'required|in:none,tls,ssl',
            'mail_runtime_from_address' => 'required|email',
            'mail_runtime_from_name' => 'required|string|max:255',
            'mail_runtime_ehlo_domain' => 'nullable|string|max:255',
            'mail_runtime_timeout' => 'required|integer|min:5|max:120',
            'mail_runtime_test_recipient' => 'nullable|email',
            'radar_sync_snapshot_enabled' => 'nullable|boolean',
            'radar_sync_snapshot_daily_enabled' => 'nullable|boolean',
            'radar_sync_snapshot_weekly_enabled' => 'nullable|boolean',
            'radar_sync_snapshot_recipients' => 'nullable|string',
            'radar_sync_snapshot_daily_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'radar_sync_snapshot_weekly_day' => 'required|integer|min:0|max:6',
            'radar_sync_snapshot_weekly_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
        ]);

        $recipientList = collect(explode(',', (string) ($validated['radar_sync_snapshot_recipients'] ?? '')))
            ->map(fn (string $email) => trim($email))
            ->filter()
            ->values()
            ->all();

        foreach ($recipientList as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return back()->withErrors(["Destinatário inválido no Radar: {$email}"])->withInput();
            }
        }

        $before = $this->radarOperationalSettings->currentSnapshot();
        $after = $this->radarOperationalSettings->normalizePayload([
            ...$validated,
            'mail_runtime_enabled' => $request->boolean('mail_runtime_enabled'),
            'radar_sync_snapshot_enabled' => $request->boolean('radar_sync_snapshot_enabled'),
            'radar_sync_snapshot_daily_enabled' => $request->boolean('radar_sync_snapshot_daily_enabled'),
            'radar_sync_snapshot_weekly_enabled' => $request->boolean('radar_sync_snapshot_weekly_enabled'),
            'radar_sync_snapshot_recipients' => implode(',', $recipientList),
        ], $before);

        $this->radarOperationalSettings->applySnapshot($after);
        $this->radarOperationalSettings->recordUpdate($request->user(), $before, $after);

        return back()->with('success', 'Configurações operacionais do Radar e SMTP salvas com sucesso.');
    }

    public function rollbackOperational(Activity $activity, Request $request)
    {
        if (!$this->radarOperationalSettings->isAuditActivity($activity)) {
            return response()->json([
                'ok' => false,
                'message' => 'Registro de auditoria do Radar não  encontrado.',
            ], 404);
        }

        $rolledBack = $this->radarOperationalSettings->rollback($activity, $request->user());

        if (!$rolledBack) {
            return response()->json([
                'ok' => false,
                'message' => 'Não foi possível restaurar este snapshot operacional.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Configuração operacional do Radar restaurada com sucesso.',
            'rollback_activity_id' => $rolledBack->id,
        ]);
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

    public function testMailRuntime(Request $request)
    {
        $recipient = trim((string) $request->input('recipient', SystemSetting::get('mail_runtime_test_recipient', '')));

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'error' => 'Informe um destinatário de teste válido para o SMTP.',
            ], 422);
        }

        try {
            $this->runtimeMail->sendRaw(
                [$recipient],
                'Teste SMTP do Meu Marqueteiro',
                "SMTP configurado com sucesso.\n\nEnviado em " . now()->format('d/m/Y H:i:s')
            );

            return response()->json([
                'success' => true,
                'mailer' => $this->runtimeMail->activeMailerName(),
                'recipient' => $recipient,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testRadarSnapshot(Request $request)
    {
        $recipient = trim((string) $request->input('recipient', SystemSetting::get('mail_runtime_test_recipient', '')));
        $period = strtolower((string) $request->input('period', 'daily'));

        if (!in_array($period, ['daily', 'weekly'], true)) {
            return response()->json([
                'success' => false,
                'error' => 'Período inválido para teste do snapshot.',
            ], 422);
        }

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'error' => 'Informe um destinatário de teste válido para o snapshot.',
            ], 422);
        }

        try {
            $snapshot = $this->radarSyncSnapshot->buildSnapshot($period);
            $this->runtimeMail->send([$recipient], new RadarSyncSnapshotMail($snapshot));

            return response()->json([
                'success' => true,
                'recipient' => $recipient,
                'period' => $period,
                'summary' => $snapshot['summary'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ─── Integrações Externas ─────────────────────────────────────────────

    private function getIntegracoes(): array
    {
        return [
            'ibge_municípios' => ['grupo' => 'socioeconomico', 'nome' => 'IBGE — Cidades e MUNIC', 'descrição' => 'População, domicílios, renda, escolaridade e estrutura de gestão municipal.', 'url' => 'https://servicodados.ibge.gov.br/api/docs', 'gratuita' => true, 'requer_chave' => false],
            'ibge_populacao'  => ['grupo' => 'socioeconomico', 'nome' => 'IBGE — Estimativas populacionais', 'descrição' => 'Atualização anual de população por município.', 'url' => 'https://servicodados.ibge.gov.br/api/docs/agregados', 'gratuita' => true, 'requer_chave' => false],
            'atlas_brasil'    => ['grupo' => 'socioeconomico', 'nome' => 'Atlas Brasil (PNUD)', 'descrição' => 'IDH municipal, vulnerabilidade social e índices de desenvolvimento por dimensão.', 'url' => 'http://www.atlasbrasil.org.br', 'gratuita' => true, 'requer_chave' => false],
            'ipea_data'       => ['grupo' => 'socioeconomico', 'nome' => 'IPEA Data', 'descrição' => 'Indicadores regionais e séries históricas socioeconômicas.', 'url' => 'http://www.ipeadata.gov.br', 'gratuita' => true, 'requer_chave' => false],
            'siconfi'         => ['grupo' => 'fiscal', 'nome' => 'SICONFI (STN)', 'descrição' => 'Balanços, RREO, RGF, receitas e despesas por função e subfunção.', 'url' => 'https://siconfi.tesouro.gov.br', 'gratuita' => true, 'requer_chave' => false],
            'finbra'          => ['grupo' => 'fiscal', 'nome' => 'FINBRA (STN)', 'descrição' => 'Comparativo fiscal entre municípios — benchmark orçamentário.', 'url' => 'https://www.tesourotransparente.gov.br/ckan/dataset/finbra', 'gratuita' => true, 'requer_chave' => false],
            'transparencia'   => ['grupo' => 'fiscal', 'nome' => 'Portal da Transparência Federal', 'descrição' => 'Transferências federais, convênios, emendas parlamentares e dados de execução pública.', 'url' => 'https://api.portaldatransparencia.gov.br/swagger-ui/index.html', 'gratuita' => true, 'requer_chave' => true],
            'datasus'         => ['grupo' => 'saude', 'nome' => 'DATASUS', 'descrição' => 'Mortalidade, produção ambulatorial e hospitalar, cobertura vacinal.', 'url' => 'https://datasus.saude.gov.br', 'gratuita' => true, 'requer_chave' => false],
            'fns'             => ['grupo' => 'saude', 'nome' => 'FNS — Fundo Nacional de Saúde', 'descrição' => 'Repasses por bloco de financiamento e tetos de MAC.', 'url' => 'https://www.fns.saude.gov.br', 'gratuita' => true, 'requer_chave' => false],
            'fnde'            => ['grupo' => 'educacao', 'nome' => 'FNDE', 'descrição' => 'Repasses de FUNDEB, PNAE, PNATE e obras do PAR.', 'url' => 'https://www.fnde.gov.br', 'gratuita' => true, 'requer_chave' => false],
            'inep_censo'      => ['grupo' => 'educacao', 'nome' => 'INEP — Censo Escolar', 'descrição' => 'Matrículas, docentes e infraestrutura escolar por unidade.', 'url' => 'https://www.gov.br/inep/pt-br/acesso-a-informacao/dados-abertos/microdados', 'gratuita' => true, 'requer_chave' => false],
            'inep_ideb'       => ['grupo' => 'educacao', 'nome' => 'INEP — IDEB', 'descrição' => 'Resultados de aprendizagem por escola e rede.', 'url' => 'https://www.gov.br/inep/pt-br/areas-de-atuacao/pesquisas-estatisticas-e-indicadores/ideb', 'gratuita' => true, 'requer_chave' => false],
            'snis'            => ['grupo' => 'infraestrutura', 'nome' => 'SNIS — Saneamento', 'descrição' => 'Indicadores de saneamento básico: água, esgoto e resíduos sólidos.', 'url' => 'https://www.gov.br/mdr/pt-br/assuntos/saneamento/snis', 'gratuita' => true, 'requer_chave' => false],
            'aneel'           => ['grupo' => 'infraestrutura', 'nome' => 'ANEEL / SIGEL', 'descrição' => 'Energia elétrica, concessões e iluminação pública.', 'url' => 'https://dadosabertos.aneel.gov.br', 'gratuita' => true, 'requer_chave' => false],
            'transferegov'    => ['grupo' => 'captação', 'nome' => 'Portal da Transparência para Captação', 'descrição' => 'Compatibilidade da captação: usa a API do Portal da Transparência para convênios, repasses e emendas.', 'url' => 'https://api.portaldatransparencia.gov.br/swagger-ui/index.html', 'gratuita' => true, 'requer_chave' => true],
            'bndes'           => ['grupo' => 'captação', 'nome' => 'BNDES — Linhas municipais', 'descrição' => 'Crédito para infraestrutura, saneamento e mobilidade.', 'url' => 'https://www.bndes.gov.br/wps/portal/site/home/transparencia/dados-abertos', 'gratuita' => true, 'requer_chave' => false],
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
            'captação'       => 'Captação de Recursos e Programas Federais',
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
                if ($key === 'transferegov') {
                    SystemSetting::set('integration_transparencia_chave', $chaves[$key], 'secret', 'integrations', 'Portal da Transparência Federal — chave');
                }
            } elseif ($key === 'transferegov') {
                $legacyKey = SystemSetting::get('integration_transferegov_chave', '');
                if (!empty($legacyKey) && empty(SystemSetting::get('integration_transparencia_chave', ''))) {
                    SystemSetting::set('integration_transparencia_chave', $legacyKey, 'secret', 'integrations', 'Portal da Transparência Federal — chave');
                }
            }
        }

        return back()->with('success', 'Integrações salvas com sucesso.');
    }
}
