@extends('layouts.mayor')

@section('title', 'Demandas')
@section('topbar-title', 'Registro de Demandas')

@push('styles')
    <style>
        .demands-layout {
            padding: 1.75rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            max-width: 900px;
        }

        /* ── Cabeçalho ─── */
        .demands-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .demands-header h1 {
            font-family: 'Lora', serif;
            font-size: 1.5rem;
            color: var(--ink);
            margin-bottom: .25rem;
        }

        .demands-header p {
            font-size: .84rem;
            color: var(--ink-muted);
        }

        /* ── Hero de captura de voz ─── */
        .voice-hero {
            background: var(--ink);
            border-radius: 16px;
            padding: 2rem 2.25rem;
            display: flex;
            align-items: center;
            gap: 2rem;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition: opacity .2s;
        }

        .voice-hero::after {
            content: '';
            position: absolute;
            right: -30px;
            top: -30px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(184, 144, 42, .18), transparent 70%);
            pointer-events: none;
        }

        .voice-hero:hover {
            opacity: .95;
        }

        .vh-btn {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            flex-shrink: 0;
            background: rgba(255, 255, 255, .08);
            border: 2px solid rgba(255, 255, 255, .15);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .2s;
            position: relative;
            z-index: 1;
        }

        .vh-btn svg {
            width: 30px;
            height: 30px;
            color: #fff;
        }

        .vh-btn.recording {
            background: var(--red);
            border-color: var(--red);
            animation: pulse-mic 1s ease-in-out infinite;
        }

        @keyframes pulse-mic {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(181, 43, 43, .5);
            }

            50% {
                box-shadow: 0 0 0 14px rgba(181, 43, 43, 0);
            }
        }

        .vh-text {
            flex: 1;
        }

        .vh-text h2 {
            font-family: 'Lora', serif;
            font-size: 1.2rem;
            color: #fff;
            margin-bottom: .4rem;
        }

        .vh-text p {
            font-size: .82rem;
            color: rgba(255, 255, 255, .45);
            line-height: 1.5;
        }

        .vh-text .vh-status {
            font-size: .82rem;
            color: var(--gold-lt);
            font-weight: 500;
            margin-top: .6rem;
            min-height: 1.2em;
        }

        .vh-shortcut {
            flex-shrink: 0;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 10px;
            padding: .75rem 1.1rem;
        }

        .vh-shortcut-key {
            font-size: 1.1rem;
            color: #fff;
            font-weight: 500;
        }

        .vh-shortcut-lbl {
            font-size: .65rem;
            color: rgba(255, 255, 255, .3);
            margin-top: .2rem;
        }

        /* ── Form de demanda manual ─── */
        .manual-form {
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 14px;
            padding: 1.5rem;
        }

        .manual-form h3 {
            font-family: 'Lora', serif;
            font-size: 1rem;
            color: var(--ink);
            margin-bottom: 1rem;
        }

        .mf-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .85rem;
            margin-bottom: .85rem;
        }

        .mf-full {
            margin-bottom: .85rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: .3rem;
        }

        .form-label {
            font-size: .75rem;
            font-weight: 500;
            color: var(--ink-soft);
        }

        .form-input,
        .form-select,
        .form-textarea {
            padding: .58rem .8rem;
            border-radius: 9px;
            border: 1.5px solid var(--border);
            background: var(--white);
            font-family: "Open Sans", sans-serif;
            font-size: .84rem;
            color: var(--ink);
            outline: none;
            transition: border-color .15s;
            width: 100%;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: var(--ink);
        }

        .form-textarea {
            resize: vertical;
            min-height: 90px;
        }

        /* ── Transcrição temporária ─── */
        .transcript-preview {
            display: none;
            background: #fdfaf4;
            border: 1.5px solid var(--gold);
            border-radius: 12px;
            padding: 1.2rem 1.4rem;
        }

        .transcript-preview.visible {
            display: block;
        }

        .tp-label {
            font-size: .7rem;
            font-weight: 500;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: .5rem;
        }

        .tp-text {
            font-size: .9rem;
            color: var(--ink);
            line-height: 1.55;
            margin-bottom: 1rem;
        }

        .tp-actions {
            display: flex;
            gap: .6rem;
        }

        /* ── Lista de demandas ─── */
        .demands-list {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        .demand-item {
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 1rem 1.2rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: box-shadow .2s;
        }

        .demand-item:hover {
            box-shadow: 0 3px 10px rgba(0, 0, 0, .06);
        }

        .di-type-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 5px;
        }

        .di-type-dot.voice {
            background: var(--gold);
        }

        .di-type-dot.manual {
            background: #1a5fa8;
        }

        .di-body {
            flex: 1;
        }

        .di-text {
            font-size: .87rem;
            color: var(--ink);
            line-height: 1.5;
            margin-bottom: .4rem;
        }

        .di-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .di-tag {
            font-size: .7rem;
            color: var(--ink-muted);
            display: flex;
            align-items: center;
            gap: .25rem;
        }

        .di-tag svg {
            width: 11px;
            height: 11px;
        }

        .di-tag strong {
            color: var(--ink-soft);
        }

        .di-ask-btn {
            flex-shrink: 0;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: var(--surface);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--ink-muted);
            transition: all .12s;
            opacity: 0;
        }

        .demand-item:hover .di-ask-btn {
            opacity: 1;
        }

        .di-ask-btn:hover {
            background: var(--ink);
            color: #fff;
            border-color: var(--ink);
        }

        .di-ask-btn svg {
            width: 14px;
            height: 14px;
        }

        /* empty */
        .demands-empty {
            text-align: center;
            padding: 2.5rem;
            color: var(--ink-muted);
            font-size: .85rem;
        }

        .demands-empty svg {
            width: 32px;
            height: 32px;
            color: var(--border);
            display: block;
            margin: 0 auto .75rem;
        }

        /* btns */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            border-radius: 9px;
            font-family: "Open Sans", sans-serif;
            font-size: .83rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .15s;
            text-decoration: none;
            border: 1.5px solid transparent;
        }

        .btn svg {
            width: 16px;
            height: 16px;
        }

        .btn-dark {
            background: var(--ink);
            color: #fff;
            border-color: var(--ink);
        }

        .btn-dark:hover {
            background: var(--ink-soft);
        }

        .btn-outline {
            background: none;
            color: var(--ink);
            border-color: var(--border);
        }

        .btn-outline:hover {
            border-color: var(--ink);
        }

        .btn-gold {
            background: var(--gold);
            color: #fff;
            border-color: var(--gold);
        }

        .btn-gold:hover {
            background: var(--gold-lt);
            border-color: var(--gold-lt);
        }

        .btn-sm {
            padding: .38rem .8rem;
            font-size: .78rem;
        }

        @media(max-width:700px) {
            .demands-layout {
                padding: 1rem
            }

            .voice-hero {
                flex-direction: column;
                text-align: center
            }

            .vh-shortcut {
                display: none
            }

            .mf-row {
                grid-template-columns: 1fr
            }
        }
    </style>
