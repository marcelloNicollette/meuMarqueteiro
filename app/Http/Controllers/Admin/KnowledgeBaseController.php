<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentEmbedding;
use App\Models\KnowledgeBaseDocument;
use App\Models\Municipality;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class KnowledgeBaseController extends Controller
{
    private array $categories = [
        'legislation'      => 'Legislação',
        'federal_programs' => 'Programas Federais',
        'benchmark'        => 'Benchmarks',
        'best_practice'    => 'Boas Práticas',
        'communication'    => 'Comunicação Política',
        'policy'           => 'Políticas Setoriais',
        'outros'           => 'Outros',
    ];

    public function index(Request $request)
    {
        $query = KnowledgeBaseDocument::with('publisher')->orderByDesc('created_at');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('indexing_status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $documents  = $query->paginate(15)->withQueryString();
        $categories = $this->categories;
        $municipalities = Municipality::where('subscription_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $stats = [
            'total'       => KnowledgeBaseDocument::count(),
            'indexados'   => KnowledgeBaseDocument::where('indexing_status', 'done')->count(),
            'pendentes'   => KnowledgeBaseDocument::where('indexing_status', 'pending')->count(),
            'com_erro'    => KnowledgeBaseDocument::where('indexing_status', 'failed')->count(),
        ];

        return view('admin.knowledge-base.index', compact('documents', 'categories', 'stats', 'municipalities'));
    }

    public function chunks(KnowledgeBaseDocument $doc): JsonResponse
    {
        $query = DocumentEmbedding::query()
            ->whereNull('municipality_id')
            ->where('layer', 'knowledge_base')
            ->where(function ($q) use ($doc) {
                $q->where('metadata->document_id', $doc->id)
                    ->orWhere('source', $doc->title);
            })
            ->orderBy('chunk_index');

        $total = (clone $query)->count();

        $chunks = $query->limit(200)->get([
            'id',
            'chunk_index',
            'token_count',
            'category',
            'source',
            'content',
            'metadata',
            'created_at',
        ]);

        $items = $chunks->map(fn($c) => [
            'id'            => $c->id,
            'chunk_index'   => $c->chunk_index,
            'token_count'   => $c->token_count,
            'category'      => $c->category,
            'source'        => $c->source,
            'metadata'      => $c->metadata,
            'content'       => mb_substr($c->content ?? '', 0, 4000),
            'content_short' => mb_substr(trim(preg_replace('/\s+/', ' ', $c->content ?? '')), 0, 260),
        ])->values();

        return response()->json([
            'ok'     => true,
            'doc'    => ['id' => $doc->id, 'title' => $doc->title],
            'total'  => $total,
            'items'  => $items,
            'limit'  => 200,
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'title'          => 'required|string|max:255',
            'category'       => 'required|in:' . implode(',', array_keys($this->categories)),
            'description'    => 'nullable|string|max:1000',
            'reference_year' => 'nullable|integer|min:2000|max:2030',
            'valid_until'    => 'nullable|date',
            'tags'           => 'nullable|string',
            'file'           => 'nullable|file|mimes:pdf,docx,txt,xlsx|max:51200',
            'content_raw'    => 'nullable|string',
        ]);

        $data = [
            'title'          => $request->title,
            'category'       => $request->category,
            'description'    => $request->description,
            'reference_year' => $request->reference_year,
            'valid_until'    => $request->valid_until,
            'tags'           => $request->filled('tags')
                ? array_map('trim', explode(',', $request->tags))
                : null,
            'content_raw'    => $request->content_raw,
            'published_by'   => $request->user()?->id,
            'indexing_status' => 'pending',
            'is_active'      => true,
            'disk'           => 'local',
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('knowledge-base', 'local');
            $data['path']              = $path;
            $data['mime_type']         = $file->getMimeType();
            $data['size_bytes']        = $file->getSize();
            $data['original_filename'] = $file->getClientOriginalName();
        }

        KnowledgeBaseDocument::create($data);

        return back()->with('success', 'Documento adicionado à base de conhecimento.');
    }

    public function destroy($id)
    {
        $doc = KnowledgeBaseDocument::findOrFail($id);

        if ($doc->path) {
            Storage::disk($doc->disk)->delete($doc->path);
        }

        $doc->delete();

        return back()->with('success', 'Documento removido da base de conhecimento.');
    }

    public function toggleActive($id)
    {
        $doc = KnowledgeBaseDocument::findOrFail($id);
        $doc->update(['is_active' => !$doc->is_active]);
        return back()->with('success', 'Status do documento atualizado.');
    }

    public function reindex($id)
    {
        $doc = KnowledgeBaseDocument::findOrFail($id);
        $doc->update(['indexing_status' => 'pending', 'indexing_error' => null]);

        // Indexar de forma síncrona via artisan
        try {
            Artisan::call('marqueteiro:index-knowledge-base', ['--id' => $id, '--force' => true]);
            $output = Artisan::output();
            $status = str_contains($output, 'chunks indexados') ? 'Documento indexado com sucesso.' : 'Indexação iniciada.';
        } catch (\Throwable $e) {
            $status = 'Marcado para indexação. Execute manualmente: php artisan marqueteiro:index-knowledge-base';
        }

        return back()->with('success', $status);
    }
}
