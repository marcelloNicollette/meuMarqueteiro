<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentEmbedding;
use App\Models\Municipality;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiagnosticController extends Controller
{
    public function index()
    {
        $checks = [];

        // ── 1. Provider de IA ────────────────────────────────────────────
        $provider = SystemSetting::get('ai_default_provider', env('AI_DEFAULT_PROVIDER', 'anthropic'));
        $model    = SystemSetting::get("{$provider}_model", env('ANTHROPIC_MODEL', '—'));
        $apiKey   = SystemSetting::get("{$provider}_api_key", env(strtoupper($provider).'_API_KEY', ''));

        $checks['ia'] = [
            'label'  => 'Provider de IA',
            'status' => !empty($apiKey) ? 'ok' : 'error',
            'detalhe'=> "Provider: {$provider} | Modelo: {$model}",
            'msg'    => !empty($apiKey) ? 'Chave configurada' : 'Chave de API não configurada em Configurações → IA',
        ];

        // ── 2. Banco de dados ────────────────────────────────────────────
        try {
            DB::select('SELECT 1');
            $checks['db'] = ['label'=>'Banco de dados','status'=>'ok','detalhe'=>'PostgreSQL','msg'=>'Conexão OK'];
        } catch (\Exception $e) {
            $checks['db'] = ['label'=>'Banco de dados','status'=>'error','detalhe'=>'','msg'=>$e->getMessage()];
        }

        // ── 3. pgvector ──────────────────────────────────────────────────
        try {
            DB::select("SELECT '[1,2,3]'::vector");
            $checks['pgvector'] = ['label'=>'Extensão pgvector','status'=>'ok','detalhe'=>'RAG habilitado','msg'=>'Extensão instalada e funcional'];
        } catch (\Exception $e) {
            $checks['pgvector'] = ['label'=>'Extensão pgvector','status'=>'warning','detalhe'=>'RAG desabilitado','msg'=>'pgvector não instalado — RAG não funcionará'];
        }

        // ── 4. Embeddings no banco ───────────────────────────────────────
        $totalEmbeddings  = DocumentEmbedding::count();
        $byMunicipality   = DocumentEmbedding::whereNotNull('municipality_id')->count();
        $knowledgeBase    = DocumentEmbedding::whereNull('municipality_id')->count();

        $checks['embeddings'] = [
            'label'  => 'Embeddings indexados',
            'status' => $totalEmbeddings > 0 ? 'ok' : 'warning',
            'detalhe'=> "Total: {$totalEmbeddings} | Por município: {$byMunicipality} | Base geral: {$knowledgeBase}",
            'msg'    => $totalEmbeddings > 0
                ? 'RAG ativo — respostas serão enriquecidas com dados indexados'
                : 'Nenhum documento indexado — chat funciona mas sem RAG (sem dados contextuais)',
        ];

        // ── 5. APIs externas ativas ──────────────────────────────────────
        $apisCatalog = [
            'ibge_municipios','ibge_populacao','atlas_brasil','ipea_data',
            'siconfi','finbra','transparencia','datasus','fns',
            'fnde','inep_censo','inep_ideb','snis','aneel','transferegov','bndes',
        ];
        $apisAtivas = collect($apisCatalog)->filter(fn($k) => SystemSetting::get("integration_{$k}_ativo", false));

        $checks['apis'] = [
            'label'  => 'APIs externas',
            'status' => $apisAtivas->count() > 0 ? 'ok' : 'warning',
            'detalhe'=> $apisAtivas->count().' de '.count($apisCatalog).' APIs ativas',
            'msg'    => $apisAtivas->count() > 0
                ? 'APIs marcadas como ativas: '.implode(', ', $apisAtivas->map(fn($k)=>str_replace('_',' ',$k))->toArray())
                : 'Nenhuma API externa ativa — ative em Configurações → APIs Externas',
        ];

        // ── 6. Embed provider ────────────────────────────────────────────
        $openaiKey = SystemSetting::get('openai_api_key', env('OPENAI_API_KEY', ''));
        $embedMsg  = match($provider) {
            'anthropic' => empty($openaiKey)
                ? 'ATENÇÃO: Anthropic não gera embeddings. É necessário configurar a chave OpenAI para o RAG funcionar.'
                : 'Anthropic como chat + OpenAI para embeddings (configuração correta)',
            'openai'    => !empty($apiKey) ? 'OpenAI usada para chat e embeddings' : 'Chave OpenAI não configurada',
            'gemini'    => 'Gemini usado para chat e embeddings',
            default     => '—',
        };
        $embedStatus = ($provider === 'anthropic' && empty($openaiKey)) ? 'warning' : 'ok';
        $checks['embed'] = [
            'label'  => 'Geração de embeddings (RAG)',
            'status' => $embedStatus,
            'detalhe'=> "Provider embed: ".($provider === 'anthropic' ? 'openai (fallback)' : $provider),
            'msg'    => $embedMsg,
        ];

        // ── 7. Fluxo do chat ─────────────────────────────────────────────
        $municipalities = Municipality::where('subscription_active', true)
            ->withCount('documents')
            ->get(['id','name','onboarding_status','voice_profile','political_map']);

        $munChecks = $municipalities->map(function ($m) {
            $issues = [];
            if (!$m->voice_profile)  $issues[] = 'sem perfil de voz';
            if (!$m->political_map)  $issues[] = 'sem mapa político';
            if ($m->documents_count === 0) $issues[] = 'sem documentos';
            return [
                'nome'   => $m->name,
                'status' => count($issues) === 0 ? 'ok' : (count($issues) >= 2 ? 'warning' : 'info'),
                'issues' => $issues,
            ];
        });

        return view('admin.diagnostic.index', compact('checks', 'munChecks', 'provider', 'model'));
    }

    public function testAI()
    {
        try {
            $service  = app(\App\Services\AI\AIProviderService::class);
            $start    = microtime(true);
            $response = $service->chat([
                ['role' => 'user', 'content' => 'Responda apenas: sistema operacional'],
            ], ['max_tokens' => 20]);
            $ms = round((microtime(true) - $start) * 1000);

            return response()->json([
                'ok'       => true,
                'provider' => $response->provider,
                'model'    => $response->model,
                'resposta' => $response->content,
                'tokens'   => $response->tokensUsed,
                'tempo_ms' => $ms,
            ]);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'erro' => $e->getMessage()], 500);
        }
    }

    public function testRAG(Request $request)
    {
        try {
            $municipality = $request->filled('municipality_id')
                ? Municipality::where('subscription_active', true)->where('id', $request->integer('municipality_id'))->firstOrFail()
                : Municipality::where('subscription_active', true)->firstOrFail();

            $rag   = app(\App\Services\RAG\RAGService::class);
            $start = microtime(true);
            $query = $request->input('query') ?: 'orçamento municipal saúde educação';
            $limit = $request->integer('limit') ?: 10;
            $chunks = $rag->retrieve($query, $municipality, $limit);
            $ms    = round((microtime(true) - $start) * 1000);

            $byGeneral = $chunks->filter(fn($c) => empty($c->municipality_id))->count();
            $byMunicipality = $chunks->filter(fn($c) => !empty($c->municipality_id))->count();

            return response()->json([
                'ok'         => true,
                'municipio'  => $municipality->name,
                'query'      => $query,
                'chunks'     => $chunks->count(),
                'tempo_ms'   => $ms,
                'breakdown'  => [
                    'knowledge_base_general' => $byGeneral,
                    'municipality_specific'  => $byMunicipality,
                ],
                'items'      => $chunks->map(fn($c) => [
                    'municipality_id' => $c->municipality_id,
                    'is_general'      => empty($c->municipality_id),
                    'layer'           => $c->layer ?? null,
                    'category'        => $c->category ?? null,
                    'source'          => $c->source ?? null,
                    'similarity'      => round($c->similarity ?? 0, 6),
                    'preview'         => mb_substr(trim(preg_replace('/\s+/', ' ', $c->content ?? '')), 0, 220),
                    'metadata'        => is_array($c->metadata) ? $c->metadata : json_decode($c->metadata ?? '{}', true),
                ])->values(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'erro' => $e->getMessage()], 500);
        }
    }
}
