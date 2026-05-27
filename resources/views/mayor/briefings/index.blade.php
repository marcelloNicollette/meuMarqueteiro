@extends('layouts.mayor')
@section('title', 'Pra hoje!')
@section('topbar-title', 'Briefing Matinal')

@push('styles')
    <style>
        .briefings-wrap {
            max-width: 780px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        /* Card do dia — destaque principal */
        .today-card {
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .08);
        }

        .today-card-header {
            background: linear-gradient(135deg, var(--ink) 0%, #1e2740 100%);
            padding: 1.5rem 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .today-label {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: .3rem;
        }

        .today-date {
            font-family: 'Lora', serif;
            font-size: 1.1rem;
            color: #fff;
        }

        .today-badge {
            padding: .3rem .75rem;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 700;
            background: var(--gold);
            color: var(--ink);
        }

        .today-card-body {
            background: var(--white);
            padding: 1.5rem 1.75rem;
            border: 1px solid var(--border);
            border-top: none;
        }

        .today-preview {
            font-size: .9rem;
            line-height: 1.75;
            color: var(--ink-soft);
            margin-bottom: 1.1rem;
        }

        .today-actions {
            display: flex;
            gap: .6rem;
        }

        /* Card vazio — sem briefing hoje */
        .empty-today {
            background: var(--white);
            border: 2px dashed var(--border);
            border-radius: 16px;
            padding: 2.5rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        .empty-today-icon {
            font-size: 2.5rem;
            margin-bottom: .75rem;
        }

        .empty-today h3 {
            font-family: 'Lora', serif;
            font-size: 1.1rem;
            color: var(--ink);
            margin-bottom: .4rem;
        }

        .empty-today p {
            font-size: .84rem;
            color: var(--ink-muted);
            margin-bottom: 1.25rem;
            max-width: 360px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Botão gerar */
        .btn-gerar {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .7rem 1.4rem;
            border-radius: 10px;
            background: var(--ink);
            color: white;
            font-family: "Open Sans", sans-serif;
            font-size: .88rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background .15s;
        }

        .btn-gerar:hover {
            background: #1e2230;
        }

        .btn-gerar:disabled {
            background: var(--border);
            cursor: not-allowed;
        }

        .btn-gerar svg {
            width: 16px;
            height: 16px;
        }

        /* Botão secundário */
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .55rem 1.1rem;
            border-radius: 8px;
            font-family: "Open Sans", sans-serif;
            font-size: .82rem;
            font-weight: 500;
            border: 1.5px solid var(--border);
            background: var(--white);
            color: var(--ink-soft);
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
        }

        .btn-secondary:hover {
            border-color: var(--ink);
            color: var(--ink);
        }

        .btn-secondary svg {
            width: 14px;
            height: 14px;
        }

        /* Histórico */
        .history-section h2 {
            font-family: 'Lora', serif;
            font-size: .95rem;
            color: var(--ink);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .history-list {
            display: flex;
            flex-direction: column;
            gap: .6rem;
        }

        .history-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: .9rem 1.1rem;
            text-decoration: none;
            transition: all .2s;
        }

        .history-item:hover {
            border-color: var(--gold);
            box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
            transform: translateX(2px);
        }

        .history-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .history-dot.unread {
            background: var(--gold);
        }

        .history-dot.read {
            background: var(--border);
        }

        .history-item-date {
            font-size: .85rem;
            font-weight: 500;
            color: var(--ink);
            min-width: 160px;
        }

        .history-item-preview {
            font-size: .8rem;
            color: var(--ink-muted);
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .history-item-status {
            font-size: .7rem;
            font-weight: 600;
            color: var(--ink-muted);
            white-space: nowrap;
        }

        .history-item-status.new {
            color: var(--gold);
        }

        .pra-hoje-settings {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.25rem 1.35rem;
            margin-bottom: 1.5rem;
        }

        .pra-hoje-settings p {
            font-size: .84rem;
            color: var(--ink-muted);
            line-height: 1.65;
            margin-bottom: 1rem;
        }

        .pra-hoje-settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: .85rem;
            margin-bottom: 1rem;
        }

        .pra-hoje-settings label {
            display: block;
            font-size: .76rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--ink-muted);
            margin-bottom: .4rem;
        }

        .pra-hoje-settings input[type="time"] {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: .7rem .8rem;
            font-size: .9rem;
            color: var(--ink);
            background: var(--white);
        }

        .pra-hoje-toggle {
            display: flex;
            align-items: center;
            gap: .55rem;
            font-size: .86rem;
            color: var(--ink-soft);
            text-transform: none !important;
            letter-spacing: normal !important;
            font-weight: 500 !important;
            margin-bottom: 0 !important;
        }

        .pra-hoje-toggle input {
            width: 16px;
            height: 16px;
        }

        /* Spinner */
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, .3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        /* Toast */
        .gen-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: .7rem 1.2rem;
            border-radius: 10px;
            font-size: .84rem;
            font-weight: 500;
            z-index: 9999;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .15);
            animation: toastIn .2s ease;
        }

        .gen-toast.ok {
            background: #1e7e48;
            color: white;
        }

        .gen-toast.err {
            background: #b52b2b;
            color: white;
        }

        @keyframes toastIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 600px) {
            .history-item-preview {
                display: none;
            }

            .today-actions {
                flex-direction: column;
            }
        }
    </style>
