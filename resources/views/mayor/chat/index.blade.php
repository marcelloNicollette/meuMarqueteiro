@extends('layouts.mayor')

@section('title', 'Assistente')
@section('topbar-title', 'Assistente — ' . auth()->user()->name)

@push('styles')
    <style>
        /* ── Layout do chat ───────────────────────────────────── */
        .chat-layout {
            display: flex;
            height: calc(100vh - var(--nav-h));
            overflow: hidden;
        }

        /* ── Histórico de conversas (sidebar) ─────────────────── */
        .conv-list {
            width: 280px;
            flex-shrink: 0;
            background: var(--white);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .conv-list-header {
            padding: 1.1rem 1.2rem .8rem;
            border-bottom: 1px solid var(--border-lt);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .conv-list-header h3 {
            font-size: .78rem;
            font-weight: 500;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--ink-muted);
        }

        .btn-new-conv {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            background: var(--ink);
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .15s;
        }

        .btn-new-conv:hover {
            background: #1e2230;
        }

        .btn-new-conv svg {
            width: 15px;
            height: 15px;
        }

        .conv-scroll {
            overflow-y: auto;
            flex: 1;
            padding: .5rem;
        }

        .conv-item {
            display: block;
            padding: .75rem .85rem;
            border-radius: 8px;
            text-decoration: none;
            transition: background .1s;
            cursor: pointer;
            border: 1px solid transparent;
            margin-bottom: .2rem;
        }

        .conv-item:hover {
            background: var(--surface);
        }

        .conv-item.active {
            background: var(--surface);
            border-color: var(--border);
        }

        .conv-item-title {
            font-size: .84rem;
            font-weight: 500;
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: .25rem;
        }

        .conv-item-meta {
            font-size: .72rem;
            color: var(--ink-muted);
            display: flex;
            justify-content: space-between;
        }

        /* ── Área principal do chat ───────────────────────────── */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: var(--surface);
        }

        /* ── Mensagens ────────────────────────────────────────── */
        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            scroll-behavior: smooth;
        }

        /* Estado vazio */
        .chat-empty {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }

        .chat-empty-icon {
            width: 56px;
            height: 56px;
            background: var(--white);

            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
        }

        .chat-empty-icon svg {
            width: 26px;
            height: 26px;
            color: var(--gold);
        }

        .chat-empty h2 {
            font-family: 'Lora', serif;
            font-size: 1.3rem;
            color: var(--ink);
            margin-bottom: .6rem;
        }

        .chat-empty p {
            font-size: .87rem;
            color: var(--ink-muted);
            max-width: 360px;
            line-height: 1.7;
            margin-bottom: 1.75rem;
        }

        /* Sugestões rápidas */
        .suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            justify-content: center;
            max-width: 560px;
        }

        .suggestion-chip {
            padding: .55rem 1rem;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            font-size: .82rem;
            color: var(--ink-soft);
            cursor: pointer;
            transition: all .15s;
            text-align: left;
        }

        .suggestion-chip:hover {
            border-color: var(--gold);
            color: var(--ink);
            background: var(--white);
            box-shadow: 0 2px 8px rgba(184, 144, 42, .15);
        }

        /* Bolhas de mensagem */
        .message {
            display: flex;
            gap: .85rem;
            max-width: 780px;
        }

        .message.user {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .message.assistant {
            align-self: flex-start;
        }

        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .78rem;
            font-weight: 600;
        }

        .message.user .message-avatar {
            background: var(--gold);
            color: var(--ink);
        }

        .message.assistant .message-avatar {
            background: var(--ink);
            color: #fff;
            font-size: .6rem;
        }

        .message-bubble {
            padding: .85rem 1.1rem;
            border-radius: 12px;
            font-size: .9rem;
            line-height: 1.7;
            max-width: 560px;
        }

        .message.user .message-bubble {
            background: var(--ink);
            color: #fff;
            border-radius: 12px 12px 4px 12px;
        }

        .message.assistant .message-bubble {
            background: var(--white);
            color: var(--ink-soft);
            border: 1px solid var(--border);
            border-radius: 12px 12px 12px 4px;
        }

        .message-meta {
            margin-top: .35rem;
            font-size: .7rem;
            color: var(--ink-muted);
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .message.user .message-meta {
            justify-content: flex-end;
        }

        /* Fontes RAG */
        .rag-sources {
            margin-top: .75rem;
            padding-top: .75rem;
            border-top: 1px solid var(--border-lt);
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }

        .rag-source-tag {
            padding: .2rem .6rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: .68rem;
            color: var(--ink-muted);
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        .rag-source-tag svg {
            width: 10px;
            height: 10px;
        }

        /* Feedback */
        .message-feedback {
            display: flex;
            gap: .3rem;
            margin-top: .3rem;
        }

        .feedback-btn {
            width: 24px;
            height: 24px;
            border-radius: 5px;
            background: none;
            border: 1px solid var(--border);
            cursor: pointer;
            color: var(--ink-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .15s;
        }

        .feedback-btn:hover {
            border-color: var(--gold);
            color: var(--gold);
            background: var(--gold-bg);
        }

        .feedback-btn svg {
            width: 12px;
            height: 12px;
        }

        /* Typing indicator */
        .typing-indicator {
            display: flex;
            gap: .85rem;
            align-items: flex-end;
            align-self: flex-start;
        }

        .typing-bubble {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px 12px 12px 4px;
            padding: .85rem 1.1rem;
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .typing-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--ink-muted);
            animation: typingBounce 1.2s ease-in-out infinite;
        }

        .typing-dot:nth-child(2) {
            animation-delay: .2s;
        }

        .typing-dot:nth-child(3) {
            animation-delay: .4s;
        }

        @keyframes typingBounce {

            0%,
            60%,
            100% {
                transform: translateY(0);
                opacity: .4;
            }

            30% {
                transform: translateY(-5px);
                opacity: 1;
            }
        }

        /* ── Input area ───────────────────────────────────────── */
        .input-area {
            padding: 1rem 2rem 1.25rem;
            background: var(--surface);
            border-top: 1px solid var(--border);
        }

        .input-box {
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 14px;
            display: flex;
            align-items: flex-end;
            gap: .75rem;
            padding: .75rem 1rem;
            transition: border-color .2s, box-shadow .2s;
        }

        .input-box:focus-within {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(184, 144, 42, .1);
        }

        #msg-input {
            flex: 1;
            border: none;
            outline: none;
            resize: none;
            font-family: "Open Sans", sans-serif;
            font-size: .93rem;
            color: var(--ink);
            background: none;
            max-height: 140px;
            min-height: 24px;
            line-height: 1.6;
        }

        #msg-input::placeholder {
            color: var(--ink-muted);
        }

        .input-actions {
            display: flex;
            gap: .4rem;
            align-items: flex-end;
        }

        .btn-voice {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            background: none;
            border: 1.5px solid var(--border);
            color: var(--ink-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .15s;
            flex-shrink: 0;
        }

        .btn-voice:hover {
            border-color: var(--ink);
            color: var(--ink);
        }

        .btn-voice svg {
            width: 16px;
            height: 16px;
        }

        .btn-send {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            background: var(--ink);
            border: none;
            color: var(--white);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .15s;
            flex-shrink: 0;
        }

        .btn-send:hover {
            background: #1e2230;
        }

        .btn-send:disabled {
            background: var(--border);
            cursor: not-allowed;
        }

        .btn-send svg {
            width: 16px;
            height: 16px;
        }

        .input-hint {
            text-align: center;
            font-size: .72rem;
            color: var(--ink-muted);
            margin-top: .6rem;
        }

        /* ── Mensagem com markdown simples ───────────────────── */
        .message-bubble strong {
            font-weight: 600;
        }

        .message-bubble em {
            font-style: italic;
        }

        .message-bubble ul {
            padding-left: 1.2rem;
            margin: .4rem 0;
        }

        .message-bubble li {
            margin-bottom: .25rem;
        }

        .message-bubble h3,
        .message-bubble h4 {
            font-family: 'Lora', serif;
            font-weight: 500;
            margin: .6rem 0 .3rem;
        }

        @media (max-width: 768px) {
            .conv-list {
                display: none;
            }

            .messages-area {
                padding: 1rem;
            }

            .input-area {
                padding: .75rem 1rem 1rem;
            }
        }
    </style>
@endpush

@section('content')

    <div class="chat-layout">

        {{-- ── Lista de conversas ───────────────────────────────── --}}
        <div class="conv-list">
            <div class="conv-list-header">
                <h3>Conversas</h3>
                <button class="btn-new-conv" id="btnNewConv" title="Nova conversa">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                    </svg>
                </button>
            </div>
            <div class="conv-scroll">
                @forelse($conversations as $conv)
                    <a href="{{ route('mayor.chat.show', $conv) }}"
                        class="conv-item {{ isset($activeConversation) && $activeConversation->id === $conv->id ? 'active' : '' }}"
                        data-id="{{ $conv->id }}">
                        <div class="conv-item-title">{{ 'Conversa - ' . $conv->created_at->format('d/m/Y H:i') }}</div>
                        <div class="conv-item-meta">
                            <span>{{ $conv->messages()->count() }} msgs</span>
                            <span style="display:flex;align-items:center;gap:.4rem">
                                {{ $conv->last_message_at?->diffForHumans() ?? 'agora' }}
                                <button type="button" title="Excluir conversa"
                                    onclick="deleteConv(event, {{ $conv->id }})"
                                    style="border:none;background:none;cursor:pointer;color:#dc2626;padding:0">
                                    <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
                                        <path d="M16 9v10H8V9h8m-1.5-6h-5l-1 1H5v2h14V4h-4.5l-1-1z" />
                                    </svg>
                                </button>
                            </span>
                        </div>
                    </a>
                @empty
                    <p style="padding:1rem .85rem;font-size:.82rem;color:var(--ink-muted)">
                        Nenhuma conversa ainda.
                    </p>
                @endforelse
            </div>
        </div>

        {{-- ── Chat principal ───────────────────────────────────── --}}
        <div class="chat-main">

            {{-- Mensagens --}}
            <div class="messages-area" id="messagesArea">

                @if (!isset($activeConversation) || $activeConversation->messages->isEmpty())
                    {{-- Estado vazio --}}
                    <div class="chat-empty" id="chatEmpty">
                        <div class="chat-empty-icon">
                            <img width="100%" src="/images/icone-robo-redondo.png" alt="">
                        </div>
                        <h2>Olá, {{ explode(' ', auth()->user()->name)[0] }}.</h2>
                        <p>
                            Sou seu assessor digital. Pergunte sobre dados do município,
                            peça ajuda com comunicação, ou consulte programas federais disponíveis.
                        </p>
                        <div class="suggestions" id="suggestions">
                            <button class="suggestion-chip" onclick="fillInput(this)">
                                Qual é a situação do nosso FUNDEB este ano?
                            </button>
                            <button class="suggestion-chip" onclick="fillInput(this)">
                                Existe algum programa federal aberto para pavimentação?
                            </button>
                            <button class="suggestion-chip" onclick="fillInput(this)">
                                Crie um post para o Instagram sobre uma obra entregue
                            </button>
                            <button class="suggestion-chip" onclick="fillInput(this)">
                                Quais compromissos do mandato estão em risco?
                            </button>
                            <button class="suggestion-chip" onclick="fillInput(this)">
                                Me prepare para uma entrevista sobre saúde pública
                            </button>
                            <button class="suggestion-chip" onclick="fillInput(this)">
                                Resumo da situação fiscal do município
                            </button>
                        </div>
                    </div>
                @else
                    {{-- Mensagens da conversa --}}
                    @foreach ($messages as $msg)
                        <div class="message {{ $msg->role }}" data-id="{{ $msg->id }}">
                            <div class="message-avatar">
                                @if ($msg->role === 'user')
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                @else
                                    <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                                        <path
                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z" />
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <div class="message-bubble">
                                    {!! nl2br(e($msg->content)) !!}

                                    @if ($msg->role === 'assistant' && !empty($msg->rag_sources))
                                        <div class="rag-sources">
                                            @foreach (array_slice($msg->rag_sources, 0, 4) as $src)
                                                <span class="rag-source-tag">
                                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                                        <path
                                                            d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
                                                    </svg>
                                                    {{ $src['source'] ?? 'Fonte' }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="message-meta">
                                    <span>{{ $msg->created_at->format('H:i') }}</span>
                                    @if ($msg->role === 'assistant')
                                        <div class="message-feedback">
                                            <button class="feedback-btn"
                                                onclick="sendFeedback({{ $msg->id }}, 'thumbs_up')"
                                                title="Boa resposta">
                                                <svg viewBox="0 0 24 24" fill="currentColor">
                                                    <path
                                                        d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z" />
                                                </svg>
                                            </button>
                                            <button class="feedback-btn"
                                                onclick="sendFeedback({{ $msg->id }}, 'thumbs_down')"
                                                title="Resposta ruim">
                                                <svg viewBox="0 0 24 24" fill="currentColor">
                                                    <path
                                                        d="M15 3H6c-.83 0-1.54.5-1.84 1.22l-3.02 7.05c-.09.23-.14.47-.14.73v2c0 1.1.9 2 2 2h6.31l-.95 4.57-.03.32c0 .41.17.79.44 1.06L9.83 23l6.59-6.59c.36-.36.58-.86.58-1.41V5c0-1.1-.9-2-2-2zm4 0v12h4V3h-4z" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

            </div>

            {{-- Input --}}
            <div class="input-area">
                <div class="input-box">
                    <textarea id="msg-input" placeholder="Pergunte qualquer coisa sobre seu município, mandato ou comunicação..."
                        rows="1" onkeydown="handleEnter(event)" oninput="autoResize(this)"></textarea>
                    <div class="input-actions">
                        <!--<button class="btn-voice" id="btnVoice" title="Enviar por voz">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm-1-9c0-.55.45-1 1-1s1 .45 1 1v6c0 .55-.45 1-1 1s-1-.45-1-1V5zm6 6c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z" />
                                </svg>
                            </button>-->
                        <button class="btn-send" id="btnSend" onclick="sendMessage()" disabled>
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                            </svg>
                        </button>
                    </div>
                </div>
                <p class="input-hint">
                    As respostas são baseadas nos dados reais de
                    <strong>{{ auth()->user()->municipality->name }}</strong>.
                </p>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const mayor = '{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}';

        // ID da conversa ativa (null = nova)
        let activeConvId = @json(optional($activeConversation)->id);

        // ── Textarea auto-resize ─────────────────────────────────
        function autoResize(el) {
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 140) + 'px';
            document.getElementById('btnSend').disabled = el.value.trim().length === 0;
        }

        // ── Enter envia, Shift+Enter quebra linha ─────────────────
        function handleEnter(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        }

        // ── Sugestões de exemplo ──────────────────────────────────
        function fillInput(btn) {
            const input = document.getElementById('msg-input');
            input.value = btn.textContent.trim();
            autoResize(input);
            input.focus();
        }

        // ── Criar ou obter conversa ───────────────────────────────
        async function ensureConversation() {
            if (activeConvId) return activeConvId;
            const res = await fetch('{{ route('mayor.chat.create') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Content-Type': 'application/json'
                },
            });
            const data = await res.json();
            activeConvId = data.id;
            return activeConvId;
        }

        // ── Enviar mensagem ───────────────────────────────────────
        let isSubmitting = false;

        async function sendMessage() {
            if (isSubmitting) return; // Guard contra duplo disparo
            const input = document.getElementById('msg-input');
            const content = input.value.trim();
            if (!content) return;
            isSubmitting = true;

            // Esconder sugestões
            document.getElementById('chatEmpty')?.remove();

            // Adicionar bolha do usuário
            appendMessage('user', content);
            input.value = '';
            autoResize(input);
            document.getElementById('btnSend').disabled = true;

            // Mostrar typing
            const typingEl = appendTyping();

            try {
                const convId = await ensureConversation();

                const res = await fetch(`/mayor/chat/${convId}/send`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        message: content
                    }),
                });

                const data = await res.json();
                typingEl.remove();

                // Suporta tanto {success, content} quanto resposta direta {message}
                const responseText = data.content || data.message || null;
                if (data.success || responseText) {
                    appendMessage('assistant', responseText, data.sources || [], data.message_id || null);
                } else {
                    appendError(data.error || data.message || 'Ocorreu um erro. Tente novamente.');
                }
            } catch (err) {
                typingEl.remove();
                appendError('Não foi possível conectar ao servidor. Verifique sua conexão.');
            } finally {
                isSubmitting = false;
                document.getElementById('btnSend').disabled = document.getElementById('msg-input').value.trim()
                    .length === 0;
            }
        }

        // ── Adicionar bolha ───────────────────────────────────────
        function appendMessage(role, content, sources = [], msgId = null) {
            const area = document.getElementById('messagesArea');
            const el = document.createElement('div');
            el.className = `message ${role}`;
            if (msgId) el.dataset.id = msgId;

            const avatarContent = role === 'user' ?
                mayor :
                `<svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                 <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/>
               </svg>`;

            const sourcesHtml = (sources && sources.length > 0) ?
                `<div class="rag-sources">
                 ${sources.slice(0,4).map(s => `
                                                                                               <span class="rag-source-tag">
                                                                                                 <svg viewBox="0 0 24 24" fill="currentColor" width="10" height="10"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                                                                                                 ${s.source || 'Fonte'}
                                                                                               </span>`).join('')}
               </div>` :
                '';

            const feedbackHtml = role === 'assistant' && msgId ?
                `<div class="message-feedback">
                 <button class="feedback-btn" onclick="sendFeedback(${msgId}, 'thumbs_up')" title="Boa resposta">
                   <svg viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"/></svg>
                 </button>
                 <button class="feedback-btn" onclick="sendFeedback(${msgId}, 'thumbs_down')" title="Resposta ruim">
                   <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15 3H6c-.83 0-1.54.5-1.84 1.22l-3.02 7.05c-.09.23-.14.47-.14.73v2c0 1.1.9 2 2 2h6.31l-.95 4.57-.03.32c0 .41.17.79.44 1.06L9.83 23l6.59-6.59c.36-.36.58-.86.58-1.41V5c0-1.1-.9-2-2-2zm4 0v12h4V3h-4z"/></svg>
                 </button>
               </div>` :
                '';

            el.innerHTML = `
            <div class="message-avatar">${avatarContent}</div>
            <div>
                <div class="message-bubble">
                    ${escapeHtml(content).replace(/\n/g, '<br>')}
                    ${sourcesHtml}
                </div>
                <div class="message-meta">
                    <span>${new Date().toLocaleTimeString('pt-BR', {hour:'2-digit', minute:'2-digit'})}</span>
                    ${feedbackHtml}
                </div>
            </div>`;

            area.appendChild(el);
            area.scrollTop = area.scrollHeight;
            return el;
        }

        function appendTyping() {
            const area = document.getElementById('messagesArea');
            const el = document.createElement('div');
            el.className = 'typing-indicator';
            el.innerHTML = `
            <div class="message-avatar" style="background:var(--ink);color:#fff;font-size:.6rem;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center">
                <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg>
            </div>
            <div class="typing-bubble">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>`;
            area.appendChild(el);
            area.scrollTop = area.scrollHeight;
            return el;
        }

        function appendError(msg) {
            const area = document.getElementById('messagesArea');
            const el = document.createElement('div');
            el.style.cssText =
                'align-self:center;padding:.6rem 1rem;background:#fdf0f0;border:1px solid #f5c6c2;border-radius:8px;font-size:.82rem;color:#b52b2b';
            el.textContent = msg;
            area.appendChild(el);
            area.scrollTop = area.scrollHeight;
        }

        async function deleteConv(ev, id) {
            ev.preventDefault();
            ev.stopPropagation();
            if (!confirm('Excluir esta conversa?')) return;
            const res = await fetch(`/mayor/chat/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json().catch(() => ({}));
            if (data && data.success) {
                const el = document.querySelector(`.conv-item[data-id="${id}"]`);
                el && el.remove();
                if (activeConvId === id) {
                    activeConvId = null;
                    document.getElementById('messagesArea').innerHTML = `
                        <div class="chat-empty" id="chatEmpty">
                            <div class="chat-empty-icon">
                                <img width="100%" src="/images/icone-robo-redondo.png" alt="">
                            </div>
                            <h2>Conversa excluída</h2>
                            <p>Crie uma nova conversa para continuar.</p>
                        </div>`;
                }
            } else {
                appendError('Não foi possível excluir esta conversa.');
            }
        }

        // ── Feedback ──────────────────────────────────────────────
        async function sendFeedback(msgId, type) {
            await fetch(`/mayor/chat/messages/${msgId}/feedback`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    feedback: type
                }),
            });
        }

        // ── Nova conversa ─────────────────────────────────────────
        document.getElementById('btnNewConv').addEventListener('click', () => {
            activeConvId = null;
            document.getElementById('messagesArea').innerHTML = `
            <div class="chat-empty" id="chatEmpty">
                <div class="chat-empty-icon">
                    <img width="100%" src="/images/icone-robo-redondo.png" alt="">
                </div>
                <h2>Nova conversa</h2>
                Olá, {{ auth()->user()->name }} !
                <p>O que você gostaria de perguntar ao seu Marqueteiro?</p>
            </div>`;
            document.getElementById('msg-input').focus();
        });

        // ── Helpers ───────────────────────────────────────────────
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }

        const prefill = sessionStorage.getItem('chatPrefill');
        if (prefill) {
            sessionStorage.removeItem('chatPrefill');
            const input = document.getElementById('msg-input');
            input.value = prefill;
            autoResize(input);
            input.focus();
        }

        // Scroll para o final ao carregar
        const area = document.getElementById('messagesArea');
        area.scrollTop = area.scrollHeight;
    </script>
@endpush
