@extends('layouts.admin')
@section('title', 'Base de Conhecimento')
@section('content')
<div style="padding:2rem;max-width:1000px">

    {{-- HEADER --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem">
        <div>
            <h1 style="font-size:1.4rem;font-weight:700">Base de Conhecimento</h1>
            <p style="font-size:.85rem;color:#6b7280;margin-top:.3rem">Camada 2 — Documentos curados pelo time do produto, compartilhados com todos os municípios</p>
        </div>
        <button onclick="document.getElementById('modal-upload').style.display='flex'"
            style="padding:.6rem 1.2rem;background:var(--gold);color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">
            + Adicionar documento
        </button>
    </div>

    @if(session('success'))
        <div style="background:#d1fae5;border:1px solid #6ee7b7;padding:.9rem 1rem;border-radius:8px;margin-bottom:1.25rem;color:#065f46;font-size:.88rem">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background:#fee2e2;border:1px solid #fca5a5;padding:.9rem 1rem;border-radius:8px;margin-bottom:1.25rem;color:#991b1b;font-size:.85rem">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    {{-- STATS --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem">
        @foreach([
            ['label'=>'Total de documentos','valor'=>$stats['total'],'cor'=>'#0f1117'],
            ['label'=>'Indexados','valor'=>$stats['indexados'],'cor'=>'#16a34a'],
            ['label'=>'Pendentes','valor'=>$stats['pendentes'],'cor'=>'#d97706'],
            ['label'=>'Com erro','valor'=>$stats['com_erro'],'cor'=>'#dc2626'],
        ] as $s)
        <div style="background:#fff;padding:1rem 1.25rem;border-radius:10px;border:1px solid #e5e7eb">
            <div style="font-size:1.6rem;font-weight:700;color:{{ $s['cor'] }}">{{ $s['valor'] }}</div>
            <div style="font-size:.75rem;color:#6b7280;margin-top:.2rem">{{ $s['label'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- FILTROS --}}
    <form method="GET" style="display:flex;gap:.75rem;margin-bottom:1.25rem;align-items:center;flex-wrap:wrap">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por título..."
            style="flex:1;min-width:200px;padding:.55rem .9rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
        <select name="category" style="padding:.55rem .9rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
            <option value="">Todas as categorias</option>
            @foreach($categories as $val => $label)
                <option value="{{ $val }}" {{ request('category') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" style="padding:.55rem .9rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
            <option value="">Todos os status</option>
            <option value="pending"    {{ request('status')==='pending'    ? 'selected':'' }}>Pendente</option>
            <option value="done"       {{ request('status')==='done'       ? 'selected':'' }}>Indexado</option>
            <option value="processing" {{ request('status')==='processing' ? 'selected':'' }}>Processando</option>
            <option value="failed"     {{ request('status')==='failed'     ? 'selected':'' }}>Com erro</option>
        </select>
        <button type="submit" style="padding:.55rem 1.1rem;background:#0f1117;color:#fff;border:none;border-radius:8px;font-size:.88rem;cursor:pointer">Filtrar</button>
        @if(request()->hasAny(['search','category','status']))
            <a href="{{ route('admin.knowledge-base.index') }}" style="font-size:.85rem;color:#6b7280;text-decoration:none">Limpar</a>
        @endif
    </form>

    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1.25rem;overflow:hidden">
        <div style="padding:1rem 1.5rem;background:#f9fafb;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <div>
                <div style="font-size:.95rem;font-weight:600">Testar se o chat está buscando a Base de Conhecimento</div>
                <div style="font-size:.82rem;color:#6b7280;margin-top:.2rem">Executa a mesma busca RAG usada pelo chat e mostra as fontes retornadas</div>
            </div>
            <div style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap">
                <select id="ragMun" style="padding:.55rem .9rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;min-width:220px">
                    @foreach($municipalities as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>
                <input id="ragQuery" type="text" placeholder="Digite uma pergunta..."
                    style="min-width:280px;padding:.55rem .9rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                <button type="button" onclick="runRagTest()"
                    style="padding:.55rem 1.1rem;background:#0f1117;color:#fff;border:none;border-radius:8px;font-size:.88rem;cursor:pointer">Testar</button>
            </div>
        </div>
        <div style="padding:1rem 1.5rem">
            <div id="ragTestStatus" style="font-size:.82rem;color:#6b7280"></div>
            <div id="ragTestResults" style="margin-top:.75rem;display:grid;gap:.5rem"></div>
        </div>
    </div>

    {{-- LISTA --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden">
        @if($documents->isEmpty())
            <div style="padding:3rem;text-align:center;color:#9ca3af">
                <div style="font-size:2rem;margin-bottom:.75rem">📚</div>
                <div style="font-weight:600;margin-bottom:.3rem">Nenhum documento encontrado</div>
                <div style="font-size:.85rem">Adicione documentos de legislação, programas federais e boas práticas.</div>
            </div>
        @else
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <th style="padding:.75rem 1.5rem;text-align:left;font-size:.78rem;font-weight:600;color:#6b7280">DOCUMENTO</th>
                    <th style="padding:.75rem 1rem;text-align:left;font-size:.78rem;font-weight:600;color:#6b7280">CATEGORIA</th>
                    <th style="padding:.75rem 1rem;text-align:left;font-size:.78rem;font-weight:600;color:#6b7280">STATUS RAG</th>
                    <th style="padding:.75rem 1rem;text-align:left;font-size:.78rem;font-weight:600;color:#6b7280">TAMANHO</th>
                    <th style="padding:.75rem 1rem;text-align:left;font-size:.78rem;font-weight:600;color:#6b7280">ADICIONADO</th>
                    <th style="padding:.75rem 1rem;text-align:center;font-size:.78rem;font-weight:600;color:#6b7280">AÇÕES</th>
                </tr>
            </thead>
            <tbody>
                @foreach($documents as $doc)
                @php
                    $statusStyle = match($doc->indexing_status) {
                        'done'       => 'background:#d1fae5;color:#065f46',
                        'processing' => 'background:#fef3c7;color:#92400e',
                        'failed'     => 'background:#fee2e2;color:#991b1b',
                        default      => 'background:#f3f4f6;color:#6b7280',
                    };
                @endphp
                <tr style="border-bottom:1px solid #f9fafb">
                    <td style="padding:.9rem 1.5rem;max-width:300px">
                        <div style="font-weight:600;font-size:.88rem;color:#0f1117;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $doc->title }}</div>
                        @if($doc->description)
                            <div style="font-size:.75rem;color:#9ca3af;margin-top:.2rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $doc->description }}</div>
                        @endif
                        @if($doc->tags)
                            <div style="margin-top:.35rem;display:flex;flex-wrap:wrap;gap:.25rem">
                                @foreach(array_slice($doc->tags, 0, 3) as $tag)
                                    <span style="padding:.1rem .4rem;background:#f3f4f6;border-radius:4px;font-size:.7rem;color:#6b7280">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td style="padding:.9rem 1rem">
                        <span style="font-size:.8rem;color:#374151">{{ $doc->category_label }}</span>
                        @if($doc->reference_year)
                            <div style="font-size:.72rem;color:#9ca3af">{{ $doc->reference_year }}</div>
                        @endif
                    </td>
                    <td style="padding:.9rem 1rem">
                        <span style="padding:.25rem .6rem;border-radius:99px;font-size:.75rem;font-weight:600;{{ $statusStyle }}">
                            {{ $doc->status_label }}
                        </span>
                        @if($doc->chunks_count)
                            <div style="font-size:.7rem;color:#9ca3af;margin-top:.2rem">{{ $doc->chunks_count }} chunks</div>
                        @endif
                        @if($doc->indexing_status === 'failed' && $doc->indexing_error)
                            <div style="font-size:.7rem;color:#dc2626;margin-top:.2rem" title="{{ $doc->indexing_error }}">Ver erro</div>
                        @endif
                    </td>
                    <td style="padding:.9rem 1rem">
                        <span style="font-size:.82rem;color:#6b7280">{{ $doc->size_formatted }}</span>
                        @if($doc->original_filename)
                            <div style="font-size:.7rem;color:#9ca3af">{{ $doc->mime_type }}</div>
                        @endif
                    </td>
                    <td style="padding:.9rem 1rem">
                        <div style="font-size:.82rem;color:#374151">{{ $doc->created_at->format('d/m/Y') }}</div>
                        @if($doc->publisher)
                            <div style="font-size:.72rem;color:#9ca3af">{{ $doc->publisher->name }}</div>
                        @endif
                    </td>
                    <td style="padding:.9rem 1rem;text-align:center">
                        <div style="display:flex;gap:.4rem;justify-content:center">
                            <button type="button" title="Ver chunks indexados"
                                onclick="openChunks('{{ route('admin.knowledge-base.chunks', $doc) }}')"
                                style="padding:.3rem .6rem;border:1px solid #d1d5db;border-radius:6px;font-size:.75rem;background:#fff;cursor:pointer">Ver</button>
                            {{-- Reindexar --}}
                            <form method="POST" action="{{ route('admin.knowledge-base.reindex', $doc) }}">
                                @csrf @method('PATCH')
                                <button type="submit" title="Re-indexar no RAG"
                                    style="padding:.3rem .6rem;border:1px solid #d1d5db;border-radius:6px;font-size:.75rem;background:#fff;cursor:pointer">↻</button>
                            </form>
                            {{-- Ativar/Desativar --}}
                            <form method="POST" action="{{ route('admin.knowledge-base.toggle', $doc) }}">
                                @csrf @method('PATCH')
                                <button type="submit" title="{{ $doc->is_active ? 'Desativar' : 'Ativar' }}"
                                    style="padding:.3rem .6rem;border:1px solid #d1d5db;border-radius:6px;font-size:.75rem;background:{{ $doc->is_active ? '#fff' : '#f9fafb' }};cursor:pointer;color:{{ $doc->is_active ? '#16a34a' : '#9ca3af' }}">
                                    {{ $doc->is_active ? '●' : '○' }}
                                </button>
                            </form>
                            {{-- Remover --}}
                            <form method="POST" action="{{ route('admin.knowledge-base.destroy', $doc) }}"
                                onsubmit="return confirm('Remover este documento da base de conhecimento?')">
                                @csrf @method('DELETE')
                                <button type="submit" title="Remover"
                                    style="padding:.3rem .6rem;border:1px solid #fca5a5;border-radius:6px;font-size:.75rem;background:#fff;cursor:pointer;color:#dc2626">✕</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- PAGINAÇÃO --}}
        @if($documents->hasPages())
        <div style="padding:1rem 1.5rem;border-top:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center">
            <div style="font-size:.82rem;color:#6b7280">{{ $documents->total() }} documentos</div>
            {{ $documents->links() }}
        </div>
        @endif
        <div style="padding:1rem 1.5rem;border-top:1px solid #f3f4f6;display:flex;justify-content:flex-end">
            <form method="POST" action="{{ route('admin.knowledge-base.cleanup') }}">
                @csrf
                <button type="submit" style="padding:.55rem 1rem;border:1px solid #ef4444;color:#ef4444;background:#fff;border-radius:8px;font-size:.85rem;cursor:pointer">
                    Limpar embeddings órfãos
                </button>
            </form>
        </div>
        @endif
    </div>
</div>

<div id="modal-chunks" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1001;align-items:center;justify-content:center;padding:1rem">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:920px;max-height:90vh;overflow:hidden;display:flex;flex-direction:column">
        <div style="padding:1.05rem 1.5rem;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center;gap:1rem">
            <div>
                <div id="chunksTitle" style="font-size:1rem;font-weight:700">Chunks indexados</div>
                <div id="chunksMeta" style="font-size:.78rem;color:#6b7280;margin-top:.2rem"></div>
            </div>
            <button onclick="closeChunks()"
                style="background:none;border:none;font-size:1.4rem;color:#9ca3af;cursor:pointer;line-height:1">×</button>
        </div>
        <div style="padding:1rem 1.5rem;overflow:auto">
            <div id="chunksStatus" style="font-size:.85rem;color:#6b7280"></div>
            <div id="chunksList" style="margin-top:.75rem;display:grid;gap:.6rem"></div>
        </div>
    </div>
</div>

{{-- MODAL UPLOAD --}}
<div id="modal-upload" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center">
            <h3 style="font-size:1rem;font-weight:700">Adicionar documento</h3>
            <button onclick="document.getElementById('modal-upload').style.display='none'"
                style="background:none;border:none;font-size:1.3rem;color:#9ca3af;cursor:pointer">×</button>
        </div>
        <form method="POST" action="{{ route('admin.knowledge-base.upload') }}" enctype="multipart/form-data" style="padding:1.5rem;display:grid;gap:1rem">
            @csrf
            <div>
                <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.35rem">Título <span style="color:#dc2626">*</span></label>
                <input type="text" name="title" required placeholder="Ex: Lei de Responsabilidade Fiscal — LRF"
                    style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.35rem">Categoria <span style="color:#dc2626">*</span></label>
                    <select name="category" required style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                        <option value="">Selecione...</option>
                        @foreach($categories as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.35rem">Ano de referência</label>
                    <input type="number" name="reference_year" min="2000" max="2030" placeholder="{{ date('Y') }}"
                        style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                </div>
            </div>
            <div>
                <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.35rem">Descrição</label>
                <textarea name="description" rows="2" placeholder="Breve descrição do conteúdo..."
                    style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;resize:vertical;box-sizing:border-box"></textarea>
            </div>
            <div>
                <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.35rem">Tags (separadas por vírgula)</label>
                <input type="text" name="tags" placeholder="LRF, fiscal, municípios, despesa"
                    style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
            </div>
            <div>
                <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.35rem">Arquivo (PDF, DOCX, TXT, XLSX — máx. 20MB)</label>
                <input type="file" name="file" accept=".pdf,.docx,.txt,.xlsx"
                    style="width:100%;padding:.5rem;border:1px solid #d1d5db;border-radius:8px;font-size:.85rem;box-sizing:border-box">
            </div>
            <div>
                <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.35rem">Ou cole o texto diretamente</label>
                <textarea name="content_raw" rows="4" placeholder="Cole o conteúdo do documento aqui..."
                    style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.85rem;resize:vertical;box-sizing:border-box"></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;padding-top:.25rem">
                <button type="button" onclick="document.getElementById('modal-upload').style.display='none'"
                    style="padding:.7rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;background:#fff;cursor:pointer">Cancelar</button>
                <button type="submit"
                    style="padding:.7rem;background:var(--gold);color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">Adicionar</button>
            </div>
        </form>
    </div>
</div>

@if($errors->any())
<script>document.getElementById('modal-upload').style.display='flex';</script>
@endif

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

async function openChunks(url) {
    document.getElementById('modal-chunks').style.display = 'flex';
    document.getElementById('chunksStatus').textContent = 'Carregando...';
    document.getElementById('chunksList').innerHTML = '';
    document.getElementById('chunksTitle').textContent = 'Chunks indexados';
    document.getElementById('chunksMeta').textContent = '';

    try {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.ok) throw new Error(data.erro || 'Falha ao carregar chunks');

        document.getElementById('chunksTitle').textContent = data.doc?.title || 'Chunks indexados';
        document.getElementById('chunksMeta').textContent = `${data.total} chunks no total (mostrando até ${data.limit})`;
        document.getElementById('chunksStatus').textContent = data.items.length === 0 ? 'Nenhum chunk encontrado.' : '';

        const list = document.getElementById('chunksList');
        data.items.forEach((item) => {
            const el = document.createElement('div');
            el.style.cssText = 'border:1px solid #e5e7eb;border-radius:12px;padding:.9rem 1rem;background:#fff';
            const header = document.createElement('div');
            header.style.cssText = 'display:flex;justify-content:space-between;gap:1rem;align-items:center;margin-bottom:.5rem';
            header.innerHTML = `
                <div style="font-weight:700;font-size:.85rem;color:#111827">
                    Chunk #${item.chunk_index}
                    <span style="font-weight:600;color:#6b7280;margin-left:.5rem">${item.token_count ?? '—'} tokens</span>
                </div>
                <div style="font-size:.75rem;color:#6b7280">${item.source ?? ''}</div>
            `;
            const body = document.createElement('div');
            body.style.cssText = 'font-size:.85rem;color:#374151;line-height:1.6;white-space:pre-wrap';
            body.textContent = item.content || '';
            el.appendChild(header);
            el.appendChild(body);
            list.appendChild(el);
        });
    } catch (e) {
        document.getElementById('chunksStatus').style.color = '#dc2626';
        document.getElementById('chunksStatus').textContent = e.message || 'Erro ao carregar chunks';
    }
}

function closeChunks() {
    document.getElementById('modal-chunks').style.display = 'none';
}

async function runRagTest() {
    const query = document.getElementById('ragQuery').value.trim();
    const munId = document.getElementById('ragMun').value;
    const status = document.getElementById('ragTestStatus');
    const results = document.getElementById('ragTestResults');
    results.innerHTML = '';
    status.style.color = '#6b7280';

    if (!query) {
        status.textContent = 'Digite uma pergunta para testar.';
        return;
    }

    status.textContent = 'Testando busca...';

    try {
        const res = await fetch('{{ route("admin.diagnostic.test-rag") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ query, municipality_id: munId, limit: 10 }),
        });
        const data = await res.json();
        if (!data.ok) throw new Error(data.erro || 'Falha ao testar busca');

        status.textContent = `${data.chunks} chunks retornados em ${data.tempo_ms}ms. Base geral: ${data.breakdown?.knowledge_base_general ?? 0} | Município: ${data.breakdown?.municipality_specific ?? 0}`;

        (data.items || []).forEach((it) => {
            const el = document.createElement('div');
            el.style.cssText = 'border:1px solid #e5e7eb;border-radius:12px;padding:.85rem 1rem;background:#fff';
            el.innerHTML = `
                <div style="display:flex;justify-content:space-between;gap:1rem;align-items:center">
                    <div style="font-weight:700;font-size:.85rem;color:#111827">${it.source || 'Fonte'}</div>
                    <div style="font-size:.75rem;color:#6b7280">camada: ${it.layer || '—'} | similaridade: ${(it.similarity ?? 0).toFixed(3)}</div>
                </div>
                <div style="font-size:.82rem;color:#6b7280;margin-top:.2rem">${it.category || '—'} ${it.is_general ? ' | base geral' : ' | município'}</div>
                <div style="font-size:.85rem;color:#374151;margin-top:.45rem;line-height:1.6">${escapeHtml(it.preview || '')}</div>
            `;
            results.appendChild(el);
        });

        if (!data.items || data.items.length === 0) {
            const el = document.createElement('div');
            el.style.cssText = 'font-size:.85rem;color:#9ca3af';
            el.textContent = 'Nenhuma fonte encontrada acima do limiar de similaridade. Dica: tente uma pergunta mais específica ou reduza o similarity_threshold.';
            results.appendChild(el);
        }
    } catch (e) {
        status.style.color = '#dc2626';
        status.textContent = e.message || 'Erro ao testar busca';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}
</script>
@endsection