@endpush

@section('content')
    <div class="demands-layout">

        {{-- Cabeçalho --}}
        <div class="demands-header">
            <div>
                <h1>Demandas</h1>
                <p>Registre demandas recebidas em campo por voz ou manualmente</p>
            </div>
        </div>

        @if (session('success'))
            <div
                style="background:var(--green-bg);border:1px solid #cfe9d9;color:var(--green);border-radius:12px;padding:.85rem 1rem;font-size:.85rem">
                {{ session('success') }}
            </div>
        @endif

        {{-- Hero de voz --}}
        <div class="voice-hero" id="voiceHero" onclick="toggleVoice()">
            <div class="vh-btn" id="vhBtn">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm-1-9c0-.55.45-1 1-1s1 .45 1 1v6c0 .55-.45 1-1 1s-1-.45-1-1V5zm6 6c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z" />
                </svg>
            </div>
            <div class="vh-text">
                <h2 id="vhTitle">Gravar</h2>
                <p>Toque para gravar uma demanda. O assistente organizará por tema e encaminhará para providência da
                    Secretaria ou Órgão Responsável.</p>
                <div class="vh-status" id="vhStatus"></div>
            </div>
            <div class="vh-shortcut">
                <div class="vh-shortcut-key">Space</div>
                <div class="vh-shortcut-lbl">atalho</div>
            </div>
        </div>

        {{-- Transcrição capturada --}}
        <div class="transcript-preview" id="transcriptPreview">
            <div class="tp-label">Transcrição capturada</div>
            <div class="tp-text" id="transcriptText"></div>
            <div class="tp-actions">
                <button class="btn btn-gold btn-sm" onclick="sendToAssistant()">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z" />
                    </svg>
                    Enviar ao assistente
                </button>
                <button class="btn btn-outline btn-sm" onclick="clearTranscript()">Descartar</button>
            </div>
        </div>

        {{-- Registro manual --}}
        <div class="manual-form">
            <h3>Registrar manualmente</h3>
            <form id="manualForm" method="POST" action="{{ route('mayor.mandato.demands.store') }}">
                @csrf
                <input type="hidden" name="input_type" value="text">
                <div class="mf-full">
                    <div class="form-group">
                        <label class="form-label">Descrição da demanda</label>
                        <textarea class="form-textarea" id="manualText" name="raw_input" placeholder="Descreva a demanda recebida..."></textarea>
                    </div>
                </div>
                <div class="mf-row">
                    <div class="form-group">
                        <label class="form-label">Localidade / Bairro</label>
                        <input class="form-input" type="text" id="manualLocation" name="locality"
                            placeholder="Ex: Bairro Nova Esperança">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Área demandante</label>
                        <select class="form-select" id="manualArea" name="area">
                            <option value="">Selecione</option>
                            <option value="saude">🏥 Saúde</option>
                            <option value="educacao">📚 Educação</option>
                            <option value="infraestrutura">🏗 Infraestrutura</option>
                            <option value="social">🤝 Social</option>
                            <option value="seguranca">🛡 Segurança</option>
                            <option value="meio_ambiente">🌿 Meio Ambiente</option>
                            <option value="economia">💼 Economia</option>
                            <option value="outros">📋 Outros</option>
                        </select>
                    </div>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:.6rem">
                    <button type="button" class="btn btn-outline btn-sm" onclick="sendManualToAssistant()">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" />
                        </svg>
                        Pedir para o assistente organizar
                    </button>
                    <button type="submit" class="btn btn-dark btn-sm">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z" />
                        </svg>
                        Salvar demanda
                    </button>
                </div>
            </form>
        </div>

        {{-- Demandas recentes --}}
        <div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.85rem">
                <h2 style="font-family:'Lora',serif;font-size:1rem;color:var(--ink)">Demandas recentes</h2>
            </div>
            <div class="demands-list">
                @forelse ($demands as $d)
                    <div class="demand-item">
                        <div class="di-type-dot manual"></div>
                        <div class="di-body">
                            <div class="di-text">{{ $d->title ?: $d->raw_input }}</div>
                            <div class="di-meta">
                                @if ($d->created_at)
                                    <span class="di-tag">
                                        <strong>{{ $d->created_at->format('d/m H:i') }}</strong>
                                    </span>
                                @endif
                                @if ($d->locality)
                                    <span class="di-tag">
                                        <strong>{{ $d->locality }}</strong>
                                    </span>
                                @endif
                                @if ($d->area)
                                    <span class="di-tag">{{ ucfirst(str_replace('_', ' ', $d->area)) }}</span>
                                @endif
                                <span class="di-tag">
                                    {{ match ($d->status) {'resolved' => '✅ resolvida','in_progress' => '🟦 em andamento','cancelled' => '⛔ cancelada',default => '🟨 pendente'} }}
                                </span>
                            </div>
                        </div>
                        <a class="di-ask-btn" href="{{ route('mayor.mandato.demands.show', $d) }}" title="Ver detalhes">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 8c-1.1 0-2 .9-2 2h2a1 1 0 1 1 1 1c-.55 0-1 .45-1 1v2h2v-1.1c1.15-.4 2-1.5 2-2.8 0-1.66-1.34-3-3-3zm-1 10h2v-2h-2v2zm1-16C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" />
                            </svg>
                        </a>
                    </div>
                @empty
                    <div class="demands-empty">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z" />
                        </svg>
                        Nenhuma demanda registrada ainda.<br>Grave por voz ou preencha o formulário.
                    </div>
                @endforelse
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        let recognition = null;
        let isRecording = false;

        const AREA_LABELS = {
            saude: 'Saúde',
            educacao: 'Educação',
            infraestrutura: 'Infraestrutura',
            social: 'Social',
            seguranca: 'Segurança',
            meio_ambiente: 'Meio Ambiente',
            economia: 'Economia',
            outros: 'Outros',
            '': ''
        };

        document.addEventListener('DOMContentLoaded', () => {
            document.addEventListener('keydown', (e) => {
                if (e.code === 'Space' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
                    e.preventDefault();
                    toggleVoice();
                }
            });
        });

        function toggleVoice() {
            if (isRecording) {
                stopVoice();
                return;
            }

            if (!('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
                alert('Seu navegador não suporta reconhecimento de voz. Use o Chrome.');
                return;
            }

            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SR();
            recognition.lang = 'pt-BR';
            recognition.continuous = true;
            recognition.interimResults = true;

            const btn = document.getElementById('vhBtn');
            const title = document.getElementById('vhTitle');
            const status = document.getElementById('vhStatus');

            btn.classList.add('recording');
            title.textContent = 'Gravando… toque para parar';
            status.textContent = '● Aguardando fala...';
            isRecording = true;

            let finalTranscript = '';

            recognition.onresult = (e) => {
                let interim = '';
                for (let i = e.resultIndex; i < e.results.length; i++) {
                    const t = e.results[i][0].transcript;
                    if (e.results[i].isFinal) finalTranscript += t + ' ';
                    else interim += t;
                }
                status.textContent = '● ' + (interim || finalTranscript || '...');
            };

            recognition.onerror = stopVoice;
            recognition.onend = () => {
                stopVoice();
                if (finalTranscript.trim()) showTranscript(finalTranscript.trim());
            };

            recognition.start();
        }

        function stopVoice() {
            if (recognition) {
                try {
                    recognition.stop();
                } catch (e) {}
            }
            isRecording = false;
            const btn = document.getElementById('vhBtn');
            const title = document.getElementById('vhTitle');
            const status = document.getElementById('vhStatus');
            btn.classList.remove('recording');
            title.textContent = 'Gravar';
            status.textContent = '';
        }

        function showTranscript(text) {
            document.getElementById('transcriptText').textContent = text;
            document.getElementById('transcriptPreview').classList.add('visible');
            document.getElementById('manualText').value = text;
        }

        function clearTranscript() {
            document.getElementById('transcriptPreview').classList.remove('visible');
            document.getElementById('transcriptText').textContent = '';
        }

        function sendToAssistant() {
            const text = document.getElementById('transcriptText').textContent;
            sessionStorage.setItem('chatPrefill',
                `Registre esta demanda que recebi em campo: "${text}". Organize por tema, localidade e secretaria responsável, e sugira as próximas ações.`
            );
            window.location.href = '{{ route('mayor.chat.index') }}';
        }

        function submitManual(e) {
            const text = document.getElementById('manualText').value.trim();
            const location = document.getElementById('manualLocation').value.trim();
            const area = document.getElementById('manualArea').value;
            if (!text) {
                return;
            }
            saveDemand({
                text,
                location,
                area,
                type: 'manual'
            });
            document.getElementById('manualForm').reset();
            clearTranscript();
        }

        function sendManualToAssistant() {
            const text = document.getElementById('manualText').value.trim();
            const location = document.getElementById('manualLocation').value.trim();
            const area = document.getElementById('manualArea').value;
            if (!text) {
                document.getElementById('manualText').focus();
                return;
            }
            const ctx = [text, location ? `Localidade: ${location}` : '', area ? `Área: ${AREA_LABELS[area]}` : ''].filter(
                Boolean).join('. ');
            sessionStorage.setItem('chatPrefill',
                `Registre e organize esta demanda: "${ctx}". Identifique o tema, localidade, secretaria responsável e sugira próximas ações.`
            );
            window.location.href = '{{ route('mayor.chat.index') }}';
        }
    </script>
@endpush
