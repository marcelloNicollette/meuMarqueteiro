@extends('layouts.admin')

@section('title', 'URLs Monitoradas')
@section('topbar-title', 'Fontes de Conhecimento — URLs')

@push('styles')
    <style>
        /* ── Botões ───────────────────────────────────────────── */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
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
            font-family: 'DM Sans', sans-serif;
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

        .btn-danger {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .45rem .9rem;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: .78rem;
            font-weight: 500;
            background: none;
            color: var(--red);
            border: 1.5px solid var(--red-bg);
            cursor: pointer;
            text-decoration: none;
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
        }

        .alert-error {
            background: var(--red-bg);
            color: var(--red);
            border: 1px solid #f5c6c6;
            border-radius: 8px;
            padding: .75rem 1rem;
            font-size: .84rem;
        }

        .alert-warning {
            background: #fef3c7;
            color: #b8902a;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: .75rem 1rem;
            font-size: .84rem;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: .5rem .75rem;
            border: 1.5px solid var(--border);
            border-radius: 7px;
            font-family: 'DM Sans', sans-serif;
            font-size: .84rem;
            color: var(--ink);
            background: var(--white);
            transition: border-color .15s;
            outline: none;
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

        /* ── Layout ───────────────────────────────────── */
        .urls-wrap {
            padding: 1.75rem 2rem;
            max-width: 1080px;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* ── Header ──────────────────────────────────── */
        .urls-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .urls-header h1 {
            font-family: 'Lora', serif;
            font-size: 1.35rem;
            color: var(--ink);
            margin: 0;
        }

        .urls-header p {
            font-size: .82rem;
            color: var(--ink-muted);
            margin: .2rem 0 0;
            max-width: 500px;
            line-height: 1.5;
        }

        /* ── KPIs ────────────────────────────────────── */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: .75rem;
        }

        @media(max-width:700px) {
            .kpi-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .kpi-mini {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: .85rem 1rem;
        }

        .kpi-mini-val {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1;
        }

        .kpi-mini-val.green {
            color: var(--green);
        }

        .kpi-mini-val.gold {
            color: var(--gold);
        }

        .kpi-mini-label {
            font-size: .72rem;
            color: var(--ink-muted);
            margin-top: .2rem;
        }

        /* ── Form de adicionar URL ───────────────────── */
        .add-url-card {
            background: var(--surface);
            border: 1.5px dashed var(--border);
            border-radius: 10px;
            padding: 1.25rem;
        }

        .add-url-card h3 {
            font-size: .88rem;
            font-weight: 600;
            color: var(--ink);
            margin: 0 0 1rem;
        }

        .add-url-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: .6rem;
            align-items: end;
        }

        @media(max-width:640px) {
            .add-url-grid {
                grid-template-columns: 1fr;
            }
        }

        .add-url-extras {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: .6rem;
            align-items: end;
            margin-top: .6rem;
        }

        @media(max-width:640px) {
            .add-url-extras {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* ── Lista de URLs ───────────────────────────── */
        .url-list {
            display: flex;
            flex-direction: column;
            gap: .6rem;
        }

        .url-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem 1.1rem;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: .85rem;
            align-items: start;
        }

        .url-favicon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: var(--bg);
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

        .url-main-title {
            font-weight: 600;
            font-size: .88rem;
            color: var(--ink);
            margin-bottom: .15rem;
        }

        .url-link {
            font-size: .75rem;
            color: var(--ink-muted);
            word-break: break-all;
        }

        .url-meta {
            display: flex;
            gap: .6rem;
            flex-wrap: wrap;
            margin-top: .35rem;
            align-items: center;
        }

        .url-badge {
            padding: .15rem .5rem;
            border-radius: 4px;
            font-size: .68rem;
            font-weight: 600;
        }

        .url-chunks {
            font-size: .72rem;
            color: var(--ink-muted);
        }

        .url-actions {
            display: flex;
            gap: .4rem;
            align-items: center;
            flex-shrink: 0;
        }

        .url-description {
            font-size: .78rem;
            color: var(--ink-soft);
            margin-top: .2rem;
        }

        /* ── Empty state ─────────────────────────────── */
        .empty-urls {
            text-align: center;
            padding: 2.5rem 1.5rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        .empty-urls p {
            font-size: .84rem;
            color: var(--ink-muted);
            margin-bottom: 1rem;
        }

        /* ── Info box ────────────────────────────────── */
        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 9px;
            padding: .85rem 1rem;
            font-size: .8rem;
            color: #1e40af;
            line-height: 1.6;
        }

        .info-box strong {
            font-weight: 600;
        }
    </style>
@endpush

@section('content')
    <div class="urls-wrap">

        {{-- Header --}}
        <div class="urls-header">
            <div>
                <h1>URLs Monitoradas</h1>
                <p>Adicione sites, portais e notícias. O assistente usa automaticamente o conteúdo dessas páginas para
                    responder com mais precisão.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="btn-secondary" style="font-size:.8rem;flex-shrink:0">
                <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px">
                    <path
                        d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z" />
                </svg>
                ← Dashboard
            </a>
        </div>

        {{-- Filtro por município --}}
        <form method="GET" style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap">
            <select name="municipality_id" onchange="this.form.submit()" style="max-width:280px">
                <option value="">Todos os municípios</option>
                @foreach ($municipalities as $m)
                    <option value="{{ $m->id }}" {{ request('municipality_id') == $m->id ? 'selected' : '' }}>
                        {{ $m->name }} — {{ $m->state }}
                    </option>
                @endforeach
            </select>
            @if (request('municipality_id'))
                <a href="{{ route('admin.monitored-urls.index') }}" style="font-size:.8rem;color:var(--ink-muted)">Limpar
                    filtro</a>
            @endif
        </form>

        {{-- Alertas --}}
        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert-error">{{ session('error') }}</div>
        @endif
        @if (session('warning'))
            <div class="alert-warning">{{ session('warning') }}</div>
        @endif

        {{-- KPIs --}}
        <div class="kpi-row">
            <div class="kpi-mini">
                <div class="kpi-mini-val">{{ $stats['total'] }}</div>
                <div class="kpi-mini-label">URLs cadastradas</div>
            </div>
            <div class="kpi-mini">
                <div class="kpi-mini-val green">{{ $stats['indexadas'] }}</div>
                <div class="kpi-mini-label">Indexadas com sucesso</div>
            </div>
            <div class="kpi-mini">
                <div class="kpi-mini-val gold">{{ number_format($stats['chunks']) }}</div>
                <div class="kpi-mini-label">Trechos no assistente</div>
            </div>
            <div class="kpi-mini">
                <div class="kpi-mini-val {{ $stats['falhas'] > 0 ? 'red' : '' }}"
                    style="{{ $stats['falhas'] > 0 ? 'color:var(--red)' : '' }}">{{ $stats['falhas'] }}</div>
                <div class="kpi-mini-label">Com erro</div>
            </div>
        </div>

        {{-- Info --}}
        <div class="info-box">
            <strong>Como funciona:</strong> cada URL que você adiciona é visitada automaticamente, o texto é extraído e
            salvo como conhecimento do assistente. Quando você pergunta algo no chat, ele busca automaticamente nos
            conteúdos dessas páginas. URLs com frequência "Diária" são re-indexadas todo dia pelo sistema.
        </div>

        {{-- Formulário de adicionar URL --}}
        <div class="add-url-card">
            <h3>+ Adicionar nova URL</h3>
            <form method="POST" action="{{ route('admin.monitored-urls.store') }}">
                @csrf
                <div style="margin-bottom:.6rem">
                    <label style="font-size:.75rem;color:var(--ink-muted);display:block;margin-bottom:.25rem">Município
                        *</label>
                    <select name="municipality_id" required>
                        <option value="">Selecione o município</option>
                        @foreach ($municipalities as $m)
                            <option value="{{ $m->id }}" {{ old('municipality_id') == $m->id ? 'selected' : '' }}>
                                {{ $m->name }} — {{ $m->state }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="add-url-grid">
                    <div>
                        <label style="font-size:.75rem;color:var(--ink-muted);display:block;margin-bottom:.25rem">URL
                            *</label>
                        <input type="url" name="url" required placeholder="https://serrinha.ba.gov.br/noticias"
                            value="{{ old('url') }}">
                    </div>
                    <button type="submit" class="btn-primary" id="btnAdd" style="white-space:nowrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            style="width:14px;height:14px">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                        Adicionar e indexar
                    </button>
                </div>
                <div class="add-url-extras">
                    <div>
                        <label style="font-size:.75rem;color:var(--ink-muted);display:block;margin-bottom:.25rem">Título
                            (opcional)</label>
                        <input type="text" name="title" placeholder="ex: Site da Prefeitura"
                            value="{{ old('title') }}">
                    </div>
                    <div>
                        <label
                            style="font-size:.75rem;color:var(--ink-muted);display:block;margin-bottom:.25rem">Categoria</label>
                        <select name="category">
                            <option value="geral">Geral</option>
                            <option value="noticias">Notícias</option>
                            <option value="transparencia">Transparência</option>
                            <option value="legislacao">Legislação</option>
                            <option value="governo">Governo</option>
                        </select>
                    </div>
                    <div>
                        <label
                            style="font-size:.75rem;color:var(--ink-muted);display:block;margin-bottom:.25rem">Atualização
                            automática</label>
                        <select name="refresh_frequency">
                            <option value="daily">Diária</option>
                            <option value="weekly">Semanal</option>
                            <option value="monthly">Mensal</option>
                            <option value="manual">Manual</option>
                        </select>
                    </div>
                    <div style="display:flex;align-items:center;gap:.4rem;padding-bottom:.1rem">
                        <input type="checkbox" name="index_subpages" id="subpages" value="1" style="width:auto">
                        <label for="subpages"
                            style="font-size:.78rem;color:var(--ink-soft);cursor:pointer;white-space:nowrap">Varrer
                            subpáginas</label>
                    </div>
                </div>
            </form>
        </div>

        {{-- Lista de URLs --}}
        @if ($urls->isEmpty())
            <div class="empty-urls">
                <p>Nenhuma URL cadastrada ainda.<br>Adicione sites e portais para que o assistente acesse essas informações.
                </p>
                <div style="font-size:.78rem;color:var(--ink-muted)">
                    Exemplos: portal da prefeitura, portal da transparência, diário oficial, site da câmara...
                </div>
            </div>
        @else
            <div class="url-list">
                @foreach ($urls as $url)
                    @php
                        $statusColor = $url->status_color;
                        $favicon =
                            'https://www.google.com/s2/favicons?domain=' .
                            parse_url($url->url, PHP_URL_HOST) .
                            '&sz=32';
                    @endphp
                    <div class="url-card" style="{{ !$url->is_active ? 'opacity:.55' : '' }}">
                        {{-- Favicon --}}
                        <div class="url-favicon">
                            <img src="{{ $favicon }}" alt=""
                                onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
                                style="display:block">
                            <span style="display:none;font-size:14px">🌐</span>
                        </div>

                        {{-- Info principal --}}
                        <div>
                            <div class="url-main-title">{{ $url->display_title }}</div>
                            <div class="url-link">
                                <a href="{{ $url->url }}" target="_blank" rel="noopener"
                                    style="color:var(--ink-muted);text-decoration:none">
                                    {{ $url->url }}
                                </a>
                            </div>
                            @if ($url->municipality)
                                <div style="font-size:.72rem;color:var(--gold);margin-top:.1rem">
                                    {{ $url->municipality->name }}</div>
                            @endif
                            @if ($url->description)
                                <div class="url-description">{{ $url->description }}</div>
                            @endif
                            <div class="url-meta">
                                <span class="url-badge"
                                    style="background:{{ $statusColor['bg'] }};color:{{ $statusColor['text'] }}">
                                    {{ $url->status_label }}
                                </span>
                                <span class="url-badge" style="background:var(--bg);color:var(--ink-muted)">
                                    {{ $url->category_label }}
                                </span>
                                @if ($url->chunks_count)
                                    <span class="url-chunks">{{ $url->chunks_count }} trechos indexados</span>
                                @endif
                                @if ($url->last_indexed_at)
                                    <span class="url-chunks">Atualizado
                                        {{ $url->last_indexed_at->diffForHumans() }}</span>
                                @endif
                                @if ($url->fetch_error)
                                    <span style="font-size:.72rem;color:var(--red)" title="{{ $url->fetch_error }}">⚠
                                        {{ Str::limit($url->fetch_error, 60) }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Ações --}}
                        <div class="url-actions">
                            {{-- Ver chunks indexados --}}
                            @if ($url->chunks_count > 0)
                                <button type="button" class="btn-secondary" style="font-size:.75rem;padding:.4rem .7rem"
                                    title="Ver conteúdo indexado"
                                    onclick="previewUrl({{ $url->id }}, '{{ addslashes($url->display_title) }}')">
                                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px">
                                        <path
                                            d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                                    </svg>
                                </button>
                            @endif

                            {{-- Re-indexar --}}
                            <form method="POST" action="{{ route('admin.monitored-urls.reindex', $url->id) }}">
                                @csrf
                                <button type="submit" class="btn-secondary" style="font-size:.75rem;padding:.4rem .7rem"
                                    title="Re-indexar agora">
                                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px">
                                        <path
                                            d="M17.65 6.35A7.958 7.958 0 0012 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0112 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z" />
                                    </svg>
                                </button>
                            </form>

                            {{-- Ativar/Pausar --}}
                            <form method="POST" action="{{ route('admin.monitored-urls.toggle', $url->id) }}">
                                @csrf
                                <button type="submit" class="btn-secondary" style="font-size:.75rem;padding:.4rem .7rem"
                                    title="{{ $url->is_active ? 'Pausar' : 'Ativar' }}">
                                    @if ($url->is_active)
                                        <svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px">
                                            <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                                        </svg>
                                    @else
                                        <svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px">
                                            <path d="M8 5v14l11-7z" />
                                        </svg>
                                    @endif
                                </button>
                            </form>

                            {{-- Remover --}}
                            <form method="POST" action="{{ route('admin.monitored-urls.destroy', $url->id) }}"
                                onsubmit="return confirm('Remover esta URL e todos os seus embeddings?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger" style="padding:.4rem .7rem" title="Remover">
                                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px">
                                        <path
                                            d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
@endsection

@push('scripts')
    <script>
        // Modal de preview de chunks
        function previewUrl(id, title) {
            const modal = document.getElementById('previewModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');

            modalTitle.textContent = title;
            modalBody.innerHTML = '<div style="text-align:center;padding:2rem;color:#888">Carregando...</div>';
            modal.style.display = 'flex';

            fetch(`/admin/monitored-urls/${id}/preview`)
                .then(r => r.json())
                .then(data => {
                    let html = `<div style="font-size:.78rem;color:#666;margin-bottom:1rem">
                <strong>${data.total_chunks}</strong> trechos indexados · <a href="${data.url}" target="_blank" style="color:#b8902a">${data.url}</a>
            </div>`;

                    data.chunks.forEach(c => {
                        const source = c.source !== data.url ?
                            `<div style="font-size:.68rem;color:#b8902a;margin-bottom:.3rem">📄 ${c.source}</div>` :
                            '';
                        html += `<div style="border:1px solid #e5e1da;border-radius:7px;padding:.75rem 1rem;margin-bottom:.5rem;background:#fff">
                    <div style="display:flex;justify-content:space-between;margin-bottom:.35rem">
                        <span style="font-size:.68rem;font-weight:600;color:#80869a;text-transform:uppercase;letter-spacing:.06em">Trecho #${c.index + 1}</span>
                        <span style="font-size:.68rem;color:#80869a">${c.tokens} tokens</span>
                    </div>
                    ${source}
                    <div style="font-size:.82rem;color:#3e424f;line-height:1.6">${c.preview}</div>
                </div>`;
                    });

                    modalBody.innerHTML = html;
                })
                .catch(() => {
                    modalBody.innerHTML = '<div style="color:#b52b2b;padding:1rem">Erro ao carregar preview.</div>';
                });
        }

        function closeModal() {
            document.getElementById('previewModal').style.display = 'none';
        }

        // Fechar ao clicar fora
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('previewModal');
            if (e.target === modal) closeModal();
        });
    </script>

    {{-- Modal de preview --}}
    <div id="previewModal"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem">
        <div
            style="background:#fff;border-radius:12px;width:100%;max-width:680px;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.2)">
            <div
                style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid #e5e1da">
                <div>
                    <div
                        style="font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:#80869a;margin-bottom:.2rem">
                        Conteúdo indexado</div>
                    <div id="modalTitle" style="font-weight:600;color:#111318;font-size:.95rem"></div>
                </div>
                <button onclick="closeModal()"
                    style="background:none;border:none;cursor:pointer;color:#80869a;font-size:1.2rem;line-height:1;padding:.25rem">✕</button>
            </div>
            <div id="modalBody" style="padding:1rem 1.25rem;overflow-y:auto;flex:1"></div>
        </div>
    </div>
    <script>
        // Feedback de loading ao adicionar URL (pode demorar 5-15s)
        document.querySelector('form[action*="urls.store"]')?.addEventListener('submit', function() {
            const btn = document.getElementById('btnAdd');
            btn.disabled = true;
            btn.innerHTML =
                '<svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px;animation:spin 1s linear infinite"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/></svg> Indexando...';
        });
    </script>
    <style>
        @keyframes spin {
            from {
                transform: rotate(0deg)
            }

            to {
                transform: rotate(360deg)
            }
        }
    </style>
@endpush
