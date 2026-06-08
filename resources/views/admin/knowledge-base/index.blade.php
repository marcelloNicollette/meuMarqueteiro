@extends('layouts.admin')
@section('title', 'Base de Conhecimento')

@push('styles')
    <style>
        .kb-tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid var(--border);
            margin-bottom: 1.5rem;
        }

        .kb-tab {
            padding: .65rem 1.4rem;
            font-size: .88rem;
            font-weight: 500;
            color: var(--ink-muted);
            cursor: pointer;
            border: none;
            background: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            display: flex;
            align-items: center;
            gap: .4rem;
            transition: color .15s;
            text-decoration: none;
        }

        .kb-tab:hover {
            color: var(--ink);
        }

        .kb-tab.active {
            color: var(--gold);
            border-bottom-color: var(--gold);
            font-weight: 600;
        }

        .kb-tab .tab-count {
            background: var(--border);
            color: var(--ink-muted);
            font-size: .68rem;
            font-weight: 600;
            padding: .1rem .4rem;
            border-radius: 999px;
            min-width: 18px;
            text-align: center;
        }

        .kb-tab.active .tab-count {
            background: #fef3c7;
            color: var(--gold);
        }

        .tab-panel {
            display: none;
        }

        .tab-panel.active {
            display: block;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            border-radius: 8px;
            font-family: "Inter", sans-serif;
            font-size: .83rem;
            font-weight: 500;
            background: var(--ink);
            color: #fff;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background .15s;
        }

        .btn-primary:hover {
            background: #1e2230;
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            border-radius: 8px;
            font-family: "Inter", sans-serif;
            font-size: .83rem;
            font-weight: 500;
            background: var(--white);
            color: var(--ink-soft);
            border: 1.5px solid var(--border);
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
        }

        .btn-secondary:hover {
            border-color: var(--ink);
            color: var(--ink);
        }

        .btn-gold {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            border-radius: 8px;
            font-family: "Inter", sans-serif;
            font-size: .83rem;
            font-weight: 500;
            background: var(--gold);
            color: #fff;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: opacity .15s;
        }

        .btn-gold:hover {
            opacity: .88;
        }

        .btn-danger {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .35rem .6rem;
            border-radius: 6px;
            font-size: .75rem;
            font-weight: 500;
            background: none;
            color: var(--red);
            border: 1.5px solid var(--red-bg);
            cursor: pointer;
            transition: all .15s;
        }

        .btn-danger:hover {
            background: var(--red-bg);
        }

        .alert-success {
            background: var(--green-bg);
            color: var(--green);
            border: 1px solid #c3e6d0;
            border-radius: 8px;
            padding: .75rem 1rem;
            font-size: .84rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .alert-error {
            background: var(--red-bg);
            color: var(--red);
            border: 1px solid #f5c6c6;
            border-radius: 8px;
            padding: .75rem 1rem;
            font-size: .84rem;
            margin-bottom: 1rem;
        }

        .alert-warning {
            background: #fef3c7;
            color: #b8902a;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: .75rem 1rem;
            font-size: .84rem;
            margin-bottom: 1rem;
        }

        input[type=text],
        input[type=number],
        input[type=url],
        input[type=file],
        select,
        textarea {
            width: 100%;
            padding: .55rem .8rem;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-family: "Inter", sans-serif;
            font-size: .85rem;
            color: var(--ink);
            background: var(--white);
            transition: border-color .15s;
            outline: none;
            box-sizing: border-box;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--gold);
        }

        input::placeholder,
        textarea::placeholder {
            color: var(--ink-muted);
        }

        label {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            color: var(--ink-soft);
            margin-bottom: .3rem;
        }

        .kpi-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        @media(max-width:700px) {
            .kpi-grid-4 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .kpi-box {
            background: var(--white);
            padding: 1rem 1.25rem;
            border-radius: 10px;
            border: 1px solid var(--border);
        }

        .kpi-box-val {
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1;
        }

        .kpi-box-label {
            font-size: .75rem;
            color: var(--ink-muted);
            margin-top: .2rem;
        }

        .docs-table-wrap {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            padding: .65rem 1rem;
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ink-muted);
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            text-align: left;
        }

        tbody td {
            padding: .8rem 1rem;
            font-size: .83rem;
            color: var(--ink);
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover td {
            background: var(--surface);
        }

        .url-list {
            display: flex;
            flex-direction: column;
            gap: .6rem;
        }

        .url-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem 1.1rem;
            display: grid;
            grid-template-columns: 32px 1fr auto;
            gap: .85rem;
            align-items: start;
        }

        .url-favicon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: var(--surface);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            overflow: hidden;
        }

        .url-favicon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .url-badge {
            padding: .15rem .5rem;
            border-radius: 4px;
            font-size: .68rem;
            font-weight: 600;
        }

        .url-actions {
            display: flex;
            gap: .4rem;
            align-items: center;
            flex-shrink: 0;
        }

        .add-url-card {
            background: var(--surface);
            border: 1.5px dashed var(--border);
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .add-url-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: .6rem;
            align-items: end;
        }

        .add-url-extras {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: .6rem;
            align-items: end;
            margin-top: .6rem;
        }

        @media(max-width:700px) {
            .add-url-grid {
                grid-template-columns: 1fr;
            }

            .add-url-extras {
                grid-template-columns: 1fr 1fr;
            }
        }

        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 9px;
            padding: .85rem 1rem;
            font-size: .8rem;
            color: #1e40af;
            line-height: 1.6;
            margin-bottom: 1.25rem;
        }

        .topbar-search {
            input[type=text] {
                padding: 0;
                border: 0;
                background: none;
            }
        }
    </style>
