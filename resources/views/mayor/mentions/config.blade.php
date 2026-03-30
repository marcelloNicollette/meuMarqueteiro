@extends('layouts.mayor')

@section('title', 'Configurar Monitoramento')
@section('topbar-title', 'Monitoramento · Palavras-chave')

@push('styles')
    <style>
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
            gap: .35rem;
            padding: .4rem .7rem;
            border-radius: 7px;
            font-size: .75rem;
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

        input,
        select {
            width: 100%;
            padding: .55rem .8rem;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: .85rem;
            color: var(--ink);
            background: var(--white);
            transition: border-color .15s;
            outline: none;
        }

        input:focus,
        select:focus {
            border-color: var(--gold);
        }

        label {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            color: var(--ink-soft);
            margin-bottom: .3rem;
        }

        .cfg-wrap {
            padding: 1.75rem 2rem;
            max-width: 720px;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .cfg-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .cfg-header h1 {
            font-family: 'Lora', serif;
            font-size: 1.35rem;
            color: var(--ink);
            margin: 0;
        }

        .kw-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
        }

        .kw-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem 1.1rem;
            border-bottom: 1px solid var(--border);
        }

        .kw-item:last-child {
            border-bottom: none;
        }

        .kw-type {
            padding: .2rem .55rem;
            border-radius: 4px;
            font-size: .7rem;
            font-weight: 600;
            background: var(--bg);
            color: var(--ink-muted);
            white-space: nowrap;
        }

        .kw-word {
            font-weight: 500;
            font-size: .88rem;
            color: var(--ink);
            flex: 1;
        }

        .kw-alert {
            font-size: .72rem;
            color: var(--ink-muted);
        }

        .add-card {
            background: var(--surface);
            border: 1.5px dashed var(--border);
            border-radius: 10px;
            padding: 1.25rem;
        }

        .add-card h3 {
            font-size: .88rem;
            font-weight: 600;
            color: var(--ink);
            margin: 0 0 1rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 160px 120px auto;
            gap: .6rem;
            align-items: end;
        }

        @media(max-width:640px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .tips-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 9px;
            padding: .85rem 1rem;
            font-size: .8rem;
            color: #1e40af;
            line-height: 1.7;
        }

        .auto-kw-btn {
            padding: .3rem .7rem;
            font-size: .75rem;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: var(--white);
            cursor: pointer;
            color: var(--ink-soft);
            transition: all .15s;
        }

        .auto-kw-btn:hover {
            border-color: var(--gold);
            color: var(--gold);
        }

        details.scan-details {
            padding: .75rem 1.1rem;
            border-top: 1px solid var(--border);
        }

        details.scan-details:first-of-type {
            border-top: none;
        }

        details.scan-details summary {
            list-style: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: .65rem;
        }

        details.scan-details summary::-webkit-details-marker {
            display: none;
        }

        .scan-url {
            display: block;
            font-size: .75rem;
            color: #1a5fa8;
            text-decoration: underline;
            word-break: break-all;
        }

        .scan-src {
            font-size: .74rem;
            color: var(--ink-muted);
            margin-top: .5rem;
        }
    </style>
@endpush