@endpush

@section('content')
    <div class="briefings-wrap">

        {{-- Card do dia --}}
        @if ($todayBriefing)
            <div class="today-card">
                <div class="today-card-header">
                    <div>
                        <div class="today-label">Briefing de hoje</div>
                        <div class="today-date">
                            {{ $todayBriefing->date->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                        </div>
                    </div>
                    @if (!$todayBriefing->read_at)
                        <div class="today-badge">NOVO</div>
                    @endif
                </div>
                <div class="today-card-body">
                    <div class="today-preview">
                        {{ Str::limit($todayBriefing->opening_text ?: strip_tags($todayBriefing->content), 280) }}
                    </div>
                    <div class="today-actions">
                        <a href="{{ route('pra-hoje.show', $todayBriefing) }}" class="btn-gerar">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                            </svg>
                            Ler briefing completo
                        </a>
                        <a href="{{ route('chat.index') }}" class="btn-secondary">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z" />
                            </svg>
                            Perguntar ao assistente
                        </a>
                        <button type="button" class="btn-secondary" onclick="gerarAgora(true)">
                            Atualizar briefing
                        </button>
                    </div>
                </div>
            </div>
        @else
            {{-- Sem briefing hoje → botão para gerar sob demanda --}}
            <div class="empty-today">
                <div class="empty-today-icon">☀️</div>
                <h3>Nenhuma sugestão de ações para hoje.</h3>
                <p>Gerado automaticamente todos os dias às 6h30.<br>
                    Clique no botão para ver as sugestões de prioridades para hoje.
                </p>
                <button class="btn-gerar" id="btnGerar" onclick="gerarAgora()">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z" />
                    </svg>
                    Gerar Prioridades do Dia
                </button>
            </div>
        @endif

        <div class="pra-hoje-settings">
            <h2>Configurações do Pra hoje</h2>
            <p>As preferências são individuais. A notificação na plataforma continua ativa; o e-mail é opcional e usa a
                mesma abertura do dia com um link direto para abrir o módulo.</p>
            <form method="POST" action="{{ route('pra-hoje.preferences') }}">
                @csrf
                <div class="pra-hoje-settings-grid">
                    <div>
                        <label for="delivery_time">Horário de entrega</label>
                        <input id="delivery_time" type="time" name="delivery_time"
                            value="{{ $praHojePreferences['delivery_time'] ?? '07:30' }}" required>
                    </div>
                    <div style="display:flex;flex-direction:column;justify-content:flex-end;gap:.75rem">
                        <label class="pra-hoje-toggle">
                            <input type="hidden" name="enabled" value="0">
                            <input type="checkbox" name="enabled" value="1" @checked(($praHojePreferences['enabled'] ?? true) === true)>
                            Ativar o Pra hoje para este usuário
                        </label>
                        <label class="pra-hoje-toggle">
                            <input type="hidden" name="email_enabled" value="0">
                            <input type="checkbox" name="email_enabled" value="1" @checked(($praHojePreferences['email_enabled'] ?? false) === true)>
                            Receber a abertura também por e-mail
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn-secondary">Salvar preferências</button>
            </form>
        </div>

        <div class="history-section">
            <h2>Pra Hoje</h2>
            <p style="text-align:center;color:var(--ink-muted);font-size:.88rem;padding:1.1rem 0 0">
                O briefing do dia substitui o anterior. Esta tela mostra apenas a versão atual do seu resumo priorizado.
            </p>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;

        async function gerarAgora(refresh = false) {
            const btn = document.getElementById('btnGerar');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<div class="spinner"></div> Gerando seu briefing...';
            }

            try {
                const res = await fetch(
                    `{{ route('pra-hoje.generate') }}${refresh ? '?refresh=1' : ''}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json'
                        }
                    });
                const data = await res.json();

                if (data.ok) {
                    showToast('✓ Briefing gerado! Abrindo...', 'ok');
                    setTimeout(() => window.location.href = data.redirect, 800);
                } else {
                    throw new Error(data.error || 'Erro desconhecido');
                }
            } catch (e) {
                showToast('Não foi possível gerar o briefing: ' + e.message, 'err');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML =
                        '<svg viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg> Tentar novamente';
                }
            }
        }

        function showToast(msg, type) {
            const t = document.createElement('div');
            t.className = 'gen-toast ' + type;
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 3500);
        }
    </script>
@endpush