@endpush

@section('content')
    <div style="padding:2rem;max-width:1080px">

        <div
            style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
            <div>
                <h1 style="font-size:1.4rem;font-weight:700;color:var(--ink)">Base de Conhecimento</h1>
                <p style="font-size:.84rem;color:var(--ink-muted);margin-top:.25rem">Documentos e URLs indexados para o
                    assistente — compartilhados com todos os municípios</p>
            </div>
        </div>

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert-error">{{ session('error') }}</div>
        @endif
        @if (session('warning'))
            <div class="alert-warning">{{ session('warning') }}</div>
        @endif

        <div class="kb-tabs">
            <button class="kb-tab {{ request('tab', 'docs') === 'docs' ? 'active' : '' }}" onclick="switchTab('docs')">
                <svg viewBox="0 0 24 24" fill="currentColor" style="width:15px;height:15px">
                    <path
                        d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z" />
                </svg>
                Documentos
                <span class="tab-count">{{ $stats['total'] }}</span>
            </button>
            <button class="kb-tab {{ request('tab') === 'urls' ? 'active' : '' }}" onclick="switchTab('urls')">
                <svg viewBox="0 0 24 24" fill="currentColor" style="width:15px;height:15px">
                    <path
                        d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z" />
                </svg>
                URLs Monitoradas
                <span class="tab-count">{{ $urlStats['total'] }}</span>
            </button>
        </div>

        {{-- ABA DOCUMENTOS --}}
        <div id="tab-docs" class="tab-panel {{ request('tab', 'docs') === 'docs' ? 'active' : '' }}">
            <div class="kpi-grid-4">
                <div class="kpi-box">
                    <div class="kpi-box-val" style="color:var(--ink)">{{ $stats['total'] }}</div>
                    <div class="kpi-box-label">Total</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-box-val" style="color:var(--green)">{{ $stats['indexados'] }}</div>
                    <div class="kpi-box-label">Indexados</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-box-val" style="color:#d97706">{{ $stats['pendentes'] }}</div>
                    <div class="kpi-box-label">Pendentes</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-box-val" style="color:var(--red)">{{ $stats['com_erro'] }}</div>
                    <div class="kpi-box-label">Com erro</div>
                </div>
            </div>
            <div style="display:flex;gap:.6rem;margin-bottom:1.25rem;align-items:center;flex-wrap:wrap">
                <form method="GET" style="display:flex;gap:.6rem;flex:1;flex-wrap:wrap">
                    <input type="hidden" name="tab" value="docs">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por título..."
                        style="flex:1;min-width:180px">
                    <select name="category" style="width:auto">
                        <option value="">Todas as categorias</option>
                        @foreach ($categories as $val => $label)
                            <option value="{{ $val }}" {{ request('category') === $val ? 'selected' : '' }}>
                                {{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="status" style="width:auto">
                        <option value="">Todos os status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendente</option>
                        <option value="done" {{ request('status') === 'done' ? 'selected' : '' }}>Indexado</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Com erro</option>
                    </select>
                    <button type="submit" class="btn-primary" style="font-size:.82rem">Filtrar</button>
                </form>
                <button onclick="document.getElementById('modal-upload').style.display='flex'" class="btn-gold"
                    style="font-size:.82rem;flex-shrink:0">
                    + Adicionar documento
                </button>
            </div>
            <div class="docs-table-wrap">
                @if ($documents->isEmpty())
                    <div style="text-align:center;padding:3rem;color:var(--ink-muted);font-size:.85rem">Nenhum documento
                        encontrado.</div>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Categoria</th>
                                <th>Status</th>
                                <th>Chunks</th>
                                <th>Indexado em</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documents as $doc)
                                @php
                                    $sc = [
                                        'done' => ['bg' => '#dcfce7', 'text' => '#16a34a', 'label' => 'Indexado'],
                                        'processing' => [
                                            'bg' => '#dbeafe',
                                            'text' => '#1e40af',
                                            'label' => 'Processando',
                                        ],
                                        'failed' => ['bg' => '#fee2e2', 'text' => '#dc2626', 'label' => 'Erro'],
                                        'pending' => ['bg' => '#f3f4f6', 'text' => '#6b7280', 'label' => 'Pendente'],
                                    ][$doc->indexing_status] ?? [
                                        'bg' => '#f3f4f6',
                                        'text' => '#6b7280',
                                        'label' => 'Pendente',
                                    ];
                                @endphp
                                <tr>
                                    <td>
                                        <div style="font-weight:500">{{ $doc->title }}</div>
                                        @if ($doc->original_filename)
                                            <div style="font-size:.73rem;color:var(--ink-muted)">
                                                {{ $doc->original_filename }} · {{ $doc->size_formatted }}</div>
                                        @endif
                                    </td>
                                    <td><span
                                            style="font-size:.78rem;background:var(--surface);padding:.2rem .5rem;border-radius:4px;color:var(--ink-soft)">{{ $doc->category_label }}</span>
                                    </td>
                                    <td><span
                                            style="padding:.2rem .6rem;border-radius:4px;font-size:.75rem;font-weight:600;background:{{ $sc['bg'] }};color:{{ $sc['text'] }}">{{ $sc['label'] }}</span>
                                    </td>
                                    <td style="color:var(--ink-muted);font-size:.82rem">{{ $doc->chunks_count ?? '—' }}
                                    </td>
                                    <td style="color:var(--ink-muted);font-size:.78rem;white-space:nowrap">
                                        {{ $doc->indexed_at?->format('d/m/Y') ?? '—' }}</td>
                                    <td>
                                        <div style="display:flex;gap:.3rem;justify-content:flex-end">
                                            <form method="POST"
                                                action="{{ route('admin.knowledge-base.reindex', $doc) }}">@csrf
                                                @method('PATCH')<button type="submit" class="btn-secondary"
                                                    style="padding:.35rem .6rem;font-size:.72rem"
                                                    title="Re-indexar">↻</button></form>
                                            <form method="POST" action="{{ route('admin.knowledge-base.toggle', $doc) }}">
                                                @csrf @method('PATCH')<button type="submit" class="btn-secondary"
                                                    style="padding:.35rem .6rem;font-size:.72rem;color:{{ $doc->is_active ? 'var(--green)' : 'var(--ink-muted)' }}">{{ $doc->is_active ? '●' : '○' }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.knowledge-base.destroy', $doc) }}"
                                                onsubmit="return confirm('Remover?')">@csrf @method('DELETE')<button
                                                    type="submit" class="btn-danger">✕</button></form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if ($documents->hasPages())
                        <div
                            style="padding:1rem 1.5rem;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                            <div style="font-size:.82rem;color:var(--ink-muted)">{{ $documents->total() }} documentos</div>
                            {{ $documents->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- ABA URLs --}}
        <div id="tab-urls" class="tab-panel {{ request('tab') === 'urls' ? 'active' : '' }}">
            <div class="info-box"><strong>Como funciona:</strong> cada URL adicionada é visitada via Jina Reader, o texto é
                extraído e indexado como conhecimento do assistente. URLs com frequência <em>Diária</em> são re-indexadas às
                5h pelo scheduler.</div>
            <div class="kpi-grid-4">
                <div class="kpi-box">
                    <div class="kpi-box-val" style="color:var(--ink)">{{ $urlStats['total'] }}</div>
                    <div class="kpi-box-label">URLs</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-box-val" style="color:var(--green)">{{ $urlStats['indexadas'] }}</div>
                    <div class="kpi-box-label">Indexadas</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-box-val" style="color:var(--gold)">{{ number_format($urlStats['chunks']) }}</div>
                    <div class="kpi-box-label">Trechos no assistente</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-box-val" style="{{ $urlStats['falhas'] > 0 ? 'color:var(--red)' : '' }}">
                        {{ $urlStats['falhas'] }}</div>
                    <div class="kpi-box-label">Com erro</div>
                </div>
            </div>

            <form method="GET" style="display:flex;gap:.6rem;margin-bottom:1rem;flex-wrap:wrap">
                <input type="hidden" name="tab" value="urls">
                <select name="municipality_id" onchange="this.form.submit()" style="max-width:280px">
                    <option value="">Todos os municípios</option>
                    @foreach ($municipalities as $m)
                        <option value="{{ $m->id }}" {{ request('municipality_id') == $m->id ? 'selected' : '' }}>
                            {{ $m->name }}</option>
                    @endforeach
                </select>
            </form>

            <div class="add-url-card">
                <h3 style="font-size:.88rem;font-weight:600;color:var(--ink);margin:0 0 1rem">+ Adicionar nova URL</h3>
                <form method="POST" action="{{ route('admin.monitored-urls.store') }}" id="addUrlForm">
                    @csrf
                    <div style="margin-bottom:.6rem">
                        <label>Município <span style="font-size:.72rem;color:var(--ink-muted);font-weight:400">(vazio =
                                global)</span></label>
                        <select name="municipality_id" style="max-width:360px">
                            <option value="">🌐 Todos os municípios — URL global</option>
                            @foreach ($municipalities as $m)
                                <option value="{{ $m->id }}"
                                    {{ old('municipality_id') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="add-url-grid">
                        <div><label>URL *</label><input type="url" name="url" required
                                placeholder="https://prefeitura.gov.br" value="{{ old('url') }}"></div>
                        <button type="submit" class="btn-primary" id="btnAddUrl"
                            style="white-space:nowrap;align-self:flex-end">+ Adicionar e indexar</button>
                    </div>
                    <div class="add-url-extras">
                        <div><label>Título</label><input type="text" name="title"
                                placeholder="ex: Site da Prefeitura" value="{{ old('title') }}"></div>
                        <div><label>Categoria</label><select name="category">
                                <option value="geral">Geral</option>
                                <option value="noticias">Notícias</option>
                                <option value="transparencia">Transparência</option>
                                <option value="legislacao">Legislação</option>
                                <option value="governo">Governo</option>
                            </select></div>
                        <div><label>Atualização</label><select name="refresh_frequency">
                                <option value="daily">Diária</option>
                                <option value="weekly">Semanal</option>
                                <option value="monthly">Mensal</option>
                                <option value="manual">Manual</option>
                            </select></div>
                        <div style="display:flex;align-items:flex-end;padding-bottom:.1rem;gap:.4rem"><input
                                type="checkbox" name="index_subpages" id="subpages" value="1"
                                style="width:auto"><label for="subpages"
                                style="font-size:.78rem;cursor:pointer;white-space:nowrap;margin:0">Subpáginas</label>
                        </div>
                    </div>
                </form>
            </div>

            @if ($urls->isEmpty())
                <div
                    style="text-align:center;padding:2.5rem;background:var(--white);border:1px solid var(--border);border-radius:10px;color:var(--ink-muted);font-size:.85rem">
                    Nenhuma URL cadastrada ainda.</div>
            @else
                <div class="url-list">
                    @foreach ($urls as $url)
                        @php
                            $sc = $url->status_color;
                            $favicon =
                                'https://www.google.com/s2/favicons?domain=' .
                                parse_url($url->url, PHP_URL_HOST) .
                                '&sz=32';
                        @endphp
                        <div class="url-card" style="{{ !$url->is_active ? 'opacity:.55' : '' }}">
                            <div class="url-favicon"><img src="{{ $favicon }}" alt=""
                                    onerror="this.style.display='none'" style="display:block"><span
                                    style="display:none">🌐</span></div>
                            <div>
                                <div style="font-weight:600;font-size:.88rem;color:var(--ink)">{{ $url->display_title }}
                                </div>
                                <div style="font-size:.75rem;color:var(--ink-muted)"><a href="{{ $url->url }}"
                                        target="_blank"
                                        style="color:var(--ink-muted);text-decoration:none">{{ $url->url }}</a></div>
                                @if ($url->municipality)
                                    <div style="font-size:.72rem;color:var(--gold)">{{ $url->municipality->name }}</div>
                                @else
                                    <div
                                        style="font-size:.72rem;color:#1e40af;background:#eff6ff;display:inline-block;padding:.1rem .4rem;border-radius:3px;margin-top:.1rem">
                                        🌐 Global — todos os municípios</div>
                                @endif
                                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.35rem;align-items:center">
                                    <span class="url-badge"
                                        style="background:{{ $sc['bg'] }};color:{{ $sc['text'] }}">{{ $url->status_label }}</span>
                                    <span class="url-badge"
                                        style="background:var(--surface);color:var(--ink-muted)">{{ $url->category_label }}</span>
                                    @if ($url->chunks_count)
                                        <span style="font-size:.72rem;color:var(--ink-muted)">{{ $url->chunks_count }}
                                            trechos</span>
                                    @endif
                                    @if ($url->last_indexed_at)
                                        <span
                                            style="font-size:.72rem;color:var(--ink-muted)">{{ $url->last_indexed_at->diffForHumans() }}</span>
                                    @endif
                                    @if ($url->index_subpages)
                                        <span
                                            style="font-size:.68rem;background:#eff6ff;color:#1e40af;padding:.1rem .4rem;border-radius:3px">subpáginas</span>
                                    @endif
                                </div>
                            </div>
                            <div class="url-actions">
                                <button type="button" class="btn-secondary" style="padding:.35rem .6rem" title="Editar"
                                    onclick="editUrl({{ $url->id }}, '{{ addslashes($url->display_title) }}', '{{ addslashes($url->url) }}', '{{ $url->municipality_id }}', '{{ addslashes($url->category) }}', '{{ addslashes($url->refresh_frequency) }}', {{ $url->index_subpages ? 'true' : 'false' }}, '{{ addslashes($url->description ?? '') }}')">
                                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px">
                                        <path
                                            d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" />
                                    </svg>
                                </button>
                                @if ($url->chunks_count > 0)
                                    <button type="button" class="btn-secondary" style="padding:.35rem .6rem"
                                        title="Ver conteúdo"
                                        onclick="previewUrl({{ $url->id }}, '{{ addslashes($url->display_title) }}')">
                                        <svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px">
                                            <path
                                                d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                                        </svg>
                                    </button>
                                @endif
                                <form method="POST" action="{{ route('admin.monitored-urls.reindex', $url->id) }}">
                                    @csrf<button type="submit" class="btn-secondary" style="padding:.35rem .6rem"
                                        title="Re-indexar"><svg viewBox="0 0 24 24" fill="currentColor"
                                            style="width:13px;height:13px">
                                            <path
                                                d="M17.65 6.35A7.958 7.958 0 0012 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0112 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z" />
                                        </svg></button></form>
                                <form method="POST" action="{{ route('admin.monitored-urls.toggle', $url->id) }}">
                                    @csrf<button type="submit" class="btn-secondary" style="padding:.35rem .6rem"
                                        title="{{ $url->is_active ? 'Pausar' : 'Ativar' }}">{{ $url->is_active ? '⏸' : '▶' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.monitored-urls.destroy', $url->id) }}"
                                    onsubmit="return confirm('Remover?')">@csrf @method('DELETE')<button type="submit"
                                        class="btn-danger" style="padding:.35rem .6rem" title="Remover"><svg
                                            viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px">
                                            <path
                                                d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" />
                                        </svg></button></form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Modal upload --}}
    <div id="modal-upload"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem">
        <div style="background:#fff;border-radius:16px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto">
            <div
                style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                <h3 style="font-size:1rem;font-weight:700">Adicionar documento</h3>
                <button onclick="document.getElementById('modal-upload').style.display='none'"
                    style="background:none;border:none;font-size:1.3rem;color:var(--ink-muted);cursor:pointer">×</button>
            </div>
            <form method="POST" action="{{ route('admin.knowledge-base.upload') }}" enctype="multipart/form-data"
                style="padding:1.5rem;display:grid;gap:1rem">
                @csrf
                <div><label>Título *</label><input type="text" name="title" required
                        placeholder="Ex: Lei de Responsabilidade Fiscal"></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                    <div><label>Categoria *</label><select name="category" required>
                            <option value="">Selecione...</option>
                            @foreach ($categories as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select></div>
                    <div><label>Ano de referência</label><input type="number" name="reference_year" min="2000"
                            max="2030" placeholder="{{ date('Y') }}"></div>
                </div>
                <div><label>Descrição</label>
                    <textarea name="description" rows="2" placeholder="Breve descrição..."></textarea>
                </div>
                <div><label>Tags (separadas por vírgula)</label><input type="text" name="tags"
                        placeholder="LRF, fiscal"></div>
                <div><label>Arquivo (PDF, DOCX, TXT, XLSX — máx. 20MB)</label><input type="file" name="file"
                        accept=".pdf,.docx,.txt,.xlsx"></div>
                <div><label>Ou cole o texto diretamente</label>
                    <textarea name="content_raw" rows="4" placeholder="Cole o conteúdo aqui..."></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                    <button type="button" onclick="document.getElementById('modal-upload').style.display='none'"
                        class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-gold">Adicionar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal preview chunks --}}
    <div id="previewModal"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem">
        <div
            style="background:#fff;border-radius:12px;width:100%;max-width:680px;max-height:85vh;display:flex;flex-direction:column">
            <div
                style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid var(--border)">
                <div>
                    <div
                        style="font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--ink-muted);margin-bottom:.2rem">
                        Conteúdo indexado</div>
                    <div id="modalTitle" style="font-weight:600;color:var(--ink);font-size:.95rem"></div>
                </div>
                <button onclick="closeModal()"
                    style="background:none;border:none;cursor:pointer;color:var(--ink-muted);font-size:1.2rem;padding:.25rem">✕</button>
            </div>
            <div id="modalBody" style="padding:1rem 1.25rem;overflow-y:auto;flex:1"></div>
        </div>
    </div>

    {{-- Modal edição de URL --}}
    <div id="editModal"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem">
        <div style="background:#fff;border-radius:12px;width:100%;max-width:540px;max-height:90vh;overflow-y:auto">
            <div
                style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid var(--border)">
                <div>
                    <div
                        style="font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--ink-muted);margin-bottom:.15rem">
                        Editar URL</div>
                    <div id="editModalTitle" style="font-weight:600;color:var(--ink);font-size:.9rem"></div>
                </div>
                <button onclick="closeEditModal()"
                    style="background:none;border:none;cursor:pointer;color:var(--ink-muted);font-size:1.2rem;padding:.25rem">✕</button>
            </div>
            <form id="editForm" method="POST" style="padding:1.25rem;display:grid;gap:.85rem">
                @csrf
                @method('PUT')
                <div>
                    <label>URL <span style="font-size:.72rem;color:var(--ink-muted)">(não editável)</span></label>
                    <input type="text" id="editUrlDisplay" disabled
                        style="background:var(--surface);color:var(--ink-muted)">
                </div>
                <div>
                    <label>Título</label>
                    <input type="text" name="title" id="editTitle" placeholder="ex: Site da Prefeitura">
                </div>
                <div>
                    <label>Município <span style="font-size:.72rem;color:var(--ink-muted)">(vazio = global)</span></label>
                    <select name="municipality_id" id="editMunicipality">
                        <option value="">🌐 Todos os municípios — URL global</option>
                        @foreach ($municipalities as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                    <div>
                        <label>Categoria</label>
                        <select name="category" id="editCategory">
                            <option value="geral">Geral</option>
                            <option value="noticias">Notícias</option>
                            <option value="transparencia">Transparência</option>
                            <option value="legislacao">Legislação</option>
                            <option value="governo">Governo</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    <div>
                        <label>Atualização automática</label>
                        <select name="refresh_frequency" id="editFrequency">
                            <option value="daily">Diária</option>
                            <option value="weekly">Semanal</option>
                            <option value="monthly">Mensal</option>
                            <option value="manual">Manual</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label>Descrição</label>
                    <textarea name="description" id="editDescription" rows="2" placeholder="Para que serve esta URL..."></textarea>
                </div>
                <div style="display:flex;align-items:center;gap:.5rem">
                    <input type="checkbox" name="index_subpages" id="editSubpages" value="1" style="width:auto">
                    <label for="editSubpages" style="margin:0;cursor:pointer;font-size:.84rem">Varrer subpáginas</label>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;padding-top:.25rem">
                    <button type="button" onclick="closeEditModal()" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Salvar alterações</button>
                </div>
            </form>
        </div>
    </div>

    @if ($errors->any())
        <script>
            document.getElementById("modal-upload").style.display = "flex";
        </script>
    @endif

@endsection

@push('scripts')
    <script>
        function switchTab(tab) {
            document.querySelectorAll(".kb-tab").forEach(t => t.classList.remove("active"));
            document.querySelectorAll(".tab-panel").forEach(p => p.classList.remove("active"));
            document.getElementById("tab-" + tab).classList.add("active");
            event.currentTarget.classList.add("active");
            const url = new URL(window.location);
            url.searchParams.set("tab", tab);
            history.replaceState({}, "", url);
        }

        document.getElementById("addUrlForm")?.addEventListener("submit", function() {
            const btn = document.getElementById("btnAddUrl");
            btn.disabled = true;
            btn.textContent = "Indexando...";
        });

        function previewUrl(id, title) {
            const modal = document.getElementById("previewModal");
            document.getElementById("modalTitle").textContent = title;
            document.getElementById("modalBody").innerHTML =
                "<div style=\"text-align:center;padding:2rem;color:#888\">Carregando...</div>";
            modal.style.display = "flex";
            fetch("/admin/monitored-urls/" + id + "/preview")
                .then(r => r.json())
                .then(data => {
                    let html = "<div style=\"font-size:.78rem;color:#666;margin-bottom:1rem\"><strong>" + data
                        .total_chunks + "</strong> trechos · <a href=\"" + data.url +
                        "\" target=\"_blank\" style=\"color:var(--gold)\">" + data.url + "</a></div>";
                    data.chunks.forEach(c => {
                        html +=
                            "<div style=\"border:1px solid var(--border);border-radius:7px;padding:.75rem 1rem;margin-bottom:.5rem;background:#fff\"><div style=\"display:flex;justify-content:space-between;margin-bottom:.35rem\"><span style=\"font-size:.68rem;font-weight:600;color:var(--ink-muted)\">Trecho #" +
                            (c.index + 1) + "</span><span style=\"font-size:.68rem;color:var(--ink-muted)\">" +
                            c.tokens +
                            " tokens</span></div><div style=\"font-size:.82rem;color:var(--ink-soft);line-height:1.6\">" +
                            c.preview + "</div></div>";
                    });
                    document.getElementById("modalBody").innerHTML = html;
                })
                .catch(() => {
                    document.getElementById("modalBody").innerHTML =
                        "<div style=\"color:var(--red);padding:1rem\">Erro.</div>";
                });
        }

        function closeModal() {
            document.getElementById("previewModal").style.display = "none";
        }
        document.addEventListener("click", e => {
            if (e.target === document.getElementById("previewModal")) closeModal();
        });

        // Modal de edição de URL
        function editUrl(id, title, url, municipalityId, category, frequency, subpages, description) {
            const modal = document.getElementById("editModal");
            const form = document.getElementById("editForm");

            // Definir action do form com a URL correta
            form.action = "/admin/monitored-urls/" + id;

            document.getElementById("editModalTitle").textContent = title || url;
            document.getElementById("editUrlDisplay").value = url;
            document.getElementById("editTitle").value = title === url ? "" : title;
            document.getElementById("editDescription").value = description || "";
            document.getElementById("editSubpages").checked = subpages;

            // Selects
            const munSel = document.getElementById("editMunicipality");
            const catSel = document.getElementById("editCategory");
            const freqSel = document.getElementById("editFrequency");

            munSel.value = municipalityId || "";
            catSel.value = category || "geral";
            freqSel.value = frequency || "daily";

            modal.style.display = "flex";
        }

        function closeEditModal() {
            document.getElementById("editModal").style.display = "none";
        }

        document.addEventListener("click", e => {
            if (e.target === document.getElementById("editModal")) closeEditModal();
        });
    </script>
@endpush
