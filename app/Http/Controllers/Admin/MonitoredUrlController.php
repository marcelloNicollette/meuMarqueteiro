<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonitoredUrl;
use App\Services\RAG\UrlIndexerService;
use Illuminate\Http\Request;

class MonitoredUrlController extends Controller
{
    public function __construct(private UrlIndexerService $indexer) {}

    public function index(Request $request)
    {
        // Admin pode filtrar por município ou ver todos
        $query = MonitoredUrl::with('municipality')->orderByDesc('created_at');

        if ($request->filled('municipality_id')) {
            $query->where('municipality_id', $request->municipality_id);
        }

        $urls = $query->get();

        $stats = [
            'total'     => $urls->count(),
            'indexadas' => $urls->where('fetch_status', 'indexed')->count(),
            'falhas'    => $urls->where('fetch_status', 'failed')->count(),
            'pendentes' => $urls->whereIn('fetch_status', ['pending', 'fetching'])->count(),
            'chunks'    => $urls->sum('chunks_count'),
        ];

        $municipalities = \App\Models\Municipality::where('subscription_active', true)
            ->orderBy('name')->get();

        return view('admin.urls.index', compact('urls', 'stats', 'municipalities'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'municipality_id'   => 'nullable|exists:municipalities,id',
            'url'               => 'required|url|max:500',
            'title'             => 'nullable|string|max:200',
            'category'          => 'nullable|in:geral,noticias,transparencia,legislacao,governo,outros',
            'description'       => 'nullable|string|max:500',
            'refresh_frequency' => 'nullable|in:manual,daily,weekly,monthly',
            'index_subpages'    => 'boolean',
        ]);

        // Verificar se URL já existe para este município
        // Verificar duplicata (global ou por município)
        $dupQuery = MonitoredUrl::where('url', $data['url']);
        if (!empty($data['municipality_id'])) {
            $dupQuery->where('municipality_id', $data['municipality_id']);
        } else {
            $dupQuery->whereNull('municipality_id');
        }
        if ($dupQuery->exists()) {
            return back()->with('error', 'Esta URL já está cadastrada.');
        }

        $url = MonitoredUrl::create(array_merge($data, [
            'created_by'        => auth()->id(),
            'category'          => $data['category'] ?? 'geral',
            'refresh_frequency' => $data['refresh_frequency'] ?? 'daily',
            'index_subpages'    => $request->boolean('index_subpages'),
        ]));

        // Indexar imediatamente em background (síncrono por ora)
        $result = $this->indexer->index($url);

        if ($result['ok']) {
            return back()->with('success', "URL adicionada e indexada com sucesso — {$result['chunks']} trechos disponíveis para o assistente.");
        }

        return back()->with('warning', "URL adicionada, mas houve erro na indexação: {$result['error']}. Tente re-indexar manualmente.");
    }

    public function reindex($id)
    {
        $url = MonitoredUrl::findOrFail($id);

        $result = $this->indexer->index($url);

        if ($result['ok']) {
            return back()->with('success', "Re-indexação concluída — {$result['chunks']} trechos atualizados.");
        }

        return back()->with('error', "Falha na re-indexação: {$result['error']}");
    }

    public function preview($id)
    {
        $url = MonitoredUrl::findOrFail($id);

        $chunks = \App\Models\DocumentEmbedding::where('municipality_id', $url->municipality_id)
            ->where('layer', 'url_monitor')
            ->where('source', $url->url)
            ->orderBy('chunk_index')
            ->get(['chunk_index', 'content', 'token_count', 'metadata']);

        return response()->json([
            'url'         => $url->url,
            'title'       => $url->display_title,
            'total_chunks' => $chunks->count(),
            'chunks'      => $chunks->map(fn($c) => [
                'index'   => $c->chunk_index,
                'tokens'  => $c->token_count,
                'preview' => mb_substr($c->content, 0, 300) . (mb_strlen($c->content) > 300 ? '...' : ''),
                'source'  => $c->metadata['url'] ?? $url->url,
            ]),
        ]);
    }

    public function toggle($id)
    {
        $url     = MonitoredUrl::findOrFail($id);
        $wasActive = $url->is_active;

        if ($wasActive) {
            // Pausar: remover embeddings do assistente
            \App\Models\DocumentEmbedding::where('municipality_id', $url->municipality_id)
                ->where('layer', 'url_monitor')
                ->where('source', $url->url)
                ->delete();

            $url->update(['is_active' => false, 'chunks_count' => 0]);

            return back()->with('success', 'URL pausada — conteúdo removido do assistente. Reative para restaurar.');
        } else {
            // Reativar e re-indexar
            $url->update(['is_active' => true, 'fetch_status' => 'pending', 'last_indexed_at' => null]);

            try {
                $indexer = app(\App\Services\RAG\UrlIndexerService::class);
                $result  = $indexer->index($url);
                $msg = $result['ok']
                    ? "URL reativada — {$result['chunks']} trechos disponíveis para o assistente."
                    : "URL reativada, mas houve erro na indexação: {$result['error']}";
            } catch (\Exception $e) {
                $msg = 'URL reativada. Re-indexe manualmente se necessário.';
            }

            return back()->with('success', $msg);
        }
    }

    public function update(Request $request, $id)
    {
        $url = MonitoredUrl::findOrFail($id);

        $data = $request->validate([
            'title'             => 'nullable|string|max:200',
            'municipality_id'   => 'nullable|exists:municipalities,id',
            'category'          => 'nullable|in:geral,noticias,transparencia,legislacao,governo,outros',
            'description'       => 'nullable|string|max:500',
            'refresh_frequency' => 'nullable|in:manual,daily,weekly,monthly',
            'index_subpages'    => 'boolean',
        ]);

        $municipalityChanged = ($url->municipality_id != ($data['municipality_id'] ?: null));

        $url->update([
            'title'             => $data['title'] ?: null,
            'municipality_id'   => $data['municipality_id'] ?: null,
            'category'          => $data['category'] ?? $url->category,
            'description'       => $data['description'] ?: null,
            'refresh_frequency' => $data['refresh_frequency'] ?? $url->refresh_frequency,
            'index_subpages'    => $request->boolean('index_subpages'),
        ]);

        // Se município mudou, mover os embeddings para o novo municipality_id
        if ($municipalityChanged) {
            \App\Models\DocumentEmbedding::where('source', $url->url)
                ->where('layer', 'url_monitor')
                ->update(['municipality_id' => $data['municipality_id'] ?: null]);
        }

        return back()->with('success', 'URL atualizada com sucesso.');
    }

    public function destroy($id)
    {
        $url = MonitoredUrl::findOrFail($id);

        // Remover embeddings associados
        \App\Models\DocumentEmbedding::where('municipality_id', $url->municipality_id)
            ->where('layer', 'url_monitor')
            ->where('source', $url->url)
            ->delete();

        $url->delete();

        return back()->with('success', 'URL removida e embeddings excluídos.');
    }
}