@section('content')
    <div class="cfg-wrap">

        <div class="cfg-header">
            <div>
                <h1>Palavras-chave</h1>
                <p style="font-size:.82rem;color:var(--ink-muted);margin:.2rem 0 0">Configure o que monitorar para
                    {{ $municipality->name }}</p>
            </div>
            <a href="{{ route('mayor.mentions.index') }}" class="btn-secondary" style="font-size:.8rem">
                ← Dashboard
            </a>
        </div>

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert-error">{{ session('error') }}</div>
        @endif

        {{-- Sugestões automáticas --}}
        <div class="tips-box">
            <strong>💡 Sugestões para {{ $municipality->name }}:</strong><br>
            Adicione o nome da cidade, nome do prefeito, e temas relevantes do mandato.
            Clique para adicionar rapidamente:
            <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.6rem">
                @foreach ([[$municipality->name, 'city'], ['"Prefeitura de ' . $municipality->name . '"', 'city'], ['gestão municipal', 'topic'], ['obras públicas', 'topic']] as $sugg)
                    <button type="button" class="auto-kw-btn"
                        onclick="document.getElementById('kwInput').value='{{ $sugg[0] }}'; document.getElementById('kwType').value='{{ $sugg[1] }}'">
                        + {{ $sugg[0] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Formulário de adicionar --}}
        <div class="add-card">
            <h3>+ Adicionar palavra-chave</h3>
            <form method="POST" action="{{ route('mayor.mentions.keyword.store') }}">
                @csrf
                <div class="form-row">
                    <div>
                        <label>Palavra ou frase *</label>
                        <input type="text" name="keyword" id="kwInput" required
                            placeholder='ex: "Serrinha BA" ou Prefeito João'>
                    </div>
                    <div>
                        <label>Tipo</label>
                        <select name="type" id="kwType">
                            <option value="city">Cidade</option>
                            <option value="mayor">Prefeito(a)</option>
                            <option value="secretary">Secretaria</option>
                            <option value="topic">Tema</option>
                            <option value="hashtag">Hashtag</option>
                        </select>
                    </div>
                    <div style="display:flex;flex-direction:column;justify-content:flex-end;padding-bottom:.1rem">
                        <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer">
                            <input type="checkbox" name="alert_negative" value="1" checked style="width:auto">
                            <span style="font-size:.78rem">Alertar negativo</span>
                        </label>
                    </div>
                    <button type="submit" class="btn-primary" style="align-self:flex-end">Adicionar</button>
                </div>
            </form>
        </div>

        {{-- Lista de keywords --}}
        @if ($keywords->isEmpty())
            <div
                style="text-align:center;padding:2rem;background:var(--surface);border:1px solid var(--border);border-radius:10px;color:var(--ink-muted);font-size:.85rem">
                Nenhuma palavra-chave configurada ainda.
            </div>
        @else
            @foreach ($keywords->groupBy('type') as $type => $group)
                @php
                    $typeColors = [
                        'city' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                        'mayor' => ['bg' => '#fef3c7', 'text' => '#b8902a'],
                        'secretary' => ['bg' => '#ede9fe', 'text' => '#7c3aed'],
                        'topic' => ['bg' => '#dcfce7', 'text' => '#1e7e48'],
                        'hashtag' => ['bg' => '#f3f4f6', 'text' => '#666'],
                    ][$type] ?? ['bg' => '#f3f4f6', 'text' => '#666'];
                @endphp
                <div class="kw-card">
                    <div
                        style="padding:.6rem 1.1rem;background:var(--bg);border-bottom:1px solid var(--border);font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-muted)">
                        {{ $group->first()->type_label }} ({{ $group->count() }})
                    </div>
                    @foreach ($group as $kw)
                        <div class="kw-item" style="{{ !$kw->is_active ? 'opacity:.5' : '' }}">
                            <span class="kw-type"
                                style="background:{{ $typeColors['bg'] }};color:{{ $typeColors['text'] }}">
                                {{ $kw->type_label }}
                            </span>
                            <span class="kw-word">{{ $kw->keyword }}</span>
                            @if ($kw->alert_negative)
                                <span class="kw-alert">🔔 alerta negativo</span>
                            @endif
                            <div style="display:flex;gap:.3rem">
                                <form method="POST" action="{{ route('mayor.mentions.keyword.toggle', $kw->id) }}">
                                    @csrf
                                    <button type="submit" class="btn-secondary"
                                        style="padding:.3rem .6rem;font-size:.72rem"
                                        title="{{ $kw->is_active ? 'Pausar' : 'Ativar' }}">
                                        {{ $kw->is_active ? '⏸' : '▶' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('mayor.mentions.keyword.destroy', $kw->id) }}"
                                    onsubmit="return confirm('Remover esta palavra-chave?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-danger" style="padding:.3rem .6rem">✕</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        @endif

        <div class="kw-card" id="scan-urls">
            <div
                style="padding:.6rem 1.1rem;background:var(--bg);border-bottom:1px solid var(--border);font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-muted)">
                Fontes e URLs monitoradas
            </div>
            <div style="padding:.75rem 1.1rem;font-size:.8rem;color:var(--ink-muted);line-height:1.6">
                Para cada palavra-chave ativa, o sistema consulta o RSS do Google News. Para tipos exceto "Tema", também
                consulta RSS de
                Twitter/X via instâncias públicas do Nitter.
            </div>
            @foreach ($keywords as $kw)
                @php
                    $typeColors = [
                        'city' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                        'mayor' => ['bg' => '#fef3c7', 'text' => '#b8902a'],
                        'secretary' => ['bg' => '#ede9fe', 'text' => '#7c3aed'],
                        'topic' => ['bg' => '#dcfce7', 'text' => '#1e7e48'],
                        'hashtag' => ['bg' => '#f3f4f6', 'text' => '#666'],
                    ][$kw->type] ?? ['bg' => '#f3f4f6', 'text' => '#666'];
                    $kwTargets = $scanTargets[$kw->id] ?? [];
                @endphp
                <details class="scan-details" {{ $kw->is_active ? '' : 'style=opacity:.55' }}>
                    <summary>
                        <span class="kw-type" style="background:{{ $typeColors['bg'] }};color:{{ $typeColors['text'] }}">
                            {{ $kw->type_label }}
                        </span>
                        <span class="kw-word">{{ $kw->keyword }}</span>
                        <span class="kw-alert" style="white-space:nowrap">
                            {{ $kw->is_active ? 'ativa' : 'pausada' }}
                        </span>
                    </summary>
                    @foreach ($kwTargets as $t)
                        <div class="scan-src">{{ $t['source'] }}</div>
                        <a class="scan-url" href="{{ $t['url'] }}" target="_blank"
                            rel="noopener">{{ $t['url'] }}</a>
                    @endforeach
                </details>
            @endforeach
        </div>

        {{-- Dicas de uso --}}
        <div class="tips-box">
            <strong>📌 Dicas de monitoramento eficiente:</strong><br>
            • Use <strong>aspas</strong> para frases exatas: <code>"Prefeitura de Serrinha"</code><br>
            • Nome da cidade + estado melhora a precisão: <code>Serrinha BA</code><br>
            • Hashtags com #: <code>#Serrinha</code> ou <code>#PrefeituraSerrinha</code><br>
            • Para o prefeito, use o nome completo e variações<br>
            • O monitoramento roda automaticamente a cada 4 horas
        </div>

    </div>
@endsection
