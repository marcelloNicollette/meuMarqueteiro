@extends('layouts.mayor')

@section('title', 'Conteúdo')
@section('topbar-title', 'Comunicação e Conteúdo')

@push('styles')
    <style>
        .content-layout {
            display: grid;
            grid-template-columns: 340px 1fr;
            height: calc(100vh - var(--nav-h));
            overflow: hidden;
        }

        .generator-panel {
            background: var(--white);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .panel-header {
            padding: 1.2rem 1.4rem;
            border-bottom: 1px solid var(--border-lt);
            position: sticky;
            top: 0;
            background: var(--white);
            z-index: 10;
        }

        .panel-header h2 {
            font-family: 'Lora', serif;
            font-size: 1rem;
            color: var(--ink);
            margin-bottom: .15rem;
        }

        .panel-header p {
            font-size: .78rem;
            color: var(--ink-muted);
        }

        .panel-body {
            padding: 1.2rem 1.4rem;
            flex: 1;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: .25rem;
            background: var(--surface);
            border-radius: 9px;
            padding: .28rem;
            margin-bottom: 1.4rem;
        }

        .tab-btn {
            flex: 1;
            padding: .46rem .2rem;
            border: none;
            background: none;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            font-size: .72rem;
            font-weight: 500;
            color: var(--ink-muted);
            border-radius: 7px;
            transition: all .15s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .22rem;
        }

        .tab-btn.active {
            background: var(--white);
            color: var(--ink);
            box-shadow: 0 1px 4px rgba(0, 0, 0, .08);
        }

        .tab-btn.active.image-tab {
            color: #7c3aed;
        }

        .tab-btn svg {
            width: 11px;
            height: 11px;
            flex-shrink: 0;
        }

        /* Campos */
        .field {
            margin-bottom: 1rem;
        }

        .field label {
            display: block;
            font-size: .72rem;
            font-weight: 500;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--ink-soft);
            margin-bottom: .45rem;
        }

        .field input,
        .field textarea,
        .field select {
            width: 100%;
            padding: .7rem .85rem;
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: .87rem;
            color: var(--ink);
            outline: none;
            transition: border-color .2s, background .2s;
        }

        .field input:focus,
        .field textarea:focus,
        .field select:focus {
            border-color: var(--gold);
            background: var(--white);
        }

        .field textarea {
            resize: none;
            min-height: 80px;
        }

        /* Chips */
        .chip-group {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }

        .chip {
            padding: .38rem .7rem;
            border-radius: 20px;
            border: 1.5px solid var(--border);
            background: none;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            font-size: .76rem;
            color: var(--ink-soft);
            transition: all .15s;
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        .chip.selected {
            border-color: var(--gold);
            background: var(--gold-bg);
            color: var(--gold);
            font-weight: 500;
        }

        .chip.selected.purple {
            border-color: #7c3aed;
            background: #f5f3ff;
            color: #7c3aed;
        }

        /* Botões */
        .btn-generate {
            width: 100%;
            padding: .85rem;
            background: var(--ink);
            color: var(--white);
            border: none;
            border-radius: 9px;
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem;
            font-weight: 500;
            cursor: pointer;
            margin-top: .5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            transition: background .15s;
        }

        .btn-generate:hover {
            background: #1e2230;
        }

        .btn-generate.purple-btn {
            background: #7c3aed;
        }

        .btn-generate.purple-btn:hover {
            background: #6d28d9;
        }

        .btn-generate svg {
            width: 16px;
            height: 16px;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin: 1.4rem 0;
            font-size: .72rem;
            color: var(--ink-muted);
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* Painel direito */
        .results-panel {
            overflow-y: auto;
            padding: 1.5rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .results-empty {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
            min-height: 60vh;
        }

        .results-empty-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: var(--surface);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.2rem;
        }

        .results-empty-icon svg {
            width: 26px;
            height: 26px;
            color: var(--ink-muted);
        }

        .results-empty h3 {
            font-family: 'Lora', serif;
            font-size: 1.1rem;
            color: var(--ink);
            margin-bottom: .5rem;
        }

        .results-empty p {
            font-size: .84rem;
            color: var(--ink-muted);
            max-width: 300px;
            line-height: 1.7;
        }

        /* Content cards */
        .content-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .content-card-header {
            padding: .9rem 1.2rem;
            border-bottom: 1px solid var(--border-lt);
            display: flex;
            align-items: center;
            gap: .7rem;
        }

        .channel-icon {
            width: 30px;
            height: 30px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: .7rem;
            font-weight: 700;
        }

        .channel-icon.instagram {
            background: #fce4ec;
            color: #c62828;
        }

        .channel-icon.facebook {
            background: #e3f2fd;
            color: #1565c0;
        }

        .content-card-meta {
            flex: 1;
        }

        .content-card-title {
            font-size: .87rem;
            font-weight: 500;
            color: var(--ink);
        }

        .content-card-info {
            font-size: .72rem;
            color: var(--ink-muted);
            margin-top: .1rem;
        }

        .content-card-status {
            padding: .2rem .6rem;
            border-radius: 10px;
            font-size: .7rem;
            font-weight: 500;
        }

        .status-draft {
            background: #f1f5f9;
            color: #64748b;
        }

        .variation-tabs {
            display: flex;
            border-bottom: 1px solid var(--border-lt);
            background: var(--surface);
        }

        .var-tab {
            padding: .55rem 1rem;
            font-size: .78rem;
            font-weight: 500;
            color: var(--ink-muted);
            cursor: pointer;
            border: none;
            background: none;
            font-family: 'DM Sans', sans-serif;
            border-bottom: 2px solid transparent;
            transition: all .15s;
        }

        .var-tab.active {
            color: var(--ink);
            background: var(--white);
            border-bottom-color: var(--gold);
        }

        .variation-content {
            padding: 1.1rem 1.2rem;
        }

        .post-text {
            font-size: .87rem;
            line-height: 1.75;
            color: var(--ink-soft);
            white-space: pre-wrap;
            min-height: 80px;
        }

        .content-card-actions {
            padding: .8rem 1.2rem;
            border-top: 1px solid var(--border-lt);
            display: flex;
            gap: .5rem;
            background: var(--surface);
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .45rem .85rem;
            border-radius: 7px;
            font-family: 'DM Sans', sans-serif;
            font-size: .78rem;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--ink-soft);
            transition: all .15s;
        }

        .action-btn:hover {
            border-color: var(--ink);
            color: var(--ink);
        }

        .action-btn.primary {
            background: var(--ink);
            color: var(--white);
            border-color: var(--ink);
        }

        .action-btn.purple {
            background: #7c3aed;
            color: var(--white);
            border-color: #7c3aed;
        }

        .action-btn.purple:hover {
            background: #6d28d9;
        }

        .action-btn svg {
            width: 13px;
            height: 13px;
        }

        .crisis-result {
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 1.4rem 1.5rem;
        }

        .crisis-result h4 {
            font-family: 'Lora', serif;
            font-size: .95rem;
            color: var(--ink);
            margin-bottom: .85rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .crisis-result-body {
            font-size: .86rem;
            line-height: 1.8;
            color: var(--ink-soft);
            white-space: pre-wrap;
        }

        /* Skeleton */
        .skel {
            background: linear-gradient(90deg, var(--surface) 25%, var(--border-lt) 50%, var(--surface) 75%);
            background-size: 200% 100%;
            border-radius: 4px;
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            from {
                background-position: 200% 0;
            }

            to {
                background-position: -200% 0;
            }
        }

        /* ═══════════════════════════════════════
           IMAGEM IA — estilos específicos
        ═══════════════════════════════════════ */
        .image-info-box {
            background: linear-gradient(135deg, #f5f3ff 0%, #faf5ff 100%);
            border: 1.5px solid #ede9fe;
            border-radius: 10px;
            padding: .85rem 1rem;
            margin-bottom: 1.1rem;
            display: flex;
            gap: .6rem;
            align-items: flex-start;
        }

        .image-info-box strong {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            color: #4c1d95;
            margin-bottom: .2rem;
        }

        .image-info-box p {
            font-size: .73rem;
            color: #6d28d9;
            line-height: 1.5;
            margin: 0;
        }

        /* Header de resultados de imagem */
        .img-results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: .5rem;
            border-bottom: 1px solid var(--border-lt);
        }

        .img-results-header h3 {
            font-family: 'Lora', serif;
            font-size: 1rem;
            color: var(--ink);
        }

        .img-results-header span {
            font-size: .76rem;
            color: var(--ink-muted);
        }

        /* Banner CTAs — botões que funcionam */
        .tools-banner {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: .85rem 1.1rem;
            display: flex;
            align-items: center;
            gap: .6rem;
            flex-wrap: wrap;
        }

        .tools-banner-label {
            font-size: .8rem;
            color: var(--ink-soft);
            font-weight: 600;
            white-space: nowrap;
        }

        .tool-btn {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .38rem .8rem;
            border-radius: 7px;
            font-family: 'DM Sans', sans-serif;
            font-size: .76rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all .15s;
            text-decoration: none;
        }

        .tool-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, .1);
        }

        .tool-btn.dalle {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .tool-btn.midj {
            background: #dcfce7;
            color: #15803d;
        }

        .tool-btn.canva {
            background: #fdf4ff;
            color: #86198f;
        }

        .tool-btn.ideogram {
            background: #fff7ed;
            color: #c2410c;
        }

        .tool-btn svg {
            width: 13px;
            height: 13px;
        }

        /* Card principal de prompt */
        .prompt-card {
            background: var(--white);
            border: 1.5px solid #e5e7eb;
            border-radius: 14px;
            transition: box-shadow .2s, border-color .2s;
        }

        .prompt-card:hover {
            box-shadow: 0 6px 24px rgba(124, 58, 237, .1);
            border-color: #c4b5fd;
        }

        /* Faixa de instrucao no topo do card */
        .prompt-how-to {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            padding: .6rem 1.2rem;
            display: flex;
            align-items: center;
            gap: .55rem;
            border-bottom: 1px solid rgba(124, 58, 237, .3);
        }

        .prompt-how-to-icon {
            font-size: .9rem;
            flex-shrink: 0;
        }

        .prompt-how-to span {
            font-size: .73rem;
            color: rgba(255, 255, 255, .65);
        }

        /* Numero + label do card */
        .prompt-card-header {
            padding: .85rem 1.2rem;
            display: flex;
            align-items: center;
            gap: .75rem;
            border-bottom: 1px solid var(--border-lt);
            background: linear-gradient(135deg, #f5f3ff 0%, #faf5ff 100%);
        }

        .prompt-num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #7c3aed;
            color: white;
            font-size: .72rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .prompt-label {
            font-size: .85rem;
            font-weight: 600;
            color: #4c1d95;
            flex: 1;
        }

        .prompt-badge {
            font-size: .68rem;
            padding: .18rem .55rem;
            border-radius: 20px;
            background: #ede9fe;
            color: #7c3aed;
            font-weight: 500;
            white-space: nowrap;
        }

        /* Corpo do card */
        .prompt-card-body {
            padding: 1.1rem 1.2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .section-label {
            font-size: .68rem;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--ink-muted);
            margin-bottom: .35rem;
        }

        /* Caixa do prompt — principal, bem destacada */
        .prompt-box {
            background: #1e1b4b;
            border-radius: 10px;
            padding: 1rem;
            position: relative;
        }

        .prompt-box-label {
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #a78bfa;
            margin-bottom: .5rem;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .prompt-box-label svg {
            width: 10px;
            height: 10px;
        }

        .prompt-box-text {
            font-family: 'DM Mono', 'SF Mono', 'Courier New', monospace;
            font-size: .78rem;
            line-height: 1.7;
            color: #e0d9ff;
            white-space: pre-wrap;
            word-break: break-word;
            max-height: 120px;
            overflow-y: auto;
        }

        .prompt-box-text::-webkit-scrollbar {
            width: 3px;
        }

        .prompt-box-text::-webkit-scrollbar-thumb {
            background: #4c1d95;
            border-radius: 3px;
        }

        .prompt-copy-btn {
            position: absolute;
            top: .6rem;
            right: .6rem;
            background: rgba(124, 58, 237, .6);
            border: none;
            border-radius: 6px;
            padding: .3rem .5rem;
            cursor: pointer;
            color: white;
            font-size: .68rem;
            display: flex;
            align-items: center;
            gap: .3rem;
            transition: background .15s;
        }

        .prompt-copy-btn:hover {
            background: #7c3aed;
        }

        .prompt-copy-btn svg {
            width: 11px;
            height: 11px;
        }

        /* Caixa negative prompt */
        .negative-box {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 8px;
            padding: .65rem .85rem;
        }

        .negative-box-text {
            font-family: 'DM Mono', 'SF Mono', monospace;
            font-size: .76rem;
            line-height: 1.6;
            color: #be123c;
            white-space: pre-wrap;
        }

        .description-box {
            background: var(--surface);
            border-radius: 8px;
            padding: .7rem .85rem;
        }

        .description-box-text {
            font-size: .84rem;
            line-height: 1.7;
            color: var(--ink-soft);
        }

        .caption-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: .7rem .85rem;
        }

        .caption-text-content {
            font-size: .84rem;
            line-height: 1.75;
            color: #14532d;
            white-space: pre-wrap;
        }

        .hashtag-box {
            background: #eff6ff;
            border-radius: 8px;
            padding: .65rem .85rem;
        }

        .hashtag-text-content {
            font-size: .78rem;
            color: #1d4ed8;
            line-height: 1.8;
            word-break: break-word;
        }

        /* Ações do card */
        .prompt-actions {
            padding: .85rem 1.2rem;
            border-top: 1px solid var(--border-lt);
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            background: #fafafa;
            align-items: center;
        }

        .copy-all-btn {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .5rem 1rem;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: .78rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            background: #7c3aed;
            color: white;
            transition: background .15s;
        }

        .copy-all-btn:hover {
            background: #6d28d9;
        }

        .copy-all-btn svg {
            width: 13px;
            height: 13px;
        }

        /* Design tips */
        .design-tips {
            background: linear-gradient(135deg, #fef3c7, #fff8e7);
            border: 1px solid #fde68a;
            border-radius: 12px;
            padding: 1.1rem 1.2rem;
        }

        .design-tips h4 {
            font-size: .8rem;
            font-weight: 700;
            color: #92400e;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: .7rem;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .design-tips li {
            font-size: .82rem;
            color: #78350f;
            line-height: 1.6;
            padding: .25rem 0;
            display: flex;
            align-items: flex-start;
            gap: .5rem;
            list-style: none;
        }

        .design-tips li::before {
            content: '→';
            color: #d97706;
            font-weight: 700;
            flex-shrink: 0;
        }

        .img-hist-item {
            padding: .6rem 0;
            border-bottom: 1px solid var(--border-lt);
            cursor: pointer;
            display: flex;
            gap: .6rem;
            align-items: center;
        }

        .img-hist-icon {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: #ede9fe;
            color: #7c3aed;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Toast */
        .copy-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #7c3aed;
            color: white;
            padding: .65rem 1.2rem;
            border-radius: 8px;
            font-size: .83rem;
            font-weight: 500;
            z-index: 9999;
            box-shadow: 0 4px 20px rgba(124, 58, 237, .35);
            animation: toastIn .2s ease;
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

        @media (max-width: 900px) {
            .content-layout {
                grid-template-columns: 1fr;
            }

            .generator-panel {
                max-height: 420px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="content-layout">

        {{-- ══ Painel esquerdo ══ --}}
        <div class="generator-panel">
            <div class="panel-header">
                <h2>Gerar conteúdo</h2>
                <p>Posts, imagens IA, entrevistas e crise</p>
            </div>
            <div class="panel-body">

                <div class="tabs">
                    <button class="tab-btn active" onclick="switchTab('post',this)">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z" />
                        </svg>
                        Post
                    </button>
                    <button class="tab-btn image-tab" onclick="switchTab('image',this)">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M21 3H3C2 3 1 4 1 5v14c0 1.1.9 2 2 2h18c1 0 2-1 2-2V5c0-1-1-2-2-2zm0 16H3V5h18v14zm-5-7l-3 3.72L11 13l-4 5h14l-4-5z" />
                        </svg>
                        Imagem IA
                    </button>
                    <button class="tab-btn" onclick="switchTab('interview',this)">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z" />
                        </svg>
                        Entrevista
                    </button>
                    <button class="tab-btn" onclick="switchTab('crisis',this)">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                        </svg>
                        Crise
                    </button>
                </div>

                {{-- Tab Post --}}
                <div id="tab-post">
                    <div class="field">
                        <label>Tema / ação de governo</label>
                        <textarea id="post-theme"
                            placeholder="Ex: Entrega do novo posto de saúde do bairro São João, com 4 consultórios e atendimento 24h..."
                            rows="3"></textarea>
                    </div>
                    <div class="field">
                        <label>Canal</label>
                        <div class="chip-group">
                            <button class="chip selected" data-channel="instagram"
                                onclick="this.classList.toggle('selected')">Instagram</button>
                            <button class="chip" data-channel="facebook"
                                onclick="this.classList.toggle('selected')">Facebook</button>
                            <button class="chip" data-channel="whatsapp"
                                onclick="this.classList.toggle('selected')">WhatsApp</button>
                            <button class="chip" data-channel="discurso"
                                onclick="this.classList.toggle('selected')">Discurso</button>
                        </div>
                    </div>
                    <div class="field">
                        <label>Tom</label>
                        <div class="chip-group">
                            <button class="chip selected" data-tone="celebratorio"
                                onclick="this.classList.toggle('selected')">Celebratório</button>
                            <button class="chip" data-tone="tecnico"
                                onclick="this.classList.toggle('selected')">Técnico</button>
                            <button class="chip selected" data-tone="empatico"
                                onclick="this.classList.toggle('selected')">Empático</button>
                            <button class="chip" data-tone="informativo"
                                onclick="this.classList.toggle('selected')">Informativo</button>
                        </div>
                    </div>
                    <button class="btn-generate" onclick="generatePost()">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z" />
                        </svg>
                        Gerar post
                    </button>
                    <div class="divider">histórico</div>
                    @forelse($posts ?? [] as $content)
                        <div style="padding:.6rem 0;border-bottom:1px solid var(--border-lt);cursor:pointer"
                            onclick="loadContent({{ $content->id }})">
                            <div
                                style="font-size:.82rem;font-weight:500;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                {{ $content->title }}</div>
                            <div style="font-size:.72rem;color:var(--ink-muted);margin-top:.1rem">
                                {{ ucfirst($content->channel) }} · {{ $content->created_at->diffForHumans() }}</div>
                        </div>
                    @empty
                        <p style="font-size:.8rem;color:var(--ink-muted);text-align:center;padding:.5rem 0">Nenhum post
                            gerado ainda.</p>
                    @endforelse
                </div>

                {{-- Tab Imagem IA --}}
                <div id="tab-image" style="display:none">
                    <div class="image-info-box">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="#7c3aed"
                            style="flex-shrink:0;margin-top:1px">
                            <path
                                d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                        </svg>
                        <div>
                            <strong>Gerador de imagem para Instagram</strong>
                            <p>A IA cria prompts otimizados para DALL-E 3, Midjourney, Canva e Ideogram. Copie o prompt e
                                cole diretamente na ferramenta.</p>
                        </div>
                    </div>
                    <div class="field">
                        <label>Tema da imagem</label>
                        <textarea id="image-theme"
                            placeholder="Ex: Inauguração da nova praça do bairro Vila Nova com playground e iluminação LED, presença do prefeito e moradores..."
                            rows="3"></textarea>
                    </div>
                    <div class="field">
                        <label>Estilo visual</label>
                        <div class="chip-group" id="style-chips">
                            <button class="chip purple selected" data-style="moderno"
                                onclick="selectOne(this,'style-chips')">Moderno</button>
                            <button class="chip purple" data-style="fotografico"
                                onclick="selectOne(this,'style-chips')">Fotográfico</button>
                            <button class="chip purple" data-style="vibrante"
                                onclick="selectOne(this,'style-chips')">Vibrante</button>
                            <button class="chip purple" data-style="minimalista"
                                onclick="selectOne(this,'style-chips')">Minimalista</button>
                            <button class="chip purple" data-style="aquarela"
                                onclick="selectOne(this,'style-chips')">Aquarela</button>
                            <button class="chip purple" data-style="tradicional"
                                onclick="selectOne(this,'style-chips')">Tradicional</button>
                        </div>
                    </div>
                    <div class="field">
                        <label>Formato</label>
                        <div class="chip-group" id="format-chips">
                            <button class="chip purple selected" data-format="feed"
                                onclick="selectOne(this,'format-chips')">Feed (1:1)</button>
                            <button class="chip purple" data-format="stories"
                                onclick="selectOne(this,'format-chips')">Stories (9:16)</button>
                            <button class="chip purple" data-format="carrossel"
                                onclick="selectOne(this,'format-chips')">Carrossel</button>
                        </div>
                    </div>
                    <div class="field">
                        <label>Paleta de cores</label>
                        <div class="chip-group" id="color-chips">
                            <button class="chip purple selected" data-color="governo"
                                onclick="selectOne(this,'color-chips')">Governo BR</button>
                            <button class="chip purple" data-color="neutro"
                                onclick="selectOne(this,'color-chips')">Neutro</button>
                            <button class="chip purple" data-color="terra" onclick="selectOne(this,'color-chips')">Tons
                                de Terra</button>
                            <button class="chip purple" data-color="vibrante"
                                onclick="selectOne(this,'color-chips')">Vibrante</button>
                        </div>
                    </div>
                    <button class="btn-generate purple-btn" onclick="generateImage()">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M21 3H3C2 3 1 4 1 5v14c0 1.1.9 2 2 2h18c1 0 2-1 2-2V5c0-1-1-2-2-2zm0 16H3V5h18v14zm-5-7l-3 3.72L11 13l-4 5h14l-4-5z" />
                        </svg>
                        Gerar prompts com IA
                    </button>
                    <div class="divider">histórico de imagens</div>
                    @php $images = ($contents ?? collect())->where('type','imagem_instagram'); @endphp
                    @forelse($images as $img)
                        <div class="img-hist-item" onclick="loadContent({{ $img->id }})">
                            <div class="img-hist-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M21 3H3C2 3 1 4 1 5v14c0 1.1.9 2 2 2h18c1 0 2-1 2-2V5c0-1-1-2-2-2zm0 16H3V5h18v14zm-5-7l-3 3.72L11 13l-4 5h14l-4-5z" />
                                </svg>
                            </div>
                            <div>
                                <div
                                    style="font-size:.82rem;font-weight:500;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px">
                                    {{ $img->title }}</div>
                                <div style="font-size:.7rem;color:var(--ink-muted)">{{ ucfirst($img->tone) }} ·
                                    {{ $img->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <p style="font-size:.8rem;color:var(--ink-muted);text-align:center;padding:.5rem 0">Nenhuma imagem
                            gerada ainda.</p>
                    @endforelse
                </div>

                {{-- Tab Entrevista --}}
                <div id="tab-interview" style="display:none">
                    <div class="field">
                        <label>Contexto da entrevista</label>
                        <textarea id="interview-context"
                            placeholder="Ex: Entrevista ao vivo para a Rádio Comunitária FM sobre os 100 dias de governo..." rows="4"></textarea>
                    </div>
                    <div class="field">
                        <label>Temas sensíveis (opcional)</label>
                        <input type="text" id="interview-sensitive" placeholder="Ex: atraso na reforma do ginásio...">
                    </div>
                    <button class="btn-generate" onclick="generateInterview()">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z" />
                        </svg>
                        Preparar para entrevista
                    </button>
                    <div class="divider">histórico</div>
                    @forelse($entrevistas ?? [] as $c)
                        <div style="padding:.6rem 0;border-bottom:1px solid var(--border-lt);cursor:pointer"
                            onclick="loadContent({{ $c->id }})">
                            <div style="font-size:.82rem;font-weight:500;color:var(--ink)">{{ $c->title }}</div>
                            <div style="font-size:.72rem;color:var(--ink-muted)">{{ $c->created_at->diffForHumans() }}
                            </div>
                        </div>
                    @empty
                        <p style="font-size:.8rem;color:var(--ink-muted);text-align:center;padding:.5rem 0">Nenhuma
                            entrevista gerada.</p>
                    @endforelse
                </div>

                {{-- Tab Crise --}}
                <div id="tab-crisis" style="display:none">
                    <div
                        style="background:#fff8f0;border:1px solid #ffe0b2;border-radius:8px;padding:.85rem;margin-bottom:1rem;display:flex;gap:.6rem;align-items:flex-start">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="#e65100"
                            style="flex-shrink:0;margin-top:1px">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                        </svg>
                        <p style="font-size:.78rem;color:#bf360c;line-height:1.5;margin:0">Para situações urgentes.
                            Descreva o cenário com o máximo de detalhes.</p>
                    </div>
                    <div class="field">
                        <label>Descreva a situação</label>
                        <textarea id="crisis-description"
                            placeholder="Ex: Vídeo vazou nas redes mostrando obra parada há 3 meses. Oposição já está noticiando..."
                            rows="5"></textarea>
                    </div>
                    <button class="btn-generate" style="background:#c0392b" onclick="generateCrisis()">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M13 2.05v2.02c3.95.49 7 3.85 7 7.93 0 3.21-1.81 6-4.72 7.28L13 17v5h5l-1.22-1.22C19.91 19.07 22 15.76 22 12c0-5.18-3.95-9.45-9-9.95zM11 2.05C5.95 2.55 2 6.82 2 12c0 3.76 2.09 7.07 5.22 8.78L6 22h5v-5l-2.28 2.28C7.81 18 6 15.21 6 12c0-4.08 3.05-7.44 7-7.93V2.05z" />
                        </svg>
                        Gerenciar crise agora
                    </button>
                    <div class="divider">histórico</div>
                    @forelse($crises ?? [] as $c)
                        <div style="padding:.6rem 0;border-bottom:1px solid var(--border-lt);cursor:pointer"
                            onclick="loadContent({{ $c->id }})">
                            <div style="font-size:.82rem;font-weight:500;color:var(--ink)">{{ $c->title }}</div>
                            <div style="font-size:.72rem;color:var(--ink-muted)">{{ $c->created_at->diffForHumans() }}
                            </div>
                        </div>
                    @empty
                        <p style="font-size:.8rem;color:var(--ink-muted);text-align:center;padding:.5rem 0">Nenhuma crise
                            registrada.</p>
                    @endforelse
                </div>

            </div>
        </div>

        {{-- ══ Painel direito ══ --}}
        <div class="results-panel" id="resultsPanel">
            <div class="results-empty" id="resultsEmpty">
                <div class="results-empty-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M21 3H3C2 3 1 4 1 5v14c0 1.1.9 2 2 2h18c1 0 2-1 2-2V5c0-1-1-2-2-2zm0 16H3V5h18v14zm-5-7l-3 3.72L11 13l-4 5h14l-4-5z" />
                    </svg>
                </div>
                <h3>Pronto para criar</h3>
                <p>Escolha o tipo de conteúdo, preencha o tema e clique em gerar. Os resultados aparecem aqui.</p>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;

        // ── Tabs ──────────────────────────────────────────────────────
        function switchTab(name, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            ['post', 'image', 'interview', 'crisis'].forEach(t =>
                document.getElementById('tab-' + t).style.display = t === name ? 'block' : 'none'
            );
        }

        function selectOne(btn, groupId) {
            document.getElementById(groupId).querySelectorAll('.chip').forEach(c => c.classList.remove('selected'));
            btn.classList.add('selected');
        }

        // ── Gerar Post ────────────────────────────────────────────────
        async function generatePost() {
            const theme = document.getElementById('post-theme').value.trim();
            if (!theme) {
                alert('Descreva o tema.');
                return;
            }
            const channels = [...document.querySelectorAll('#tab-post .chip[data-channel].selected')].map(c => c.dataset
                .channel);
            const tones = [...document.querySelectorAll('#tab-post .chip[data-tone].selected')].map(c => c.dataset
            .tone);
            if (!channels.length) {
                alert('Selecione ao menos um canal.');
                return;
            }
            showSkeleton();
            try {
                const data = await (await apiFetch('{{ route('mayor.content.generate-post') }}', {
                    theme,
                    channel: channels[0],
                    tones
                })).json();
                renderPostCard(data);
            } catch (e) {
                showError('Erro ao gerar post.');
            }
        }

        // ── Gerar Imagem IA ───────────────────────────────────────────
        async function generateImage() {
            const theme = document.getElementById('image-theme').value.trim();
            if (!theme) {
                alert('Descreva o tema da imagem.');
                return;
            }
            const style = document.querySelector('#style-chips .chip.selected')?.dataset.style || 'moderno';
            const format = document.querySelector('#format-chips .chip.selected')?.dataset.format || 'feed';
            const color = document.querySelector('#color-chips .chip.selected')?.dataset.color || 'governo';
            showImageSkeleton();
            try {
                const data = await (await apiFetch('{{ route('mayor.content.generate-image') }}', {
                    theme,
                    image_style: style,
                    format,
                    color_tone: color
                })).json();
                if (!data.success) throw new Error(data.error || 'Erro desconhecido');
                renderImageResults(data.prompts, data.design_tips, theme);
            } catch (e) {
                showError('Erro ao gerar prompts: ' + e.message);
            }
        }

        // ── Gerar Entrevista ─────────────────────────────────────────
        async function generateInterview() {
            const context = document.getElementById('interview-context').value.trim();
            if (!context) {
                alert('Descreva o contexto.');
                return;
            }
            showSkeleton();
            try {
                const data = await (await apiFetch('{{ route('mayor.content.interview-prep') }}', {
                    context,
                    sensitive_topics: document.getElementById('interview-sensitive').value
                })).json();
                renderTextResult(data.content, '📋 Preparação para entrevista');
            } catch (e) {
                showError('Erro ao gerar preparação.');
            }
        }

        // ── Gerar Crise ──────────────────────────────────────────────
        async function generateCrisis() {
            const description = document.getElementById('crisis-description').value.trim();
            if (!description) {
                alert('Descreva a situação.');
                return;
            }
            showSkeleton();
            try {
                const data = await (await apiFetch('{{ route('mayor.content.crisis-response') }}', {
                    crisis_description: description
                })).json();
                renderTextResult(data.content, '🚨 Orientação de crise');
            } catch (e) {
                showError('Erro ao processar.');
            }
        }

        // ── Carregar histórico ────────────────────────────────────────
        async function loadContent(id) {
            try {
                const data = await (await fetch('/mayor/content/' + id, {
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json'
                    }
                })).json();
                if (data.type === 'imagem_instagram') {
                    const p = JSON.parse(data.content);
                    renderImageResults(p.prompts, p.design_tips, data.title);
                } else if (data.variations && data.variations.length) {
                    renderPostCard(data);
                } else {
                    renderTextResult(data.content, data.title);
                }
            } catch (e) {
                alert('Erro ao carregar.');
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // RENDER IMAGEM — sem mistura innerHTML+= / appendChild
        // ═══════════════════════════════════════════════════════════════
        function renderImageResults(prompts, tips, theme) {
            const panel = clearResults();

            // Título
            const hdr = document.createElement('div');
            hdr.className = 'img-results-header';
            hdr.innerHTML = '<h3>Prompts de imagem gerados</h3><span>' + prompts.length +
            ' op\u00e7\u00f5es criadas</span>';
            panel.appendChild(hdr);

            // Banner CTAs
            const banner = document.createElement('div');
            banner.className = 'tools-banner';
            const bannerLabel = document.createElement('span');
            bannerLabel.className = 'tools-banner-label';
            bannerLabel.textContent = 'Cole o prompt em:';
            banner.appendChild(bannerLabel);
            [{
                    label: 'DALL-E 3',
                    cls: 'dalle',
                    url: 'https://chatgpt.com',
                    icon: '\uD83E\uDD16'
                },
                {
                    label: 'Midjourney',
                    cls: 'midj',
                    url: 'https://www.midjourney.com/app/',
                    icon: '\uD83C\uDFA8'
                },
                {
                    label: 'Canva AI',
                    cls: 'canva',
                    url: 'https://www.canva.com/ai-image-generator/',
                    icon: '\u270F\uFE0F'
                },
                {
                    label: 'Ideogram',
                    cls: 'ideogram',
                    url: 'https://ideogram.ai/',
                    icon: '\uD83D\uDDBC\uFE0F'
                },
            ].forEach(function(t) {
                const btn = document.createElement('button');
                btn.className = 'tool-btn ' + t.cls;
                btn.textContent = t.icon + ' ' + t.label;
                btn.type = 'button';
                btn.onclick = function() {
                    window.open(t.url, '_blank', 'noopener,noreferrer');
                };
                banner.appendChild(btn);
            });
            panel.appendChild(banner);

            // Um card por opção de prompt
            prompts.forEach(function(p, i) {
                var ptId = 'pt-' + i;
                var pnId = 'pn-' + i;
                var pcId = 'pc-' + i;
                var phId = 'ph-' + i;

                // Monta o HTML do card inteiro de uma vez — sem misturar appendChild depois
                var card = document.createElement('div');
                card.className = 'prompt-card';
                card.innerHTML =
                    // — Header —
                    '<div class="prompt-card-header">' +
                    '<div class="prompt-num">' + (i + 1) + '</div>' +
                    '<div class="prompt-label">' + esc(p.label || 'Op\u00e7\u00e3o ' + (i + 1)) + '</div>' +
                    '<span class="prompt-badge">Pronto para usar</span>' +
                    '</div>' +

                    // — Instrucao simples no topo —
                    '<div class="prompt-how-to">' +
                    '<span class="prompt-how-to-icon">\uD83D\uDCCB</span>' +
                    '<span>Copie o prompt e cole no DALL-E 3, Midjourney, Canva AI ou Ideogram para gerar a imagem</span>' +
                    '</div>' +

                    // — Corpo —
                    '<div class="prompt-card-body">' +

                    // Descri\u00e7\u00e3o
                    '<div>' +
                    '<div class="section-label">\uD83D\uDCDD O que a imagem mostrar\u00e1</div>' +
                    '<div class="description-box"><div class="description-box-text">' + esc(p.description || '') +
                    '</div></div>' +
                    '</div>' +

                    // Prompt principal
                    '<div>' +
                    '<div class="section-label">\uD83E\uDD16 Prompt para a IA \u2014 copie e cole na ferramenta</div>' +
                    '<div class="prompt-box">' +
                    '<div class="prompt-box-label">PROMPT (em ingl\u00eas)</div>' +
                    '<div class="prompt-box-text" id="' + ptId + '">' + esc(p.prompt || '') + '</div>' +
                    '<button type="button" class="prompt-copy-btn" onclick="copyById(\'' + ptId +
                    '\',\'\u2713 Prompt copiado!\')">' +
                    '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>' +
                    'Copiar' +
                    '</button>' +
                    '</div>' +
                    '</div>' +

                    // Negative prompt
                    '<div>' +
                    '<div class="section-label">\uD83D\uDEAB Negative Prompt (o que a imagem N\u00c3O deve ter)</div>' +
                    '<div class="negative-box"><div class="negative-box-text" id="' + pnId + '">' + esc(p
                        .negative_prompt || 'text, words, letters, numbers, watermark, blurry, low quality') +
                    '</div></div>' +
                    '</div>' +

                    // Legenda
                    '<div>' +
                    '<div class="section-label">\uD83D\uDCE3 Legenda sugerida para o post</div>' +
                    '<div class="caption-box"><div class="caption-text-content" id="' + pcId + '">' + esc(p
                        .caption_suggestion || '') + '</div></div>' +
                    '</div>' +

                    // Hashtags
                    '<div>' +
                    '<div class="section-label">\uD83C\uDFF7\uFE0F Hashtags</div>' +
                    '<div class="hashtag-box"><div class="hashtag-text-content" id="' + phId + '">' + esc(p
                        .hashtags || '') + '</div></div>' +
                    '</div>' +

                    '</div>' + // fim .prompt-card-body

                    // — A\u00e7\u00f5es —
                    '<div class="prompt-actions">' +
                    '<button type="button" class="action-btn" onclick="copyById(\'' + pnId +
                    '\',\'Negative copiado!\')">' +
                    '<svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>' +
                    'Negative' +
                    '</button>' +
                    '<button type="button" class="action-btn" onclick="copyById(\'' + pcId +
                    '\',\'Legenda copiada!\')">' +
                    '<svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>' +
                    'Legenda' +
                    '</button>' +
                    '<button type="button" class="action-btn" onclick="copyById(\'' + phId +
                    '\',\'Hashtags copiadas!\')">' +
                    '<svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px"><path d="M17.63 5.84C17.27 5.33 16.67 5 16 5L5 5.01C3.9 5.01 3 5.9 3 7v10c0 1.1.9 1.99 2 1.99L16 19c.67 0 1.27-.33 1.63-.84L22 12l-4.37-6.16z"/></svg>' +
                    'Hashtags' +
                    '</button>' +
                    '<button type="button" class="copy-all-btn" onclick="copyAll(' + i + ')">' +
                    '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>' +
                    'Copiar Tudo' +
                    '</button>' +
                    '</div>';

                panel.appendChild(card);
            });

            // Design tips
            if (tips && tips.length) {
                const tipsEl = document.createElement('div');
                tipsEl.className = 'design-tips';
                tipsEl.innerHTML =
                    '<h4><svg width="14" height="14" viewBox="0 0 24 24" fill="#d97706"><path d="M9 21c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7z"/></svg>Dicas de design</h4>' +
                    '<ul>' + tips.map(function(t) {
                        return '<li>' + esc(t) + '</li>';
                    }).join('') + '</ul>';
                panel.appendChild(tipsEl);
            }
        }

        // Copiar tudo de um card (prompt + legenda + hashtags)
        function copyAll(i) {
            var prompt = document.getElementById('pt-' + i)?.innerText || '';
            var negative = document.getElementById('pn-' + i)?.innerText || '';
            var caption = document.getElementById('pc-' + i)?.innerText || '';
            var hashtags = document.getElementById('ph-' + i)?.innerText || '';
            var full = 'PROMPT:\n' + prompt + '\n\nNEGATIVE PROMPT:\n' + negative + '\n\nLEGENDA:\n' + caption +
                '\n\nHASHTAGS:\n' + hashtags;
            navigator.clipboard.writeText(full);
            showToast('✓ Tudo copiado!');
        }

        // ── Render: post ──────────────────────────────────────────────
        function renderPostCard(data) {
            const panel = clearResults();
            const variations = data.variations || [{
                tone: 'geral',
                content: data.content
            }];
            const hdr = document.createElement('div');
            hdr.style.cssText = 'display:flex;align-items:center;justify-content:space-between';
            hdr.innerHTML = '<h3 style="font-family:\'Lora\',serif;font-size:1rem;color:var(--ink)">' + esc(data.title ||
                'Conteúdo gerado') + '</h3>';
            panel.appendChild(hdr);
            const card = document.createElement('div');
            card.className = 'content-card';
            const channelKey = data.channel || 'instagram';
            card.innerHTML =
                '<div class="content-card-header">' +
                '<div class="channel-icon ' + channelKey + '">' + channelKey.slice(0, 2).toUpperCase() + '</div>' +
                '<div class="content-card-meta">' +
                '<div class="content-card-title">' + channelKey.charAt(0).toUpperCase() + channelKey.slice(1) + '</div>' +
                '<div class="content-card-info">' + variations.length + ' variação' + (variations.length > 1 ? 'ões' : '') +
                '</div>' +
                '</div>' +
                '<span class="content-card-status status-draft">Rascunho</span>' +
                '</div>' +
                '<div class="variation-tabs">' +
                variations.map(function(v, i) {
                    return '<button class="var-tab ' + (i === 0 ? 'active' : '') + '" onclick="switchVar(this,\'var-' +
                        i + '\')">' + ucFirst(v.tone) + '</button>';
                }).join('') +
                '</div>' +
                variations.map(function(v, i) {
                    return '<div id="var-' + i + '" class="variation-content" style="' + (i > 0 ? 'display:none' : '') +
                        '">' +
                        '<p class="post-text" id="tv-' + i + '">' + esc(v.content) + '</p></div>';
                }).join('') +
                '<div class="content-card-actions">' +
                '<button class="action-btn" onclick="copyActivePost()">Copiar</button>' +
                '<button class="action-btn" onclick="editActivePost()">Editar</button>' +
                '</div>';
            panel.appendChild(card);
        }

        let activeVar = 'var-0';

        function switchVar(btn, id) {
            document.querySelectorAll('.var-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.variation-content').forEach(v => v.style.display = 'none');
            document.getElementById(id).style.display = 'block';
            activeVar = id;
        }

        function copyActivePost() {
            const el = document.querySelector('#' + activeVar + ' .post-text');
            if (el) {
                navigator.clipboard.writeText(el.innerText);
                showToast('✓ Post copiado!');
            }
        }

        function editActivePost() {
            const el = document.querySelector('#' + activeVar + ' .post-text');
            if (el) {
                el.contentEditable = 'true';
                el.focus();
            }
        }

        // ── Render: texto ─────────────────────────────────────────────
        function renderTextResult(content, title) {
            const panel = clearResults();
            const el = document.createElement('div');
            el.className = 'crisis-result';
            el.innerHTML =
                '<h4><svg viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13z"/></svg>' +
                esc(title) + '</h4>' +
                '<div class="crisis-result-body" id="crbody">' + esc(content) + '</div>' +
                '<div style="margin-top:1rem"><button class="action-btn" onclick="copyById(\'crbody\',\'Copiado!\')">Copiar</button></div>';
            panel.appendChild(el);
        }

        // ── Skeletons ─────────────────────────────────────────────────
        function showSkeleton() {
            clearResults().innerHTML =
                '<div style="background:var(--white);border:1px solid var(--border);border-radius:12px;padding:1.2rem">' +
                '<div style="display:flex;gap:.75rem;margin-bottom:1rem"><div class="skel" style="width:30px;height:30px;border-radius:7px"></div>' +
                '<div style="flex:1"><div class="skel" style="height:12px;width:60%;margin-bottom:.4rem"></div><div class="skel" style="height:10px;width:40%"></div></div></div>' +
                '<div class="skel" style="height:11px;margin-bottom:.5rem"></div>' +
                '<div class="skel" style="height:11px;width:85%;margin-bottom:.5rem"></div>' +
                '<div class="skel" style="height:11px;width:70%"></div></div>';
        }

        function showImageSkeleton() {
            var html = '';
            for (var x = 0; x < 3; x++) {
                html +=
                    '<div style="background:var(--white);border:1.5px solid #ede9fe;border-radius:14px;overflow:hidden">' +
                    '<div style="height:80px;background:linear-gradient(135deg,#1e1b4b,#4c1d95);display:flex;align-items:center;justify-content:center">' +
                    '<div class="skel" style="width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,.2)"></div>' +
                    '</div>' +
                    '<div style="padding:1rem">' +
                    '<div class="skel" style="height:13px;width:70%;margin-bottom:.75rem;background:#f5f3ff"></div>' +
                    '<div class="skel" style="height:60px;border-radius:8px;background:#f5f3ff;margin-bottom:.75rem"></div>' +
                    '<div class="skel" style="height:11px;width:80%;background:#f5f3ff;margin-bottom:.4rem"></div>' +
                    '<div class="skel" style="height:11px;width:60%;background:#f5f3ff"></div>' +
                    '</div></div>';
            }
            html += '<div style="background:#f5f3ff;border-radius:10px;padding:.9rem;text-align:center">' +
                '<p style="font-size:.83rem;color:#7c3aed;font-weight:500;margin:0">✨ A IA está criando os prompts... aguarde alguns segundos</p></div>';
            clearResults().innerHTML = html;
        }

        function showError(msg) {
            clearResults().innerHTML =
                '<div style="background:#fdf0f0;border:1.5px solid #f5c6c2;border-radius:12px;padding:1.4rem;text-align:center">' +
                '<p style="font-size:.87rem;color:#b52b2b;margin:0">' + esc(msg) + '</p></div>';
        }

        // ── Utils ─────────────────────────────────────────────────────
        function clearResults() {
            var p = document.getElementById('resultsPanel');
            p.innerHTML = '';
            return p;
        }

        function copyById(id, msg) {
            var el = document.getElementById(id);
            if (!el) return;
            navigator.clipboard.writeText(el.innerText).then(function() {
                showToast(msg || '✓ Copiado!');
            });
        }

        function showToast(msg) {
            var existing = document.querySelector('.copy-toast');
            if (existing) existing.remove();
            var t = document.createElement('div');
            t.className = 'copy-toast';
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(function() {
                t.remove();
            }, 2400);
        }
        async function apiFetch(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(body),
            });
        }

        function esc(t) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(t || ''));
            return d.innerHTML;
        }

        function ucFirst(s) {
            return s ? s.charAt(0).toUpperCase() + s.slice(1) : s;
        }
    </script>
@endpush
