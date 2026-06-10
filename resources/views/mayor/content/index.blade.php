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
            font-family: "Outfit", sans-serif;
            font-size: 1rem;
            color: var(--ink);
            margin-bottom: .15rem;
        }

        .panel-header p {
            font-size: .78rem;
            color: var(--ink-muted);
        }

        .summary-mini-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: .45rem;
            margin-top: .9rem;
        }

        .summary-mini-card {
            border: 1px solid var(--border-lt);
            border-radius: 10px;
            padding: .55rem .6rem;
            background: var(--surface);
        }

        .summary-mini-card .label {
            font-size: .62rem;
            text-transform: uppercase;
            color: var(--ink-muted);
            letter-spacing: .08em;
            font-weight: 700;
        }

        .summary-mini-card .value {
            font-size: .95rem;
            font-weight: 800;
            color: var(--ink);
            margin-top: .15rem;
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
            font-family: "Inter", sans-serif;
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
            font-family: "Inter", sans-serif;
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
            font-family: "Inter", sans-serif;
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
            font-family: "Inter", sans-serif;
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
            font-family: "Outfit", sans-serif;
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

        .status-approved {
            background: #ecfdf5;
            color: #047857;
        }

        .status-published {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .status-archived {
            background: #f3f4f6;
            color: #6b7280;
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
            font-family: "Inter", sans-serif;
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
            font-family: "Inter", sans-serif;
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
            font-family: "Outfit", sans-serif;
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

        .assist-card {
            background: linear-gradient(180deg, #fffdf8 0%, #ffffff 100%);
            border: 1px solid #f1e4b2;
            border-radius: 14px;
            padding: 1rem 1.05rem;
        }

        .assist-head {
            display: flex;
            justify-content: space-between;
            gap: .8rem;
            align-items: flex-start;
            margin-bottom: .8rem;
        }

        .assist-title {
            font-size: .9rem;
            font-weight: 700;
            color: var(--ink);
            margin: 0;
        }

        .assist-subtitle {
            font-size: .76rem;
            color: var(--ink-muted);
            line-height: 1.55;
            margin: .2rem 0 0;
        }

        .assist-badge {
            border-radius: 999px;
            background: #fff7dd;
            color: #8a6a00;
            font-size: .68rem;
            font-weight: 700;
            padding: .28rem .55rem;
            white-space: nowrap;
        }

        .assist-grid {
            display: grid;
            grid-template-columns: 180px 160px 1fr;
            gap: .75rem;
            align-items: end;
        }

        .assist-meta {
            margin-top: .75rem;
            font-size: .74rem;
            color: var(--ink-muted);
            line-height: 1.55;
        }

        .assist-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            margin-top: .85rem;
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
            font-family: "Outfit", sans-serif;
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
            font-family: "Inter", sans-serif;
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
            font-family: "Inter", sans-serif;
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

        .comm-page {
            padding: 1.4rem 1.6rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            background: #f6f7fb;
            min-height: calc(100vh - var(--nav-h));
        }

        .module-shell-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, .04);
            padding: 1rem 1.1rem;
        }

        .module-shell-tabs {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem 1rem;
            flex-wrap: wrap;
        }

        .module-shell-tab-group {
            display: flex;
            gap: .55rem;
            flex-wrap: wrap;
        }

        .module-shell-tab-group.is-right {
            margin-left: auto;
            justify-content: flex-end;
        }

        .module-shell-tab {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .58rem .9rem;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--ink-soft);
            text-decoration: none;
            font-size: .82rem;
            font-weight: 700;
        }

        .module-shell-tab.is-active {
            background: #111827;
            color: #fff;
            border-color: #111827;
        }

        .module-shell-tab.is-soon {
            background: #f8fafc;
        }

        .comm-hero,
        .workspace-grid,
        .calendar-grid,
        .results-shell {
            width: 100%;
        }

        .hero-card,
        .workspace-card,
        .queue-card,
        .calendar-card,
        .mix-card,
        .results-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, .04);
        }

        .hero-card {
            padding: 1.35rem 1.4rem;
            background: linear-gradient(135deg, #fffdf6 0%, #ffffff 55%, #f8fafc 100%);
        }

        .hero-top {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: flex-start;
        }

        .hero-title h1 {
            font-family: "Outfit", sans-serif;
            font-size: 1.45rem;
            color: var(--ink);
            margin: 0 0 .35rem;
        }

        .hero-title p {
            margin: 0;
            color: var(--ink-muted);
            font-size: .93rem;
            max-width: 760px;
            line-height: 1.65;
        }

        .hero-badge {
            background: #111827;
            color: white;
            border-radius: 999px;
            padding: .45rem .8rem;
            font-size: .75rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .hero-summary-grid {
            margin-top: 1rem;
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: .8rem;
        }

        .module-area-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(300px, .85fr);
            gap: 1.1rem;
            align-items: start;
        }

        .archive-list,
        .mention-list-embedded {
            display: flex;
            flex-direction: column;
            gap: .8rem;
        }

        .archive-item {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: .95rem 1rem;
            cursor: pointer;
        }

        .archive-item:hover {
            border-color: var(--gold);
        }

        .archive-item-head,
        .mention-item-head {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            align-items: flex-start;
        }

        .archive-item-title {
            font-size: .9rem;
            font-weight: 700;
            color: var(--ink);
        }

        .archive-item-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin-top: .35rem;
            font-size: .76rem;
            color: var(--ink-muted);
        }

        .archive-item-note {
            margin-top: .45rem;
            font-size: .77rem;
            color: var(--ink-soft);
            line-height: 1.55;
        }

        .archive-item-actions {
            display: flex;
            gap: .45rem;
            flex-wrap: wrap;
            margin-top: .75rem;
        }

        .archive-memory-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .archive-version-list {
            display: flex;
            flex-direction: column;
            gap: .55rem;
            margin-top: .8rem;
        }

        .archive-version-item {
            background: #fbfbfd;
            border: 1px solid var(--border-lt);
            border-radius: 12px;
            padding: .72rem .8rem;
        }

        .archive-version-item strong {
            display: block;
            font-size: .8rem;
            color: var(--ink);
        }

        .archive-version-item p {
            margin: .22rem 0 0;
            font-size: .74rem;
            color: var(--ink-muted);
            line-height: 1.5;
        }

        .archive-toolbar-form {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: .8rem;
            align-items: end;
        }

        .module-placeholder {
            padding: 1.3rem 1.35rem;
            border: 1px dashed var(--border);
            border-radius: 16px;
            background: #fcfcfd;
        }

        .module-placeholder h3 {
            font-size: 1rem;
            color: var(--ink);
            margin: 0 0 .45rem;
        }

        .module-placeholder p {
            font-size: .84rem;
            color: var(--ink-muted);
            line-height: 1.6;
            margin: 0;
        }

        .mention-grid-top {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(320px, .9fr);
            gap: 1rem;
        }

        .mention-toolbar-form {
            display: grid;
            grid-template-columns: 180px 200px 160px auto;
            gap: .8rem;
            align-items: end;
        }

        .mention-metric-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: .75rem;
        }

        .mention-metric-card {
            background: #fbfbfc;
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            padding: .9rem .95rem;
        }

        .mention-metric-card .label {
            font-size: .67rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ink-muted);
            font-weight: 700;
        }

        .mention-metric-card .value {
            margin-top: .25rem;
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--ink);
        }

        .mention-reputation-bar {
            display: flex;
            width: 100%;
            height: 16px;
            border-radius: 999px;
            overflow: hidden;
            background: #eef2f7;
            border: 1px solid var(--border);
        }

        .mention-reputation-segment {
            height: 100%;
        }

        .mention-reputation-legend {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .7rem;
            margin-top: .85rem;
        }

        .mention-reputation-item {
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
            padding: .75rem .8rem;
        }

        .mention-reputation-item strong {
            display: block;
            font-size: .95rem;
            color: var(--ink);
        }

        .mention-reputation-item span {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            margin-top: .2rem;
            font-size: .74rem;
            color: var(--ink-muted);
        }

        .mention-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .mention-item {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: .95rem 1rem;
        }

        .mention-item.is-highlighted,
        .operations-card.is-highlighted {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(184, 144, 42, .16);
        }

        .mention-item.unread {
            border-left: 3px solid var(--gold);
        }

        .mention-item-title {
            font-size: .9rem;
            font-weight: 700;
            color: var(--ink);
        }

        .mention-item-copy {
            margin-top: .35rem;
            font-size: .82rem;
            color: var(--ink-soft);
            line-height: 1.55;
        }

        .mention-item-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin-top: .45rem;
            font-size: .74rem;
            color: var(--ink-muted);
        }

        .mention-item-actions {
            display: flex;
            gap: .45rem;
            flex-wrap: wrap;
            margin-top: .7rem;
        }

        .mention-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: .2rem .55rem;
            font-weight: 700;
            font-size: .7rem;
        }

        .operations-summary-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: .75rem;
        }

        .operations-summary-card {
            background: #fbfbfc;
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            padding: .9rem .95rem;
        }

        .operations-summary-card .label {
            font-size: .66rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ink-muted);
            font-weight: 700;
        }

        .operations-summary-card .value {
            margin-top: .28rem;
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--ink);
        }

        .operations-summary-card .meta {
            margin-top: .25rem;
            font-size: .74rem;
            color: var(--ink-muted);
            line-height: 1.45;
        }

        .operations-toolbar-form {
            display: grid;
            grid-template-columns: 200px 220px 140px 140px 1fr auto;
            gap: .8rem;
            align-items: end;
        }

        .operations-board-shell {
            overflow-x: auto;
            padding-bottom: .2rem;
        }

        .operations-board {
            display: grid;
            grid-template-columns: repeat(5, minmax(240px, 1fr));
            gap: 1rem;
            min-width: 1200px;
        }

        .operations-column {
            background: #fbfbfd;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: .95rem;
        }

        .operations-column-head {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            align-items: flex-start;
            margin-bottom: .85rem;
        }

        .operations-column-title {
            font-family: "Outfit", sans-serif;
            font-size: .98rem;
            color: var(--ink);
            margin: 0;
        }

        .operations-column-copy {
            margin-top: .18rem;
            font-size: .74rem;
            color: var(--ink-muted);
            line-height: 1.5;
        }

        .operations-column-total {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            border-radius: 999px;
            background: #111827;
            color: #fff;
            font-size: .8rem;
            font-weight: 700;
        }

        .operations-column-list {
            display: flex;
            flex-direction: column;
            gap: .8rem;
        }

        .operations-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: .9rem;
            box-shadow: 0 4px 14px rgba(15, 23, 42, .03);
        }

        .operations-card.is-overdue {
            border-color: #fecaca;
            background: #fffafa;
        }

        .operations-card-head {
            display: flex;
            justify-content: space-between;
            gap: .6rem;
            align-items: flex-start;
        }

        .operations-card-title {
            font-size: .88rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.45;
        }

        .operations-card-copy {
            margin-top: .35rem;
            font-size: .76rem;
            color: var(--ink-muted);
            line-height: 1.52;
        }

        .operations-chip-row,
        .operations-meta-row,
        .operations-action-row {
            display: flex;
            flex-wrap: wrap;
            gap: .42rem;
            margin-top: .6rem;
        }

        .operations-chip,
        .operations-meta-chip {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: .22rem .55rem;
            font-size: .68rem;
            font-weight: 700;
        }

        .operations-meta-chip {
            background: #f8fafc;
            color: var(--ink-soft);
            border: 1px solid var(--border-lt);
            font-weight: 600;
        }

        .operations-card-hint {
            margin-top: .6rem;
            padding: .65rem .72rem;
            border-radius: 10px;
            background: #fffdf6;
            border: 1px solid #f6e6a7;
            font-size: .74rem;
            color: #8a6b10;
            line-height: 1.5;
        }

        .ops-stack {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .ops-stack-list {
            display: flex;
            flex-direction: column;
            gap: .72rem;
        }

        .ops-mini-item {
            padding: .82rem .88rem;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
        }

        .ops-mini-item strong {
            display: block;
            color: var(--ink);
            font-size: .84rem;
            line-height: 1.45;
        }

        .ops-mini-item p {
            margin: .3rem 0 0;
            font-size: .75rem;
            color: var(--ink-muted);
            line-height: 1.5;
        }

        .ops-mini-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .42rem;
            margin-top: .45rem;
            font-size: .72rem;
            color: var(--ink-muted);
        }

        .hero-summary-card {
            padding: .95rem 1rem;
            border-radius: 14px;
            background: #fbfbfc;
            border: 1px solid var(--border-lt);
        }

        .hero-summary-card .label {
            font-size: .67rem;
            color: var(--ink-muted);
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 700;
        }

        .hero-summary-card .value {
            margin-top: .3rem;
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--ink);
        }

        .hero-summary-card .meta {
            margin-top: .25rem;
            font-size: .76rem;
            color: var(--ink-muted);
            line-height: 1.5;
        }

        .toolbar-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1rem 1.1rem;
        }

        .toolbar-form {
            display: grid;
            grid-template-columns: 180px 180px 1fr auto;
            gap: .8rem;
            align-items: end;
        }

        .toolbar-actions {
            display: flex;
            gap: .55rem;
            align-items: center;
        }

        .workspace-grid {
            display: grid;
            grid-template-columns: 1;
            gap: 1.1rem;
            align-items: start;
        }

        .workspace-card {
            padding: 1.15rem 1.2rem 1.25rem;
        }

        .workspace-card-head,
        .queue-card-head,
        .calendar-card-head,
        .mix-card-head,
        .results-card-head {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1rem;
        }

        .section-title {
            font-family: "Outfit", sans-serif;
            font-size: 1.02rem;
            color: var(--ink);
            margin: 0;
        }

        .section-subtitle {
            color: var(--ink-muted);
            font-size: .8rem;
            margin: .2rem 0 0;
        }

        .queue-card,
        .calendar-card,
        .mix-card,
        .results-card,
        .template-card {
            background: var(--white);
            border: 1px solid var(--border-lt);
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .04);
            padding: 1.05rem 1.15rem 1.15rem;
        }

        .queue-list {
            display: flex;
            flex-direction: column;
            gap: .75rem;
            max-height: 760px;
            overflow: auto;
            padding-right: .15rem;
        }

        .queue-item {
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            padding: .85rem .9rem;
            background: #fbfbfc;
            cursor: pointer;
            transition: border-color .15s, transform .15s, box-shadow .15s;
        }

        .queue-item:hover {
            border-color: #d4af37;
            transform: translateY(-1px);
            box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
        }

        .queue-item-top {
            display: flex;
            justify-content: space-between;
            gap: .65rem;
            align-items: flex-start;
        }

        .queue-item-title {
            font-size: .88rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.45;
        }

        .queue-item-meta,
        .queue-item-foot {
            margin-top: .35rem;
            font-size: .76rem;
            color: var(--ink-muted);
            line-height: 1.6;
        }

        .queue-item-badges {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: .35rem;
        }

        .queue-item-sla {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            align-items: center;
            margin-top: .45rem;
        }

        .queue-empty,
        .calendar-empty {
            padding: .5rem;
            text-align: center;
            color: var(--ink-muted);
            font-size: .7rem;
            border: 1px dashed var(--border);
            border-radius: 12px;
            background: var(--surface);
        }

        .template-select-meta {
            margin-top: .55rem;
            padding: .7rem .8rem;
            border-radius: 12px;
            background: #faf7ef;
            border: 1px solid #eadfb5;
            font-size: .78rem;
            color: #6b5a1e;
            line-height: 1.55;
        }

        .template-format-select {
            margin-top: .55rem;
        }

        .template-library-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .8rem;
            margin-top: 1rem;
        }

        .template-item {
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            background: #fcfcfd;
            padding: .85rem .9rem;
            min-height: 100%;
        }

        .template-item-top,
        .template-item-actions {
            display: flex;
            justify-content: space-between;
            gap: .6rem;
            align-items: flex-start;
        }

        .template-item-title {
            font-size: .88rem;
            font-weight: 700;
            color: var(--ink);
        }

        .template-item-meta,
        .template-item-desc {
            margin-top: .28rem;
            font-size: .76rem;
            color: var(--ink-muted);
            line-height: 1.6;
        }

        .template-builder-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }

        .template-builder-grid .field:last-child,
        .template-builder-grid .field.full {
            grid-column: 1 / -1;
        }

        .template-builder-actions {
            display: flex;
            gap: .6rem;
            margin-top: .15rem;
        }

        .playbooks-shell {
            margin-top: 1rem;
        }

        .playbook-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .85rem;
        }

        .playbook-card {
            border: 1px solid var(--border-lt);
            border-radius: 16px;
            background: linear-gradient(180deg, #fcfcfd 0%, #f8fafc 100%);
            padding: .95rem 1rem;
            min-height: 100%;
        }

        .playbook-card-top {
            display: flex;
            justify-content: space-between;
            gap: .7rem;
            align-items: flex-start;
        }

        .playbook-card-title {
            font-size: .9rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.4;
        }

        .playbook-card-meta,
        .playbook-card-desc {
            margin-top: .3rem;
            font-size: .76rem;
            color: var(--ink-muted);
            line-height: 1.6;
        }

        .playbook-chip-row,
        .playbook-checklist {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin-top: .7rem;
        }

        .playbook-chip {
            display: inline-flex;
            align-items: center;
            padding: .32rem .55rem;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid var(--border-lt);
            color: var(--ink-soft);
            font-size: .7rem;
            font-weight: 700;
        }

        .playbook-checklist span {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .26rem .48rem;
            border-radius: 999px;
            background: #eef2ff;
            color: #4338ca;
            font-size: .7rem;
            font-weight: 600;
        }

        .playbook-actions {
            display: flex;
            gap: .55rem;
            margin-top: .85rem;
        }

        .sla-section,
        .templates-shell,
        .performance-section {
            margin-top: 1rem;
        }

        .sla-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .8rem;
            margin-bottom: 1rem;
        }

        .sla-summary-card {
            border: 1px solid var(--border-lt);
            border-radius: 16px;
            background: #fbfbfc;
            padding: .95rem 1rem;
        }

        .sla-summary-card .label {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ink-muted);
            font-weight: 700;
        }

        .sla-summary-card .value {
            margin-top: .22rem;
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--ink);
        }

        .sla-summary-card .meta {
            margin-top: .28rem;
            font-size: .76rem;
            line-height: 1.55;
            color: var(--ink-muted);
        }

        .sla-summary-card.status-on_track {
            background: linear-gradient(180deg, #f7fcf8 0%, #fcfcfd 100%);
            border-color: #bbf7d0;
        }

        .sla-summary-card.status-at_risk {
            background: linear-gradient(180deg, #fffaf2 0%, #fcfcfd 100%);
            border-color: #fed7aa;
        }

        .sla-summary-card.status-overdue {
            background: linear-gradient(180deg, #fff7f7 0%, #fcfcfd 100%);
            border-color: #fecaca;
        }

        .sla-summary-card.status-complete {
            background: linear-gradient(180deg, #f8fafc 0%, #fcfcfd 100%);
            border-color: #cbd5e1;
        }

        .sla-config-badges {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            justify-content: flex-end;
        }

        .sla-config-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .38rem .62rem;
            border-radius: 999px;
            border: 1px solid var(--border-lt);
            background: #f8fafc;
            color: var(--ink-soft);
            font-size: .72rem;
            font-weight: 700;
        }

        .sla-stage-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .sla-stage-card {
            border: 1px solid var(--border-lt);
            border-left-width: 5px;
            border-radius: 16px;
            background: #fcfcfd;
            padding: .95rem 1rem;
        }

        .sla-stage-card.status-on_track {
            border-left-color: #16a34a;
            background: linear-gradient(180deg, #f7fcf8 0%, #fcfcfd 100%);
        }

        .sla-stage-card.status-at_risk {
            border-left-color: #d97706;
            background: linear-gradient(180deg, #fffaf2 0%, #fcfcfd 100%);
        }

        .sla-stage-card.status-overdue {
            border-left-color: #dc2626;
            background: linear-gradient(180deg, #fff7f7 0%, #fcfcfd 100%);
        }

        .sla-stage-top,
        .sla-stage-metrics,
        .sla-item-head,
        .sla-item-meta,
        .workflow-sla-top,
        .workflow-sla-meta {
            display: flex;
            justify-content: space-between;
            gap: .65rem;
            align-items: baseline;
        }

        .sla-stage-metrics {
            margin-top: .8rem;
            flex-wrap: wrap;
        }

        .sla-metric {
            min-width: 84px;
        }

        .sla-metric .metric-label {
            font-size: .66rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ink-muted);
            font-weight: 700;
        }

        .sla-metric .metric-value {
            margin-top: .16rem;
            font-size: .95rem;
            font-weight: 800;
            color: var(--ink);
        }

        .sla-item-list {
            display: flex;
            flex-direction: column;
            gap: .7rem;
            margin-top: .9rem;
        }

        .sla-item {
            border: 1px solid var(--border-lt);
            border-radius: 12px;
            background: #fff;
            padding: .7rem .75rem;
            cursor: pointer;
            transition: border-color .15s, transform .15s, box-shadow .15s;
        }

        .sla-item:hover {
            border-color: #d4af37;
            transform: translateY(-1px);
            box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
        }

        .sla-item-title {
            font-size: .82rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.45;
        }

        .sla-item-meta {
            margin-top: .25rem;
            font-size: .72rem;
            color: var(--ink-muted);
        }

        .sla-critical-shell {
            border-top: 1px solid var(--border-lt);
            padding-top: 1rem;
        }

        .sla-critical-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .85rem;
        }

        .sla-critical-item {
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            background: #fcfcfd;
            padding: .9rem .95rem;
            cursor: pointer;
            transition: border-color .15s, transform .15s, box-shadow .15s;
        }

        .sla-critical-item:hover {
            border-color: #d4af37;
            transform: translateY(-1px);
            box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
        }

        .sla-critical-item.is-overdue {
            border-color: #fecaca;
            background: #fff7f7;
        }

        .sla-critical-item.is-at-risk {
            border-color: #fed7aa;
            background: #fffaf2;
        }

        .sla-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .3rem .58rem;
            font-size: .7rem;
            font-weight: 700;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .sla-badge.sla-status-on_track {
            background: #ecfdf3;
            color: #166534;
            border-color: #bbf7d0;
        }

        .sla-badge.sla-status-at_risk {
            background: #fff7ed;
            color: #9a3412;
            border-color: #fed7aa;
        }

        .sla-badge.sla-status-overdue {
            background: #fef2f2;
            color: #991b1b;
            border-color: #fecaca;
        }

        .sla-badge.sla-status-complete {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }

        .workflow-sla {
            margin: 0 1.2rem .9rem;
            padding: .85rem .9rem;
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            background: #fbfbfc;
        }

        .workflow-sla-stage {
            font-size: .76rem;
            font-weight: 700;
            color: var(--ink-soft);
        }

        .workflow-sla-meta {
            margin-top: .35rem;
            font-size: .74rem;
            color: var(--ink-muted);
            flex-wrap: wrap;
        }

        .workflow-playbook {
            margin: 0 1.2rem .9rem;
            padding: .85rem .9rem;
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: linear-gradient(180deg, #f8fbff 0%, #fbfdff 100%);
        }

        .workflow-playbook-top {
            display: flex;
            justify-content: space-between;
            gap: .6rem;
            align-items: flex-start;
        }

        .workflow-playbook-title {
            font-size: .82rem;
            font-weight: 800;
            color: #1e3a8a;
        }

        .workflow-playbook-meta {
            margin-top: .35rem;
            font-size: .74rem;
            color: var(--ink-muted);
            line-height: 1.6;
        }

        .workflow-playbook-list {
            margin-top: .55rem;
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
        }

        .workflow-playbook-list span {
            display: inline-flex;
            align-items: center;
            padding: .28rem .48rem;
            border-radius: 999px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            font-size: .68rem;
            font-weight: 700;
        }

        .template-shell-grid {
            display: grid;
            grid-template-columns: minmax(320px, .8fr) minmax(0, 1.2fr);
            gap: 1rem;
            align-items: start;
        }

        .template-library-panel {
            border-left: 1px solid var(--border-lt);
            padding-left: 1rem;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.1rem;
            align-items: start;
        }

        .calendar-board {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: .75rem;
        }

        .calendar-day {
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            background: #fcfcfd;
            padding: .75rem;
            min-height: 150px;
        }

        .calendar-day-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: .6rem;
        }

        .calendar-day-label {
            font-size: .74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ink-soft);
        }

        .calendar-day-date {
            font-size: .78rem;
            color: var(--ink-muted);
        }

        .calendar-entry {
            border-radius: 10px;
            padding: .55rem .6rem;
            background: var(--white);
            border: 1px solid var(--border-lt);
            margin-bottom: .5rem;
            cursor: pointer;
        }

        .calendar-entry-top {
            display: flex;
            justify-content: space-between;
            gap: .45rem;
            align-items: flex-start;
        }

        .calendar-entry-actions {
            display: flex;
            gap: .25rem;
            margin-top: .45rem;
        }

        .calendar-order-btn {
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--ink-soft);
            border-radius: 8px;
            padding: .22rem .45rem;
            font-size: .7rem;
            cursor: pointer;
        }

        .calendar-month-shell {
            margin-top: 1rem;
        }

        .month-header {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: baseline;
            margin-bottom: .85rem;
        }

        .month-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: .7rem;
        }

        .month-day {
            min-height: 140px;
            border-radius: 14px;
            border: 1px solid var(--border-lt);
            background: #fcfcfd;
            padding: .7rem;
        }

        .month-day.is-outside {
            opacity: .55;
            background: #f8fafc;
        }

        .month-day.is-today {
            border-color: #d4af37;
            box-shadow: inset 0 0 0 1px rgba(212, 175, 55, .18);
        }

        .month-day-top {
            display: flex;
            justify-content: space-between;
            gap: .45rem;
            align-items: center;
            margin-bottom: .55rem;
        }

        .month-day-number {
            font-size: .8rem;
            font-weight: 800;
            color: var(--ink);
        }

        .month-day-badge {
            font-size: .68rem;
            color: var(--ink-muted);
        }

        .month-entry {
            border-radius: 10px;
            border: 1px solid var(--border-lt);
            background: var(--white);
            padding: .42rem .5rem;
            margin-bottom: .4rem;
            cursor: pointer;
        }

        .month-entry-title {
            font-size: .74rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.4;
        }

        .month-entry-meta {
            font-size: .68rem;
            color: var(--ink-muted);
            margin-top: .15rem;
        }

        .performance-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .8rem;
            margin-bottom: 1rem;
        }

        .performance-summary-card {
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            background: #fbfbfc;
            padding: .85rem .95rem;
        }

        .performance-summary-card .label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ink-muted);
            font-weight: 700;
        }

        .performance-summary-card .value {
            margin-top: .2rem;
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--ink);
        }

        .performance-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .performance-card {
            border: 1px solid var(--border-lt);
            border-radius: 16px;
            background: #fcfcfd;
            padding: .95rem 1rem;
            border-left-width: 5px;
        }

        .performance-card.status-good {
            border-left-color: #16a34a;
            background: linear-gradient(180deg, #f7fcf8 0%, #fcfcfd 100%);
        }

        .performance-card.status-medium {
            border-left-color: #d97706;
            background: linear-gradient(180deg, #fffaf2 0%, #fcfcfd 100%);
        }

        .performance-card.status-low {
            border-left-color: #dc2626;
            background: linear-gradient(180deg, #fff7f7 0%, #fcfcfd 100%);
        }

        .performance-card-headline {
            display: flex;
            justify-content: space-between;
            gap: .8rem;
            align-items: flex-start;
            margin-bottom: .75rem;
        }

        .performance-status-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .35rem .6rem;
            font-size: .72rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .performance-status-badge.status-good {
            background: #ecfdf3;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .performance-status-badge.status-medium {
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fed7aa;
        }

        .performance-status-badge.status-low {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .performance-legend {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin-top: .65rem;
        }

        .performance-legend-item {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .35rem .55rem;
            border-radius: 999px;
            font-size: .72rem;
            color: var(--ink-muted);
            background: #f8fafc;
            border: 1px solid var(--border-lt);
        }

        .performance-legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            flex-shrink: 0;
        }

        .performance-legend-dot.good {
            background: #16a34a;
        }

        .performance-legend-dot.medium {
            background: #d97706;
        }

        .performance-legend-dot.low {
            background: #dc2626;
        }

        .performance-list {
            display: flex;
            flex-direction: column;
            gap: .7rem;
        }

        .performance-item {
            border-bottom: 1px solid var(--border-lt);
            padding-bottom: .6rem;
        }

        .performance-item.status-good {
            background: rgba(22, 163, 74, .035);
            border-radius: 12px;
            padding: .55rem .65rem .65rem;
        }

        .performance-item.status-medium {
            background: rgba(217, 119, 6, .04);
            border-radius: 12px;
            padding: .55rem .65rem .65rem;
        }

        .performance-item.status-low {
            background: rgba(220, 38, 38, .04);
            border-radius: 12px;
            padding: .55rem .65rem .65rem;
        }

        .collab-card {
            margin-top: .85rem;
            border: 1px solid var(--border-lt);
            border-radius: 16px;
            background: #fcfcfd;
            padding: 1rem 1.05rem;
        }

        .collab-head {
            display: flex;
            justify-content: space-between;
            gap: .8rem;
            align-items: center;
            margin-bottom: .85rem;
        }

        .collab-title {
            font-family: "Outfit", sans-serif;
            font-size: .98rem;
            color: var(--ink);
            margin: 0;
        }

        .collab-subtitle {
            color: var(--ink-muted);
            font-size: .78rem;
            margin: .2rem 0 0;
        }

        .collab-summary {
            display: flex;
            gap: .45rem;
            flex-wrap: wrap;
            margin-bottom: .9rem;
        }

        .collab-chip {
            padding: .35rem .55rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            border: 1px solid var(--border-lt);
            background: #fff;
            color: var(--ink-soft);
        }

        .collab-list {
            display: flex;
            flex-direction: column;
            gap: .65rem;
            max-height: 260px;
            overflow: auto;
            padding-right: .15rem;
            margin-top: .9rem;
        }

        .collab-entry {
            border: 1px solid var(--border-lt);
            border-radius: 12px;
            background: #fff;
            padding: .75rem .8rem;
        }

        .collab-entry-head,
        .collab-entry-meta {
            display: flex;
            justify-content: space-between;
            gap: .65rem;
            align-items: baseline;
        }

        .collab-entry-head strong {
            font-size: .82rem;
            color: var(--ink);
        }

        .collab-entry-note {
            margin-top: .35rem;
            font-size: .78rem;
            line-height: 1.6;
            color: var(--ink-soft);
            white-space: pre-wrap;
        }

        .collab-entry-meta {
            margin-top: .15rem;
            font-size: .71rem;
            color: var(--ink-muted);
        }

        .collab-actions {
            display: flex;
            gap: .55rem;
            flex-wrap: wrap;
            margin-top: .75rem;
        }

        .performance-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .performance-item-head,
        .performance-item-meta {
            display: flex;
            justify-content: space-between;
            gap: .6rem;
            align-items: baseline;
        }

        .performance-item-head strong {
            font-size: .82rem;
            color: var(--ink);
        }

        .performance-item-meta {
            margin-top: .18rem;
            font-size: .72rem;
            color: var(--ink-muted);
        }

        .calendar-entry-title {
            font-size: .76rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.45;
        }

        .calendar-entry-meta {
            margin-top: .25rem;
            font-size: .7rem;
            color: var(--ink-muted);
            line-height: 1.5;
        }

        .mix-stack {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .mix-stack.compact .mix-item {
            padding: .58rem .65rem;
        }

        .mix-stack.compact {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .mix-list {
            display: flex;
            flex-direction: column;
            gap: .6rem;
        }

        .mix-item {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            align-items: center;
            padding: .65rem .7rem;
            border-radius: 12px;
            background: #fbfbfc;
            border: 1px solid var(--border-lt);
            font-size: .8rem;
        }

        .mix-item strong {
            color: var(--ink);
        }

        .results-shell {
            margin-top: .05rem;
        }

        .results-card-head {
            margin-bottom: .75rem;
        }

        .results-panel {
            overflow: visible;
            padding: 0;
            gap: 1rem;
        }

        .results-empty {
            min-height: 360px;
            border: 1px dashed var(--border);
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #fafafa 100%);
        }

        .content-layout,
        .generator-panel {
            display: block;
            height: auto;
            overflow: visible;
            background: transparent;
            border: none;
        }

        @media (max-width: 1180px) {
            .hero-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .workspace-grid,
            .calendar-grid,
            .module-area-grid,
            .mention-grid-top {
                grid-template-columns: 1fr;
            }

            .template-shell-grid {
                grid-template-columns: 1fr;
            }

            .template-library-panel {
                border-left: none;
                padding-left: 0;
                border-top: 1px solid var(--border-lt);
                padding-top: 1rem;
            }

            .calendar-board {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .sla-summary-grid,
            .sla-stage-grid,
            .sla-critical-grid,
            .playbook-grid,
            .performance-summary-grid,
            .template-library-list,
            .mix-stack.compact,
            .mention-reputation-legend,
            .mention-metric-grid,
            .operations-summary-grid {
                grid-template-columns: 1fr;
            }

            .toolbar-form {
                grid-template-columns: 1fr 1fr;
            }

            .mention-toolbar-form,
            .operations-toolbar-form {
                grid-template-columns: 1fr 1fr;
            }

            .archive-toolbar-form,
            .archive-memory-grid {
                grid-template-columns: 1fr 1fr;
            }

            .toolbar-actions {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 900px) {
            .comm-page {
                padding: 1rem;
            }

            .hero-top {
                flex-direction: column;
            }

            .hero-summary-grid,
            .calendar-board,
            .toolbar-form,
            .month-grid,
            .sla-summary-grid,
            .sla-stage-grid,
            .sla-critical-grid,
            .performance-summary-grid,
            .template-library-list,
            .mix-stack.compact,
            .mention-reputation-legend,
            .mention-metric-grid,
            .mention-toolbar-form,
            .operations-toolbar-form,
            .operations-summary-grid,
            .archive-toolbar-form,
            .archive-memory-grid {
                grid-template-columns: 1fr;
            }

            .workspace-card,
            .queue-card,
            .calendar-card,
            .mix-card,
            .results-card {
                padding: .95rem;
            }

            .tabs {
                flex-wrap: wrap;
            }

            .assist-grid {
                grid-template-columns: 1fr;
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
    @php
        $governance = $editorialBoard['governance'] ?? [];
        $calendarDays = $editorialBoard['calendar_days'] ?? [];
        $calendarMonth = $editorialBoard['calendar_month'] ?? [];
        $channelMix = $editorialBoard['channel_mix'] ?? [];
        $originMix = $editorialBoard['origin_mix'] ?? [];
        $performance = $editorialBoard['performance'] ?? [];
        $queueItems = $editorialBoard['focus_queue'] ?? [];
        $slaBoard = $editorialBoard['sla'] ?? [];
        $slaTotals = $slaBoard['totals'] ?? [];
        $slaStages = $slaBoard['stages'] ?? [];
        $slaCriticalItems = $slaBoard['critical_items'] ?? [];
        $slaConfigData = $slaBoard['config'] ?? [];
        $postTemplates = collect($contentTemplates ?? [])
            ->where('kind', 'post')
            ->values();
        $imageTemplates = collect($contentTemplates ?? [])
            ->where('kind', 'image')
            ->values();
        $postPlaybooks = collect($editorialPlaybooks ?? [])
            ->where('target_tab', 'post')
            ->values();
        $interviewPlaybooks = collect($editorialPlaybooks ?? [])
            ->where('target_tab', 'interview')
            ->values();
        $crisisPlaybooks = collect($editorialPlaybooks ?? [])
            ->where('target_tab', 'crisis')
            ->values();
        $mentionStats = $mentionsBoard['stats'] ?? [];
        $mentionFilters = $mentionsBoard['filters'] ?? [];
        $mentionSegments = $mentionsBoard['reputation_segments'] ?? [];
        $mentionItems = $mentionsBoard['mentions'] ?? collect();
        $mentionKeywords = $mentionsBoard['keywords'] ?? collect();
        $mentionSourceOptions = $mentionsBoard['source_options'] ?? [];
        $mentionConfiguration = $mentionsBoard['configuration'] ?? [];
        $operationsFilters = $operationsBoard['filters'] ?? [];
        $operationsSummary = $operationsBoard['summary'] ?? [];
        $operationsColumns = $operationsBoard['columns'] ?? [];
        $operationStorySuggestions = $operationsBoard['story_suggestions'] ?? [];
        $operationDeadlines = $operationsBoard['deadlines'] ?? [];
        $operationRecentActivity = $operationsBoard['recent_activity'] ?? [];
        $operationTypeMix = $operationsBoard['type_mix'] ?? [];
        $operationContactAreas = $operationsBoard['contact_areas'] ?? collect();
        $archiveTotals = $archiveBoard['totals'] ?? [];
        $archiveOptions = $archiveBoard['options'] ?? [];
        $archiveItems = $archiveBoard['recent_items'] ?? [];
        $archiveSessionGroups = $archiveBoard['session_groups'] ?? [];
        $archiveCrisisMemory = $archiveBoard['crisis_memory'] ?? [];
        $archiveMediaTrainingMemory = $archiveBoard['media_training_memory'] ?? [];
    @endphp

    <div class="comm-page">
        <section class="module-shell-card">
            <div class="module-shell-tabs">
                <div class="module-shell-tab-group">
                    <a href="{{ route('mayor.content.index', ['area' => 'produce']) }}"
                        class="module-shell-tab {{ $activeArea === 'produce' ? 'is-active' : '' }}">Produzir</a>
                    <a href="{{ route('mayor.content.index', ['area' => 'mentions']) }}"
                        class="module-shell-tab {{ $activeArea === 'mentions' ? 'is-active' : '' }}">O que estão falando</a>
                </div>
                <div class="module-shell-tab-group is-right">
                    <a href="{{ route('mayor.content.index', ['area' => 'operations']) }}"
                        class="module-shell-tab {{ $activeArea === 'operations' ? 'is-active' : '' }} {{ $activeArea !== 'operations' ? 'is-soon' : '' }}">Núcleo
                        de Operação</a>
                    <a href="{{ route('mayor.content.index', ['area' => 'archive']) }}"
                        class="module-shell-tab {{ $activeArea === 'archive' ? 'is-active' : '' }}">Arquivo</a>
                </div>
            </div>
        </section>

        @if ($activeArea === 'produce')
            <!--<section class="comm-hero">
                                                                                <div class="hero-card">
                                                                                    <div class="hero-top">
                                                                                        <div class="hero-title">
                                                                                            <h1>Central de Comunicação</h1>
                                                                                            <p>Crie, revise, agende e publique conteúdos do mandato em um workspace único, com fila
                                                                                                editorial e
                                                                                                visão semanal de execução.</p>
                                                                                        </div>
                                                                                        <div class="hero-badge">Operação editorial ativa</div>
                                                                                    </div>

                                                                                    <div class="hero-summary-grid">
                                                                                        @foreach ([['label' => 'Rascunhos', 'value' => $summary['draft'] ?? 0, 'meta' => 'Peças aguardando revisão ou acabamento final.'], ['label' => 'Aprovados', 'value' => $summary['approved'] ?? 0, 'meta' => 'Conteúdos prontos para virar publicação.'], ['label' => 'Agenda da semana', 'value' => $governance['scheduled_upcoming'] ?? 0, 'meta' => 'Conteúdos planejados para os próximos 7 dias.'], ['label' => 'Prontos para publicar', 'value' => $governance['ready_to_publish'] ?? 0, 'meta' => 'Fila quente para soltar no canal certo.'], ['label' => 'Publicados na semana', 'value' => $governance['published_this_week'] ?? 0, 'meta' => 'Entregas efetivamente publicadas nesta janela.']] as $card)
    <div class="hero-summary-card">
                                                                                                <div class="label">{{ $card['label'] }}</div>
                                                                                                <div class="value">{{ $card['value'] }}</div>
                                                                                                <div class="meta">{{ $card['meta'] }}</div>
                                                                                            </div>
    @endforeach
                                                                                    </div>
                                                                                </div>
                                                                            </section>-->

            <!--<section class="comm-toolbar">
                                                                                <div class="toolbar-card">
                                                                                    <form method="GET" action="{{ route('mayor.content.index') }}" class="toolbar-form">
                                                                                        <div class="field" style="margin-bottom:0">
                                                                                            <label>Status editorial</label>
                                                                                            <select name="status">
                                                                                                @foreach (['all' => 'Todos', 'draft' => 'Rascunho', 'approved' => 'Aprovado', 'published' => 'Publicado', 'archived' => 'Arquivado'] as $value => $label)
    <option value="{{ $value }}" @selected(($filters['status'] ?? 'all') === $value)>{{ $label }}
                                                                                                    </option>
    @endforeach
                                                                                            </select>
                                                                                        </div>
                                                                                        <div class="field" style="margin-bottom:0">
                                                                                            <label>Tipo</label>
                                                                                            <select name="type">
                                                                                                @foreach (['all' => 'Todos', 'post' => 'Comunicação', 'image' => 'Imagem IA', 'interview' => 'Entrevista', 'crisis' => 'Crise'] as $value => $label)
    <option value="{{ $value }}" @selected(($filters['type'] ?? 'all') === $value)>{{ $label }}
                                                                                                    </option>
    @endforeach
                                                                                            </select>
                                                                                        </div>
                                                                                        <div class="field" style="margin-bottom:0">
                                                                                            <label>Busca</label>
                                                                                            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                                                                                                placeholder="Tema, origem, tag, título ou contexto">
                                                                                        </div>
                                                                                        <div class="toolbar-actions">
                                                                                            <button class="action-btn primary" type="submit">Filtrar</button>
                                                                                            <a class="action-btn" href="{{ route('mayor.content.index') }}">Limpar</a>
                                                                                        </div>
                                                                                    </form>
                                                                                </div>
                                                                            </section>-->

            <!--<section class="sla-section">
                                                                            <div class="results-card">
                                                                                <div class="results-card-head">
                                                                                    <div>
                                                                                        <h2 class="section-title">SLA Editorial por Etapa</h2>
                                                                                        <p class="section-subtitle">Fecha a cadência entre revisão, colaboração, agenda e publicação com
                                                                                            leitura de risco em tempo real.</p>
                                                                                    </div>
                                                                                    <div class="sla-config-badges">
                                                                                        <span class="sla-config-chip">Revisão inicial:
                                                                                            {{ $slaConfigData['draft_review_hours'] ?? 24 }}h</span>
                                                                                        <span class="sla-config-chip">Aprovado para publicar:
                                                                                            {{ $slaConfigData['approved_publish_hours'] ?? 24 }}h</span>
                                                                                        <span class="sla-config-chip">Antecedência do agendado:
                                                                                            {{ $slaConfigData['scheduled_lead_hours'] ?? 6 }}h</span>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="sla-summary-grid">
                                                                                    <div class="sla-summary-card status-overdue">
                                                                                        <div class="label">Vencidos agora</div>
                                                                                        <div class="value">{{ $slaTotals['overdue_total'] ?? 0 }}</div>
                                                                                        <div class="meta">Peças que já estouraram a etapa ativa e pedem ação imediata.</div>
                                                                                    </div>
                                                                                    <div class="sla-summary-card status-at_risk">
                                                                                        <div class="label">Em risco</div>
                                                                                        <div class="value">{{ $slaTotals['at_risk_total'] ?? 0 }}</div>
                                                                                        <div class="meta">Peças próximas do vencimento e que precisam entrar na fila quente.</div>
                                                                                    </div>
                                                                                    <div class="sla-summary-card status-on_track">
                                                                                        <div class="label">Dentro do SLA</div>
                                                                                        <div class="value">{{ $slaTotals['on_track_total'] ?? 0 }}</div>
                                                                                        <div class="meta">{{ $slaTotals['active_total'] ?? 0 }} peça(s) com SLA ativo no recorte
                                                                                            atual.
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="sla-summary-card status-complete">
                                                                                        <div class="label">Publicação no prazo</div>
                                                                                        <div class="value">
                                                                                            {{ number_format($slaTotals['published_on_time_rate'] ?? 100, 1, ',', '.') }}%
                                                                                        </div>
                                                                                        <div class="meta">{{ $slaTotals['published_on_time_total'] ?? 0 }} de
                                                                                            {{ $slaTotals['published_recent_total'] ?? 0 }} publicações recentes dentro do prazo.</div>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="sla-stage-grid">
                                                                                    @foreach ($slaStages as $stage)
    <div class="sla-stage-card status-{{ $stage['status_key'] ?? 'on_track' }}">
                                                                                            <div class="sla-stage-top">
                                                                                                <div>
                                                                                                    <h2 class="section-title" style="font-size:.98rem">{{ $stage['label'] }}</h2>
                                                                                                    <p class="section-subtitle">{{ $stage['total'] }} peça(s) nesta etapa</p>
                                                                                                </div>
                                                                                                <span
                                                                                                    class="sla-badge sla-status-{{ $stage['status_key'] ?? 'on_track' }}">{{ $stage['overdue_total'] ?? 0 }}
                                                                                                    vencida(s)</span>
                                                                                            </div>

                                                                                            <div class="sla-stage-metrics">
                                                                                                <div class="sla-metric">
                                                                                                    <div class="metric-label">Dentro</div>
                                                                                                    <div class="metric-value">
                                                                                                        {{ number_format($stage['within_sla_rate'] ?? 100, 1, ',', '.') }}%
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="sla-metric">
                                                                                                    <div class="metric-label">Em risco</div>
                                                                                                    <div class="metric-value">{{ $stage['at_risk_total'] ?? 0 }}</div>
                                                                                                </div>
                                                                                                <div class="sla-metric">
                                                                                                    <div class="metric-label">Média</div>
                                                                                                    <div class="metric-value">
                                                                                                        {{ number_format($stage['avg_elapsed_hours'] ?? 0, 1, ',', '.') }}h
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>

                                                                                            <div class="sla-item-list">
                                                                                                @forelse ($stage['top_items'] ?? [] as $entry)
    <div class="sla-item" onclick="loadContent({{ $entry['id'] }})">
                                                                                                        <div class="sla-item-head">
                                                                                                            <div class="sla-item-title">{{ $entry['title'] ?: 'Conteúdo sem título' }}
                                                                                                            </div>
                                                                                                            <span
                                                                                                                class="sla-badge sla-status-{{ $entry['sla']['status_key'] ?? 'on_track' }}">{{ $entry['sla']['status_label'] ?? 'Dentro do SLA' }}</span>
                                                                                                        </div>
                                                                                                        <div class="sla-item-meta">
                                                                                                            <span>{{ $entry['type_label'] }} ·
                                                                                                                {{ $entry['channel'] ?: 'interno' }}</span>
                                                                                                            <span>{{ $entry['sla']['summary'] ?? 'Sem leitura de SLA' }}</span>
                                                                                                        </div>
                                                                                                        <div class="sla-item-meta">
                                                                                                            <span>Limite: {{ $entry['sla']['due_at_human'] ?? 'Sem prazo' }}</span>
                                                                                                            <span>{{ $entry['status_label'] }}</span>
                                                                                                        </div>
                                                                                                    </div>
                                    @empty
                                                                                                    <div class="queue-empty">Sem peças abertas nesta etapa agora.</div>
    @endforelse
                                                                                            </div>
                                                                                        </div>
    @endforeach
                                                                                </div>

                                                                                <div class="sla-critical-shell">
                                                                                    <div class="queue-card-head" style="margin-bottom:.8rem">
                                                                                        <div>
                                                                                            <h2 class="section-title">Fila Crítica de Vencimento</h2>
                                                                                            <p class="section-subtitle">
                                                                                                {{ $slaBoard['window_label'] ?? 'Leitura operacional do SLA atual.' }}
                                                                                            </p>
                                                                                        </div>
                                                                                    </div>

                                                                                    <div class="sla-critical-grid">
                                                                                        @forelse ($slaCriticalItems as $entry)
    <div class="sla-critical-item {{ ($entry['sla']['status_key'] ?? '') === 'overdue' ? 'is-overdue' : 'is-at-risk' }}"
                                                                                                onclick="loadContent({{ $entry['id'] }})">
                                                                                                <div class="sla-item-head">
                                                                                                    <div class="sla-item-title">{{ $entry['title'] ?: 'Conteúdo sem título' }}</div>
                                                                                                    <span
                                                                                                        class="sla-badge sla-status-{{ $entry['sla']['status_key'] ?? 'at_risk' }}">{{ $entry['sla']['status_label'] ?? 'SLA em risco' }}</span>
                                                                                                </div>
                                                                                                <div class="sla-item-meta">
                                                                                                    <span>{{ $entry['sla']['stage_label'] ?? 'Etapa atual' }}</span>
                                                                                                    <span>{{ $entry['sla']['summary'] ?? 'Sem leitura de SLA' }}</span>
                                                                                                </div>
                                                                                                <div class="sla-item-meta">
                                                                                                    <span>Limite: {{ $entry['sla']['due_at_human'] ?? 'Sem prazo' }}</span>
                                                                                                    <span>{{ $entry['type_label'] }} · {{ $entry['channel'] ?: 'interno' }}</span>
                                                                                                </div>
                                                                                            </div>
                            @empty
                                                                                            <div class="queue-empty" style="grid-column:1 / -1">Nenhuma peça crítica agora. A operação
                                                                                                está
                                                                                                respirando dentro do SLA.</div>
    @endforelse
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </section>

                                                                        <section class="playbooks-shell">
                                                                            <div class="results-card">
                                                                                <div class="results-card-head">
                                                                                    <div>
                                                                                        <h2 class="section-title">Playbooks Editoriais por Situação</h2>
                                                                                        <p class="section-subtitle">Acione um roteiro pronto por contexto operacional e leve esse guia
                                                                                            até
                                                                                            a geração, revisão, SLA e publicação.</p>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="playbook-grid">
                                                                                    @foreach ($editorialPlaybooks ?? [] as $playbook)
    <div class="playbook-card">
                                                                                            <div class="playbook-card-top">
                                                                                                <div>
                                                                                                    <div class="playbook-card-title">{{ $playbook['name'] }}</div>
                                                                                                    <div class="playbook-card-meta">{{ $playbook['situation_label'] }}</div>
                                                                                                </div>
                                                                                                <span class="playbook-chip">{{ $playbook['target_tab_label'] }}</span>
                                                                                            </div>

                                                                                            <div class="playbook-card-desc">{{ $playbook['description'] }}</div>

                                                                                            <div class="playbook-chip-row">
                                                                                                @if (!empty($playbook['suggested_channel']))
    <span class="playbook-chip">Canal: {{ $playbook['suggested_channel'] }}</span>
    @endif
                                                                                                @if (!empty($playbook['suggested_format']))
    <span class="playbook-chip">Formato: {{ $playbook['suggested_format'] }}</span>
    @endif
                                                                                            </div>

                                                                                            <div class="playbook-checklist">
                                                                                                @foreach (collect($playbook['checklist'] ?? [])->take(3) as $item)
    <span>{{ $item }}</span>
    @endforeach
                                                                                            </div>

                                                                                            <div class="playbook-actions">
                                                                                                <button type="button" class="action-btn"
                                                                                                    onclick="applyPlaybookFromLibrary('{{ $playbook['id'] }}')">Aplicar
                                                                                                    playbook</button>
                                                                                            </div>
                                                                                        </div>
    @endforeach
                                                                                </div>
                                                                            </div>
                                                                        </section>-->

            <section class="workspace-grid">
                <div class="workspace-card">
                    <div class="workspace-card-head">
                        <div>
                            <h2 class="section-title">Gerador</h2>
                            <p class="section-subtitle">Monte a peça, refine o tom e puxe do histórico recente sem sair da
                                mesma
                                área.</p>
                        </div>
                    </div>

                    <div class="tabs">
                        <button class="tab-btn active" data-tab="post" onclick="switchTab('post',this)">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z" />
                            </svg>
                            Comunicação
                        </button>
                        <button class="tab-btn image-tab" data-tab="image" onclick="switchTab('image',this)">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M21 3H3C2 3 1 4 1 5v14c0 1.1.9 2 2 2h18c1 0 2-1 2-2V5c0-1-1-2-2-2zm0 16H3V5h18v14zm-5-7l-3 3.72L11 13l-4 5h14l-4-5z" />
                            </svg>
                            Imagem IA
                        </button>
                        <button class="tab-btn" data-tab="interview" onclick="switchTab('interview',this)">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z" />
                            </svg>
                            Entrevista
                        </button>
                        <button class="tab-btn" data-tab="crisis" onclick="switchTab('crisis',this)">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                            </svg>
                            Crise
                        </button>
                    </div>

                    <div id="tab-post">
                        <!--<div class="field">
                                                                                        <label>Playbook por situação</label>
                                                                                        <select id="post-playbook-select" onchange="applyPlaybookSelection('post')">
                                                                                            <option value="">Sem playbook fixo</option>
                                                                                            @foreach ($postPlaybooks as $playbook)
    <option value="{{ $playbook['id'] }}">{{ $playbook['name'] }}</option>
    @endforeach
                                                                                        </select>
                                                                                        <div class="template-select-meta" id="post-playbook-meta">Escolha um playbook para orientar o
                                                                                            contexto da peça, o foco editorial e a execução operacional.</div>
                                                                                    </div>-->
                        <div class="field">
                            <label>Tema / ação de governo</label>
                            <textarea id="post-theme"
                                placeholder="Ex: entrega do novo posto de saúde, mutirão de limpeza, pavimentação concluída..." rows="4"></textarea>
                        </div>
                        <!--<div class="field">
                                                                                        <label>Template editorial</label>
                                                                                        <select id="post-template-select" onchange="applyTemplateSelection('post')">
                                                                                            <option value="">Sem template fixo</option>
                                                                                            @foreach ($postTemplates as $template)
    <option value="{{ $template['id'] }}">{{ $template['name'] }}
                                                                                                    @if (!empty($template['format']))
    · {{ $template['format'] }}
    @endif
                                                                                                </option>
    @endforeach
                                                                                        </select>
                                                                                        <div class="template-select-meta" id="post-template-meta">Escolha um template para reaplicar
                                                                                            canal,
                                                                                            formato editorial e orientação de texto na geração.</div>
                                                                                    </div>-->
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
                            <label>Formato editorial</label>
                            <select id="post-format-select" class="template-format-select">
                                <option value="">Livre</option>
                                <option value="feed">Feed</option>
                                <option value="carrossel">Carrossel</option>
                                <option value="stories">Stories</option>
                                <option value="nota">Nota oficial</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="discurso_curto">Discurso curto</option>
                            </select>
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
                            Gerar peça de comunicação
                        </button>
                        <div class="divider">histórico recente de comunicação</div>
                        @forelse($posts ?? [] as $content)
                            <div class="queue-item" onclick="loadContent({{ $content->id }})">
                                <div class="queue-item-top">
                                    <div class="queue-item-title">{{ $content->title }}</div>
                                    <span
                                        class="content-card-status status-{{ $content->status }}">{{ $content->status === 'approved' ? 'Aprovado' : ($content->status === 'published' ? 'Publicado' : ($content->status === 'archived' ? 'Arquivado' : 'Rascunho')) }}</span>
                                </div>
                                <div class="queue-item-meta">{{ ucfirst($content->channel) }} ·
                                    {{ $content->created_at->diffForHumans() }}</div>
                            </div>
                        @empty
                            <div class="queue-empty">Nenhum conteúdo de comunicação salvo ainda.</div>
                        @endforelse
                    </div>

                    <div id="tab-image" style="display:none">
                        <div class="image-info-box">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#7c3aed"
                                style="flex-shrink:0;margin-top:1px">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                            <div>
                                <strong>Gerador de imagem para Instagram</strong>
                                <p>A IA monta prompts prontos para DALL-E 3, Midjourney, Canva AI e Ideogram, com legenda e
                                    hashtags sugeridas.</p>
                            </div>
                        </div>
                        <div class="field">
                            <label>Tema da imagem</label>
                            <textarea id="image-theme" placeholder="Ex: inauguração da nova praça, entrega de ambulância, ação com moradores..."
                                rows="4"></textarea>
                        </div>
                        <div class="field">
                            <label>Template visual</label>
                            <select id="image-template-select" onchange="applyTemplateSelection('image')">
                                <option value="">Sem template fixo</option>
                                @foreach ($imageTemplates as $template)
                                    <option value="{{ $template['id'] }}">{{ $template['name'] }}
                                        @if (!empty($template['format']))
                                            · {{ $template['format'] }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="template-select-meta" id="image-template-meta">Use um template visual para
                                reaplicar
                                estilo, formato, paleta e instruções recorrentes de imagem.</div>
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
                                <button class="chip purple" data-color="terra"
                                    onclick="selectOne(this,'color-chips')">Terra</button>
                                <button class="chip purple" data-color="vibrante"
                                    onclick="selectOne(this,'color-chips')">Vibrante</button>
                            </div>
                        </div>
                        <button class="btn-generate purple-btn" onclick="generateImage()">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M21 3H3C2 3 1 4 1 5v14c0 1.1.9 2 2 2h18c1 0 2-1 2-2V5c0-1-1-2-2-2zm0 16H3V5h18v14zm-5-7l-3 3.72L11 13l-4 5h14l-4-5z" />
                            </svg>
                            Gerar prompts visuais
                        </button>
                        <div class="divider">histórico de imagens</div>
                        @forelse($images ?? [] as $img)
                            <div class="queue-item" onclick="loadContent({{ $img->id }})">
                                <div class="queue-item-top">
                                    <div class="queue-item-title">{{ $img->title }}</div>
                                    <span
                                        class="content-card-status status-{{ $img->status }}">{{ $img->status === 'approved' ? 'Aprovado' : ($img->status === 'published' ? 'Publicado' : ($img->status === 'archived' ? 'Arquivado' : 'Rascunho')) }}</span>
                                </div>
                                <div class="queue-item-meta">{{ ucfirst($img->tone) }} ·
                                    {{ $img->created_at->diffForHumans() }}</div>
                            </div>
                        @empty
                            <div class="queue-empty">Nenhuma imagem gerada ainda.</div>
                        @endforelse
                    </div>

                    <div id="tab-interview" style="display:none">
                        <!--<div class="field">
                                                                                <label>Playbook por situação</label>
                                                                                <select id="interview-playbook-select" onchange="applyPlaybookSelection('interview')">
                                                                                    <option value="">Sem playbook fixo</option>
                                                                                    @foreach ($interviewPlaybooks as $playbook)
    <option value="{{ $playbook['id'] }}">{{ $playbook['name'] }}</option>
    @endforeach
                                                                                </select>
                                                                                <div class="template-select-meta" id="interview-playbook-meta">Use um playbook para puxar a
                                                                                    linha
                                                                                    de preparação, mensagens-chave e riscos da entrevista.</div>
                                                                            </div>-->
                        <div class="field">
                            <label>Contexto da entrevista</label>
                            <textarea id="interview-context" placeholder="Ex: entrevista ao vivo na rádio local sobre saúde, obras e 100 dias..."
                                rows="4"></textarea>
                        </div>
                        <div class="field">
                            <label>Temas sensíveis</label>
                            <input type="text" id="interview-sensitive"
                                placeholder="Ex: obra atrasada, oposição, fila da saúde">
                        </div>
                        <button class="btn-generate" onclick="generateInterview()">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z" />
                            </svg>
                            Preparar entrevista
                        </button>
                        <div class="divider">histórico</div>
                        @forelse($entrevistas ?? [] as $c)
                            <div class="queue-item" onclick="loadContent({{ $c->id }})">
                                <div class="queue-item-title">{{ $c->title }}</div>
                                <div class="queue-item-meta">{{ $c->created_at->diffForHumans() }}</div>
                            </div>
                        @empty
                            <div class="queue-empty">Nenhuma entrevista gerada.</div>
                        @endforelse
                    </div>

                    <div id="tab-crisis" style="display:none">
                        <div
                            style="background:#fff8f0;border:1px solid #ffe0b2;border-radius:12px;padding:.9rem;margin-bottom:1rem;display:flex;gap:.7rem;align-items:flex-start">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#e65100"
                                style="flex-shrink:0;margin-top:1px">
                                <path
                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                            </svg>
                            <p style="font-size:.78rem;color:#bf360c;line-height:1.55;margin:0">Use este modo para orientar
                                resposta pública, nota oficial e próximos passos em situações sensíveis.</p>
                        </div>
                        <div class="field">
                            <label>Playbook por situação</label>
                            <select id="crisis-playbook-select" onchange="applyPlaybookSelection('crisis')">
                                <option value="">Sem playbook fixo</option>
                                @foreach ($crisisPlaybooks as $playbook)
                                    <option value="{{ $playbook['id'] }}">{{ $playbook['name'] }}</option>
                                @endforeach
                            </select>
                            <div class="template-select-meta" id="crisis-playbook-meta">Acione um playbook para puxar a
                                resposta, o timing e os próximos passos da gestão de crise.</div>
                        </div>
                        <div class="field">
                            <label>Descreva a situação</label>
                            @if (!empty($initialMentionSeed))
                                <div class="template-select-meta" id="crisis-mention-context"
                                    style="margin-bottom:.45rem">
                                    Contexto vindo de menção {{ $initialMentionSeed['sentiment_label'] ?? 'monitorada' }}
                                    em
                                    {{ $initialMentionSeed['source_label'] ?? 'fonte externa' }}.
                                </div>
                            @else
                                <div class="template-select-meta" id="crisis-mention-context" style="display:none"></div>
                            @endif
                            <textarea id="crisis-description"
                                placeholder="Ex: vídeo viralizou mostrando obra parada, oposição escalou a narrativa..." rows="5"></textarea>
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
                            <div class="queue-item" onclick="loadContent({{ $c->id }})">
                                <div class="queue-item-title">{{ $c->title }}</div>
                                <div class="queue-item-meta">{{ $c->created_at->diffForHumans() }}</div>
                            </div>
                        @empty
                            <div class="queue-empty">Nenhuma crise registrada.</div>
                        @endforelse
                    </div>
                </div>


            </section>
        @elseif ($activeArea === 'mentions')
            <section class="comm-hero">
                <div class="hero-card">
                    <div class="hero-top">
                        <div class="hero-title">
                            <h1>Comunicação · Menções</h1>
                            <p>Monitoramento de reputação, leitura de urgência e passagem direta para crise dentro do mesmo
                                módulo de Comunicação.</p>
                        </div>
                        <div class="hero-badge">Monitoramento ativo</div>
                    </div>
                </div>
            </section>

            <section class="comm-toolbar">
                <div class="toolbar-card">
                    <form method="GET" action="{{ route('mayor.content.index') }}" class="mention-toolbar-form">
                        <input type="hidden" name="area" value="mentions">
                        <div class="field" style="margin-bottom:0">
                            <label>Classe</label>
                            <select name="mention_filter">
                                @foreach (['all' => 'Todas', 'unread' => 'Não lidas', 'negative' => 'Negativas', 'urgent' => 'Urgentes', 'positive' => 'Positivas', 'neutral' => 'Neutras'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($mentionFilters['filter'] ?? 'all') === $value)>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="margin-bottom:0">
                            <label>Fonte</label>
                            <select name="mention_source">
                                <option value="all" @selected(($mentionFilters['source'] ?? 'all') === 'all')>Todas as fontes</option>
                                @foreach ($mentionSourceOptions as $option)
                                    <option value="{{ $option['value'] }}" @selected(($mentionFilters['source'] ?? 'all') === $option['value'])>
                                        {{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="margin-bottom:0">
                            <label>Janela</label>
                            <select name="mention_days">
                                @foreach ([3 => '3 dias', 7 => '7 dias', 14 => '14 dias', 30 => '30 dias'] as $value => $label)
                                    <option value="{{ $value }}" @selected((int) ($mentionFilters['days'] ?? 7) === $value)>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="toolbar-actions">
                            <button class="action-btn primary" type="submit">Filtrar</button>
                            <a class="action-btn"
                                href="{{ route('mayor.content.index', ['area' => 'mentions']) }}">Limpar</a>
                        </div>
                    </form>
                </div>
            </section>

            <section class="results-shell">
                <div class="results-card">
                    <div class="results-card-head">
                        <div>
                            <h2 class="section-title">Termômetro de Reputação</h2>
                            <p class="section-subtitle">Distribuição das menções do período filtrado por sentimento e
                                urgência.</p>
                        </div>
                        <a class="action-btn" href="{{ route('mayor.mentions.config') }}">Palavras-chave e fontes</a>
                    </div>
                    <div class="mention-reputation-bar">
                        @foreach ($mentionSegments as $segment)
                            <div class="mention-reputation-segment"
                                style="width:{{ max(0, (float) ($segment['percent'] ?? 0)) }}%;background:{{ $segment['color'] }}">
                            </div>
                        @endforeach
                    </div>
                    <div class="mention-reputation-legend">
                        @foreach ($mentionSegments as $segment)
                            <div class="mention-reputation-item">
                                <strong>{{ $segment['count'] }}</strong>
                                <span><i class="mention-dot"
                                        style="background:{{ $segment['color'] }}"></i>{{ $segment['label'] }} ·
                                    {{ number_format((float) $segment['percent'], 1, ',', '.') }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="results-shell">
                <div class="results-card">
                    <div class="mention-metric-grid">
                        @foreach ([['label' => 'Total', 'value' => $mentionStats['total'] ?? 0], ['label' => 'Positivas', 'value' => $mentionStats['positive'] ?? 0], ['label' => 'Negativas', 'value' => $mentionStats['negative'] ?? 0], ['label' => 'Urgentes', 'value' => $mentionStats['urgent'] ?? 0], ['label' => 'Neutras', 'value' => $mentionStats['neutral'] ?? 0], ['label' => 'Não lidas', 'value' => $mentionStats['unread'] ?? 0]] as $card)
                            <div class="mention-metric-card">
                                <div class="label">{{ $card['label'] }}</div>
                                <div class="value">{{ $card['value'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="results-shell">
                <div class="results-card">
                    <div class="results-card-head">
                        <div>
                            <h2 class="section-title">Cobertura configurada do monitoramento</h2>
                            <p class="section-subtitle">Leitura rápida do que o módulo `Configurações` já alimenta em
                                Menções e no `Pra hoje`.</p>
                        </div>
                        <a class="action-btn" href="{{ route('mayor.mentions.config') }}">Ajustar palavras-chave</a>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.8rem;margin-bottom:1rem">
                        <div class="mention-metric-card">
                            <div class="label">Prontidão</div>
                            <div class="value">{{ $mentionConfiguration['score'] ?? 0 }}%</div>
                        </div>
                        <div class="mention-metric-card">
                            <div class="label">Canais ativos</div>
                            <div class="value">{{ count($mentionConfiguration['active_channels'] ?? []) }}</div>
                        </div>
                        <div class="mention-metric-card">
                            <div class="label">Termos</div>
                            <div class="value">{{ count($mentionConfiguration['monitoring_terms'] ?? []) }}</div>
                        </div>
                        <div class="mention-metric-card">
                            <div class="label">Pra hoje</div>
                            <div class="value">{{ $mentionConfiguration['pra_hoje_time'] ?? '—' }}</div>
                        </div>
                    </div>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.9rem">
                        @foreach ($mentionConfiguration['active_channels'] ?? [] as $channel)
                            <span class="mention-badge"
                                style="background:#eff6ff;color:#1d4ed8">{{ ucfirst($channel) }}</span>
                        @endforeach
                        @foreach (array_slice($mentionConfiguration['monitoring_portals'] ?? [], 0, 4) as $portal)
                            <span class="mention-badge"
                                style="background:#f3f4f6;color:#374151">{{ $portal }}</span>
                        @endforeach
                    </div>
                    @if (!empty($mentionConfiguration['issues']))
                        <div class="queue-empty" style="text-align:left;border-style:solid">
                            Pendências atuais: {{ implode(', ', array_slice($mentionConfiguration['issues'], 0, 3)) }}.
                        </div>
                    @else
                        <div class="queue-empty"
                            style="text-align:left;border-style:solid;background:#f0fdf4;color:#166534;border-color:#bbf7d0">
                            Monitoramento de menções e configuração do `Pra hoje` estão prontos para operação contínua.
                        </div>
                    @endif
                </div>
            </section>

            <section class="module-area-grid">
                <div class="results-card">
                    <div class="results-card-head">
                        <div>
                            <h2 class="section-title">Painel de Menções</h2>
                            <p class="section-subtitle">Abra, marque como lida e puxe para crise sem sair do módulo.</p>
                        </div>
                    </div>
                    <div class="mention-list-embedded">
                        @forelse ($mentionItems as $mention)
                            @php $sc = $mention->sentiment_color; @endphp
                            <div class="mention-item {{ !$mention->is_read ? 'unread' : '' }}"
                                data-highlight-mention="{{ $mention->id }}">
                                <div class="mention-item-head">
                                    <div>
                                        <div class="mention-item-title">{{ $mention->title ?: 'Menção monitorada' }}
                                        </div>
                                        <div class="mention-item-copy">
                                            {{ \Illuminate\Support\Str::limit($mention->content ?? '', 260) }}</div>
                                        <div class="mention-item-meta">
                                            <span class="mention-badge"
                                                style="background:{{ $sc['bg'] }};color:{{ $sc['text'] }}">{{ $mention->sentiment_label }}</span>
                                            <span>{{ $mention->source_label }}</span>
                                            @if ($mention->author)
                                                <span>{{ $mention->author }}</span>
                                            @endif
                                            <span>{{ $mention->time_ago }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mention-item-actions">
                                    @if ($mention->url)
                                        <a href="{{ $mention->url }}" target="_blank" rel="noopener"
                                            class="action-btn">Abrir origem</a>
                                    @endif
                                    <form method="POST" action="{{ route('mayor.mentions.reclassify', $mention) }}"
                                        style="display:flex;gap:.45rem;flex-wrap:wrap;align-items:center">
                                        @csrf
                                        <select name="sentiment"
                                            style="padding:.52rem .7rem;border:1px solid #d1d5db;border-radius:10px;background:#fff;min-width:146px">
                                            @foreach (['positive' => 'Positiva', 'neutral' => 'Neutra', 'negative' => 'Negativa', 'urgent' => 'Urgente'] as $value => $label)
                                                <option value="{{ $value }}" @selected($mention->sentiment === $value)>
                                                    {{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button class="action-btn" type="submit">Reclassificar</button>
                                    </form>
                                    @if (in_array($mention->sentiment, ['negative', 'urgent'], true))
                                        <a href="{{ route('mayor.content.index', ['area' => 'produce', 'tab' => 'crisis', 'mention' => $mention->id]) }}"
                                            class="action-btn primary">Abrir crise</a>
                                    @endif
                                    @if (!$mention->is_read)
                                        <form method="POST" action="{{ route('mayor.mentions.read') }}">
                                            @csrf
                                            <input type="hidden" name="mention_id" value="{{ $mention->id }}">
                                            <button class="action-btn" type="submit">Marcar lida</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="queue-empty">Nenhuma menção encontrada neste recorte.</div>
                        @endforelse
                    </div>
                </div>

                <div class="results-card">
                    <div class="results-card-head">
                        <div>
                            <h2 class="section-title">Curadoria Manual</h2>
                            <p class="section-subtitle">Registre WhatsApp e outras fontes não automatizáveis dentro da
                                operação.</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('mayor.mentions.manual.store') }}">
                        @csrf
                        <div class="field">
                            <label>Origem</label>
                            <select name="channel">
                                <option value="whatsapp">WhatsApp / grupo</option>
                                <option value="social">Rede social manual</option>
                                <option value="news">Portal / notícia</option>
                                <option value="manual">Outro manual</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Título</label>
                            <input type="text" name="title"
                                placeholder="Ex: vídeo de crítica circulando em grupo local">
                        </div>
                        <div class="field">
                            <label>Conteúdo</label>
                            <textarea name="content" rows="5" required
                                placeholder="Cole aqui a menção, resumo do print ou contexto principal."></textarea>
                        </div>
                        <div class="field">
                            <label>Autor ou perfil</label>
                            <input type="text" name="author" placeholder="Ex: @perfil local ou grupo Bairro Centro">
                        </div>
                        <div class="field">
                            <label>URL opcional</label>
                            <input type="url" name="url" placeholder="https://...">
                        </div>
                        <div class="toolbar-actions">
                            <button class="action-btn primary" type="submit">Salvar menção manual</button>
                            <a class="action-btn" href="{{ route('mayor.mentions.config') }}">Gerir palavras-chave</a>
                        </div>
                    </form>

                    <div class="divider">palavras-chave ativas</div>
                    <div class="mix-list">
                        @forelse ($mentionKeywords->take(10) as $keyword)
                            <div class="mix-item">
                                <strong>{{ $keyword->keyword }}</strong>
                                <span>{{ $keyword->type_label }}</span>
                            </div>
                        @empty
                            <div class="queue-empty">Nenhuma palavra-chave ativa configurada.</div>
                        @endforelse
                    </div>
                </div>
            </section>
        @elseif ($activeArea === 'operations')
            <section class="comm-hero">
                <div class="hero-card">
                    <div class="hero-top">
                        <div class="hero-title">
                            <h1>Comunicação · Núcleo de Operação</h1>
                            <p>Central de trabalho da equipe de comunicação para pauta, cobertura, imprensa, sugestões do
                                Resolve ai e acompanhamento das entregas do período.</p>
                        </div>
                        <div class="hero-badge">Pauta operacional ativa</div>
                    </div>
                </div>
            </section>

            <section class="comm-toolbar">
                <div class="toolbar-card">
                    <form method="GET" action="{{ route('mayor.content.index') }}" class="operations-toolbar-form">
                        <input type="hidden" name="area" value="operations">
                        <div class="field" style="margin-bottom:0">
                            <label>Tipo de pauta</label>
                            <select name="operation_type">
                                @foreach (['all' => 'Todos', 'content_production' => 'Produção de conteúdo', 'event_coverage' => 'Cobertura de evento', 'press_service' => 'Atendimento à imprensa', 'mandate_delivery' => 'Mandato em conteúdo', 'resolve_story' => 'Resolve ai em conteúdo', 'crisis_monitoring' => 'Monitoramento de crise'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($operationsFilters['type'] ?? 'all') === $value)>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="margin-bottom:0">
                            <label>Responsável / pasta</label>
                            <select name="operation_contact_area_id">
                                <option value="">Todas as pastas</option>
                                @foreach ($operationContactAreas as $area)
                                    <option value="{{ $area->id }}" @selected((int) ($operationsFilters['contact_area_id'] ?? 0) === (int) $area->id)>
                                        {{ $area->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="margin-bottom:0">
                            <label>Prioridade</label>
                            <select name="operation_priority">
                                @foreach (['all' => 'Todas', 'alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($operationsFilters['priority'] ?? 'all') === $value)>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="margin-bottom:0">
                            <label>Período</label>
                            <select name="operation_period">
                                @foreach (['7d' => '7 dias', '30d' => '30 dias', '90d' => '90 dias', 'all' => 'Tudo'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($operationsFilters['period'] ?? '30d') === $value)>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="margin-bottom:0">
                            <label>Busca</label>
                            <input type="text" name="operation_search"
                                value="{{ $operationsFilters['search'] ?? '' }}"
                                placeholder="Tema, localidade, briefing ou entrega">
                        </div>
                        <div class="toolbar-actions">
                            <button class="action-btn primary" type="submit">Filtrar</button>
                            <a class="action-btn"
                                href="{{ route('mayor.content.index', ['area' => 'operations']) }}">Limpar</a>
                        </div>
                    </form>
                </div>
            </section>

            <section class="results-shell">
                <div class="results-card">
                    <div class="operations-summary-grid">
                        @foreach ([
            ['label' => 'Entrada', 'value' => $operationsSummary['entry_total'] ?? 0, 'meta' => 'Demandas recém-chegadas e ainda sem amarração completa.'],
            ['label' => 'Planejamento', 'value' => $operationsSummary['planning_total'] ?? 0, 'meta' => 'Itens com prazo ou pasta definidos, ainda em encaixe operacional.'],
            ['label' => 'Produção', 'value' => $operationsSummary['production_total'] ?? 0, 'meta' => 'Coberturas, respostas ou peças em execução ativa.'],
            ['label' => 'Aprovação', 'value' => $operationsSummary['approval_total'] ?? 0, 'meta' => 'Entregas aguardando confirmação, revisão ou sinal verde.'],
            ['label' => 'Concluídas', 'value' => $operationsSummary['completed_total'] ?? 0, 'meta' => 'Pautas já fechadas, entregues ou publicadas.'],
            ['label' => 'Atrasadas', 'value' => $operationsSummary['overdue_total'] ?? 0, 'meta' => 'Itens que já romperam prazo e exigem atenção agora.'],
            ['label' => 'Sugestões Resolve ai', 'value' => $operationsSummary['story_ready_total'] ?? 0, 'meta' => 'Demandas prontas para virar comunicação pública e narrativa.'],
        ] as $card)
                            <div class="operations-summary-card">
                                <div class="label">{{ $card['label'] }}</div>
                                <div class="value">{{ $card['value'] }}</div>
                                <div class="meta">{{ $card['meta'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="results-shell">
                <div class="results-card">
                    <div class="results-card-head">
                        <div>
                            <h2 class="section-title">Kanban da Pauta</h2>
                            <p class="section-subtitle">Quadro operacional com cinco colunas, seguindo a estrutura do
                                módulo
                                no documento-base.</p>
                        </div>
                        <a class="action-btn" href="{{ route('mayor.mandato.demands.index') }}">Abrir quadro
                            completo</a>
                    </div>

                    <div class="operations-board-shell">
                        <div class="operations-board">
                            @foreach ($operationsColumns as $column)
                                <div class="operations-column" data-operation-column="{{ $column['key'] }}">
                                    <div class="operations-column-head">
                                        <div>
                                            <h3 class="operations-column-title">{{ $column['label'] }}</h3>
                                            <div class="operations-column-copy">{{ $column['subtitle'] }}</div>
                                        </div>
                                        <span class="operations-column-total">{{ $column['total'] }}</span>
                                    </div>

                                    <div class="operations-column-list" data-operation-dropzone="{{ $column['key'] }}">
                                        @forelse ($column['items'] as $item)
                                            <div class="operations-card {{ !empty($item['is_overdue']) ? 'is-overdue' : '' }}"
                                                data-operation-card="{{ $item['id'] }}"
                                                data-highlight-demand="{{ $item['id'] }}" draggable="true">
                                                <div class="operations-card-head">
                                                    <div class="operations-card-title">{{ $item['title'] }}</div>
                                                    @if (!empty($item['is_overdue']))
                                                        <span class="operations-meta-chip"
                                                            style="background:#fef2f2;color:#b91c1c;border-color:#fecaca">Atrasada</span>
                                                    @endif
                                                </div>
                                                <div class="operations-card-copy">{{ $item['copy'] }}</div>
                                                <div class="operations-chip-row">
                                                    <span class="operations-chip"
                                                        style="background:{{ $item['status_badge']['bg'] }};color:{{ $item['status_badge']['color'] }}">{{ $item['status_label'] }}</span>
                                                    <span class="operations-chip"
                                                        style="background:{{ $item['priority_badge']['bg'] }};color:{{ $item['priority_badge']['color'] }}">{{ $item['priority_label'] }}</span>
                                                    <span class="operations-meta-chip">{{ $item['type_label'] }}</span>
                                                </div>
                                                <div class="operations-meta-row">
                                                    <span class="operations-meta-chip">Pasta:
                                                        {{ $item['responsible_label'] }}</span>
                                                    <span class="operations-meta-chip">Origem:
                                                        {{ $item['origin_label'] }}</span>
                                                </div>
                                                <div class="operations-meta-row">
                                                    <span class="operations-meta-chip">Prazo:
                                                        {{ $item['due_at_human'] }}</span>
                                                    <span class="operations-meta-chip">Canal:
                                                        {{ $item['channel_label'] }}</span>
                                                </div>
                                                <div class="operations-meta-row">
                                                    <span class="operations-meta-chip">Solicitante:
                                                        {{ $item['requested_by'] }}</span>
                                                    <span class="operations-meta-chip">{{ $item['locality'] }}</span>
                                                </div>
                                                <div class="operations-card-hint">{{ $item['resource_hint'] }}</div>
                                                <div class="operations-action-row">
                                                    <a href="{{ $item['show_url'] }}" class="action-btn">Abrir
                                                        demanda</a>
                                                    @if (!empty($item['context_url']))
                                                        <a href="{{ $item['context_url'] }}" class="action-btn">
                                                            {{ $item['context_label'] }}
                                                        </a>
                                                    @endif
                                                    @if (!empty($item['story_ready']))
                                                        <form method="POST"
                                                            action="{{ route('mayor.mandato.demands.communication-draft', $item['id']) }}">
                                                            @csrf
                                                            <input type="hidden" name="channel" value="instagram">
                                                            <button type="submit" class="action-btn primary">Gerar
                                                                conteúdo</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <div class="queue-empty">Nenhuma pauta nesta coluna com os filtros atuais.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section class="module-area-grid">
                <div class="results-card">
                    <div class="results-card-head">
                        <div>
                            <h2 class="section-title">Sugestões Resolve ai</h2>
                            <p class="section-subtitle">Demandas concluídas com potencial imediato para virar prestação de
                                contas, narrativa ou acompanhamento político.</p>
                        </div>
                    </div>
                    <div class="ops-stack-list">
                        @forelse ($operationStorySuggestions as $item)
                            <div class="ops-mini-item">
                                <strong>{{ $item['title'] }}</strong>
                                <p>{{ $item['copy'] }}</p>
                                <div class="ops-mini-meta">
                                    <span>{{ $item['responsible_label'] }}</span>
                                    <span>{{ $item['locality'] }}</span>
                                    <span>{{ $item['status_label'] }}</span>
                                    @if (!empty($item['has_attachment']))
                                        <span>Comprovante anexado</span>
                                    @endif
                                </div>
                                <div class="operations-action-row">
                                    <form method="POST"
                                        action="{{ route('mayor.mandato.demands.communication-draft', $item['id']) }}">
                                        @csrf
                                        <input type="hidden" name="channel" value="instagram">
                                        <button type="submit" class="action-btn primary">Gerar rascunho em
                                            Comunicação</button>
                                    </form>
                                    <form method="POST"
                                        action="{{ route('mayor.mandato.demands.strategic-conversation', $item['id']) }}">
                                        @csrf
                                        <input type="hidden" name="mode" value="narrative">
                                        <button type="submit" class="action-btn">Narrativa no Meu Assistente</button>
                                    </form>
                                    <a href="{{ $item['show_url'] }}" class="action-btn">Abrir demanda</a>
                                </div>
                            </div>
                        @empty
                            <div class="queue-empty">Nenhuma demanda pronta para sugestão de conteúdo neste recorte.</div>
                        @endforelse
                    </div>
                </div>

                <div class="ops-stack">
                    <div class="results-card">
                        <div class="results-card-head">
                            <div>
                                <h2 class="section-title">Prazos e Cobertura</h2>
                                <p class="section-subtitle">Fila curta do que vence antes e merece rebatida operacional.
                                </p>
                            </div>
                        </div>
                        <div class="ops-stack-list">
                            @forelse ($operationDeadlines as $item)
                                <div class="ops-mini-item">
                                    <strong>{{ $item['title'] }}</strong>
                                    <p>{{ $item['type_label'] }} · {{ $item['resource_hint'] }}</p>
                                    <div class="ops-mini-meta">
                                        <span>{{ $item['due_at_human'] }}</span>
                                        <span>{{ $item['responsible_label'] }}</span>
                                        <span>{{ $item['origin_label'] }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="queue-empty">Nenhum prazo operacional ativo agora.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="results-card">
                        <div class="results-card-head">
                            <div>
                                <h2 class="section-title">Atividade Recente</h2>
                                <p class="section-subtitle">Movimentações mais novas da pauta para leitura rápida do time.
                                </p>
                            </div>
                        </div>
                        <div class="ops-stack-list">
                            @forelse ($operationRecentActivity as $item)
                                <div class="ops-mini-item">
                                    <strong>{{ $item['event_label'] }} · {{ $item['title'] }}</strong>
                                    <p>{{ $item['message'] }}</p>
                                    <div class="ops-mini-meta">
                                        <span>{{ $item['user_name'] }}</span>
                                        <span>{{ $item['created_at_human'] }}</span>
                                    </div>
                                    @if (!empty($item['show_url']))
                                        <div class="operations-action-row">
                                            <a href="{{ $item['show_url'] }}" class="action-btn">Abrir demanda</a>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="queue-empty">Sem atividade recente consolidada ainda.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="results-card">
                        <div class="results-card-head">
                            <div>
                                <h2 class="section-title">Mix da Pauta</h2>
                                <p class="section-subtitle">Distribuição do recorte atual por tipo operacional.</p>
                            </div>
                        </div>
                        <div class="mix-list">
                            @forelse ($operationTypeMix as $item)
                                <div class="mix-item">
                                    <strong>{{ $item['label'] }}</strong>
                                    <span>{{ $item['total'] }}</span>
                                </div>
                            @empty
                                <div class="queue-empty">Sem leitura de tipos neste recorte.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>
        @elseif ($activeArea === 'archive')
            <section class="comm-hero">
                <div class="hero-card">
                    <div class="hero-top">
                        <div class="hero-title">
                            <h1>Comunicação · Arquivo</h1>
                            <p>Memória operacional do módulo com busca, histórico recente e reabertura rápida de peças já
                                geradas.</p>
                        </div>
                        <div class="hero-badge">Memória editorial</div>
                    </div>
                    <div class="hero-summary-grid">
                        @foreach ([['label' => 'Total no recorte', 'value' => $archiveTotals['total'] ?? 0, 'meta' => 'Itens encontrados pelos filtros atuais.'], ['label' => 'Publicados', 'value' => $archiveTotals['published'] ?? 0, 'meta' => 'Conteúdos publicados e reutilizáveis.'], ['label' => 'Aprovados', 'value' => $archiveTotals['approved'] ?? 0, 'meta' => 'Prontos ou quase prontos para novo uso.'], ['label' => 'Sessões', 'value' => $archiveTotals['sessions_total'] ?? 0, 'meta' => 'Grupos formais de geração visíveis no arquivo.'], ['label' => 'Removidos', 'value' => $archiveTotals['deleted_total'] ?? 0, 'meta' => 'Itens retirados do arquivo com trilha auditável preservada.'], ['label' => 'Versões no recorte', 'value' => $archiveTotals['versions_total'] ?? 0, 'meta' => 'Variações acessíveis para reuso e comparação.']] as $card)
                            <div class="hero-summary-card">
                                <div class="label">{{ $card['label'] }}</div>
                                <div class="value">{{ $card['value'] }}</div>
                                <div class="meta">{{ $card['meta'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
            <section class="comm-toolbar">
                <div class="toolbar-card">
                    <form method="GET" action="{{ route('mayor.content.index') }}" class="archive-toolbar-form">
                        <input type="hidden" name="area" value="archive">
                        <div class="field" style="margin-bottom:0">
                            <label>Status editorial</label>
                            <select name="status">
                                @foreach (['all' => 'Todos', 'draft' => 'Rascunho', 'approved' => 'Aprovado', 'published' => 'Publicado', 'archived' => 'Arquivado'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['status'] ?? 'all') === $value)>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="margin-bottom:0">
                            <label>Tipo</label>
                            <select name="type">
                                @foreach (['all' => 'Todos', 'post' => 'Comunicação', 'image' => 'Imagem IA', 'interview' => 'Entrevista', 'crisis' => 'Crise'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['type'] ?? 'all') === $value)>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="margin-bottom:0">
                            <label>Canal</label>
                            <select name="channel">
                                <option value="all" @selected(($filters['channel'] ?? 'all') === 'all')>Todos os canais</option>
                                @foreach ($archiveOptions['channels'] ?? [] as $option)
                                    <option value="{{ $option['value'] }}" @selected(($filters['channel'] ?? 'all') === $option['value'])>
                                        {{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="margin-bottom:0">
                            <label>Tom</label>
                            <select name="tone">
                                <option value="all" @selected(($filters['tone'] ?? 'all') === 'all')>Todos os tons</option>
                                @foreach ($archiveOptions['tones'] ?? [] as $option)
                                    <option value="{{ $option['value'] }}" @selected(($filters['tone'] ?? 'all') === $option['value'])>
                                        {{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="margin-bottom:0">
                            <label>Perfil que criou</label>
                            <select name="creator_profile">
                                <option value="all" @selected(($filters['creator_profile'] ?? 'all') === 'all')>Todos os perfis</option>
                                @foreach ($archiveOptions['creator_profiles'] ?? [] as $option)
                                    <option value="{{ $option['value'] }}" @selected(($filters['creator_profile'] ?? 'all') === $option['value'])>
                                        {{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="margin-bottom:0">
                            <label>Período</label>
                            <select name="period">
                                @foreach (['all' => 'Tudo', '7d' => '7 dias', '30d' => '30 dias', '90d' => '90 dias'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['period'] ?? 'all') === $value)>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field" style="margin-bottom:0">
                            <label>Busca</label>
                            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                                placeholder="Palavra-chave, crise, entrevista ou contexto">
                        </div>
                        <div class="toolbar-actions">
                            <button class="action-btn primary" type="submit">Filtrar</button>
                            <a class="action-btn"
                                href="{{ route('mayor.content.index', ['area' => 'archive']) }}">Limpar</a>
                        </div>
                    </form>
                </div>
            </section>
            <section class="module-area-grid">
                <div class="results-card">
                    <div class="results-card-head">
                        <div>
                            <h2 class="section-title">Sessões de Geração</h2>
                            <p class="section-subtitle">Agrupamentos formais do arquivo para rastrear lotes e sessões
                                editoriais.</p>
                        </div>
                    </div>
                    <div class="ops-stack-list">
                        @forelse ($archiveSessionGroups as $session)
                            <div class="ops-mini-item">
                                <strong>{{ $session['label'] ?? 'Sessão editorial' }}</strong>
                                <p>{{ $session['item_total'] ?? 0 }} item(ns) ·
                                    {{ implode(' · ', $session['types'] ?? []) }}</p>
                                <div class="ops-mini-meta">
                                    <span>{{ $session['last_created_at_human'] ?? 'Agora' }}</span>
                                </div>
                                <div class="operations-action-row">
                                    @foreach ($session['items'] ?? [] as $entry)
                                        <button type="button" class="action-btn"
                                            onclick="loadContent({{ $entry['id'] }})">{{ $entry['type_label'] }}</button>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="queue-empty">Nenhuma sessão formal de geração encontrada neste recorte.</div>
                        @endforelse
                    </div>
                </div>
                <div class="results-card">
                    <div class="results-card-head">
                        <div>
                            <h2 class="section-title">Histórico Recente</h2>
                            <p class="section-subtitle">Abra qualquer item do arquivo para revisar, reaproveitar ou
                                publicar novamente.</p>
                        </div>
                    </div>
                    <div class="archive-list">
                        @forelse ($archiveItems as $entry)
                            <div class="archive-item" onclick="loadContent({{ $entry['id'] }})">
                                <div class="archive-item-head">
                                    <div class="archive-item-title">{{ $entry['title'] ?: 'Conteúdo sem título' }}</div>
                                    <span
                                        class="content-card-status status-{{ $entry['status'] }}">{{ $entry['status_label'] }}</span>
                                </div>
                                <div class="archive-item-meta">
                                    <span>{{ $entry['type_label'] }}</span>
                                    <span>{{ $entry['channel_label'] }}</span>
                                    <span>{{ $entry['tone_label'] }}</span>
                                    @if (!empty($entry['generation_session']['label']))
                                        <span>{{ $entry['generation_session']['label'] }}</span>
                                    @endif
                                    <span>{{ $entry['creator_name'] }} · {{ $entry['creator_profile_label'] }}</span>
                                    <span>{{ $entry['version_count'] }} versão(ões)</span>
                                    <span>{{ $entry['created_at_human'] }}</span>
                                    @if (!empty($entry['origin_module']))
                                        <span>Origem: {{ str_replace('_', ' ', $entry['origin_module']) }}</span>
                                    @endif
                                </div>
                                @if (!empty($entry['archive_memory']['reference_note']))
                                    <div class="archive-item-note">
                                        {{ \Illuminate\Support\Str::limit($entry['archive_memory']['reference_note'], 170) }}
                                    </div>
                                @endif
                                <div class="archive-item-actions">
                                    <button type="button" class="action-btn"
                                        onclick="event.stopPropagation(); loadContent({{ $entry['id'] }})">Abrir</button>
                                    <a class="action-btn primary" onclick="event.stopPropagation()"
                                        href="{{ route('mayor.content.index', ['area' => 'produce', 'reuse' => $entry['id']]) }}">Reusar
                                        como base</a>
                                </div>
                            </div>
                        @empty
                            <div class="queue-empty">Nenhum item encontrado no arquivo com os filtros atuais.</div>
                        @endforelse
                    </div>
                </div>
                <div class="results-card" id="content-review-section">
                    <div class="results-card-head">
                        <div>
                            <h2 class="section-title">Editor e Revisão</h2>
                            <p class="section-subtitle">Abra um item do arquivo para editar, duplicar lógica editorial e
                                reutilizar contexto.</p>
                        </div>
                    </div>
                    <div class="results-panel" id="resultsPanel">
                        <div class="results-empty" id="resultsEmpty">
                            <div class="results-empty-icon">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M21 3H3C2 3 1 4 1 5v14c0 1.1.9 2 2 2h18c1 0 2-1 2-2V5c0-1-1-2-2-2zm0 16H3V5h18v14zm-5-7l-3 3.72L11 13l-4 5h14l-4-5z" />
                                </svg>
                            </div>
                            <h3>Abra uma peça do arquivo</h3>
                            <p>Selecione um item do histórico para revisar o conteúdo salvo, a trilha editorial e o contexto
                                operacional.</p>
                        </div>
                    </div>
                </div>
            </section>
            <section class="archive-memory-grid">
                <div class="results-card">
                    <div class="results-card-head">
                        <div>
                            <h2 class="section-title">Memória de Crise</h2>
                            <p class="section-subtitle">Roteiros passados para consulta rápida em crises parecidas.</p>
                        </div>
                    </div>
                    <div class="ops-stack-list">
                        @forelse ($archiveCrisisMemory as $entry)
                            <div class="ops-mini-item">
                                <strong>{{ $entry['title'] ?: 'Crise registrada' }}</strong>
                                <p>{{ \Illuminate\Support\Str::limit($entry['content'], 180) }}</p>
                                <div class="ops-mini-meta">
                                    <span>{{ $entry['creator_name'] }}</span>
                                    <span>{{ $entry['created_at_human'] }}</span>
                                    <span>{{ $entry['version_count'] }} versão(ões)</span>
                                </div>
                                <div class="operations-action-row">
                                    <button type="button" class="action-btn"
                                        onclick="loadContent({{ $entry['id'] }})">Abrir</button>
                                    <a class="action-btn primary"
                                        href="{{ route('mayor.content.index', ['area' => 'produce', 'tab' => 'crisis', 'reuse' => $entry['id']]) }}">Usar
                                        como referência</a>
                                </div>
                            </div>
                        @empty
                            <div class="queue-empty">Nenhum roteiro de crise salvo neste recorte.</div>
                        @endforelse
                    </div>
                </div>
                <div class="results-card">
                    <div class="results-card-head">
                        <div>
                            <h2 class="section-title">Memória de Media Training</h2>
                            <p class="section-subtitle">Entrevistas e registros de desempenho para melhorar a preparação
                                futura.</p>
                        </div>
                    </div>
                    <div class="ops-stack-list">
                        @forelse ($archiveMediaTrainingMemory as $entry)
                            <div class="ops-mini-item">
                                <strong>{{ $entry['title'] ?: 'Entrevista registrada' }}</strong>
                                <p>{{ \Illuminate\Support\Str::limit($entry['content'], 180) }}</p>
                                <div class="ops-mini-meta">
                                    <span>{{ $entry['creator_name'] }}</span>
                                    <span>{{ $entry['created_at_human'] }}</span>
                                    @if (!empty($entry['archive_memory']['outcome_note']))
                                        <span>Com registro pós-entrevista</span>
                                    @endif
                                </div>
                                <div class="operations-action-row">
                                    <button type="button" class="action-btn"
                                        onclick="loadContent({{ $entry['id'] }})">Abrir</button>
                                    <a class="action-btn primary"
                                        href="{{ route('mayor.content.index', ['area' => 'produce', 'tab' => 'interview', 'reuse' => $entry['id']]) }}">Reusar
                                        briefing</a>
                                </div>
                            </div>
                        @empty
                            <div class="queue-empty">Nenhum histórico de entrevista salvo neste recorte.</div>
                        @endforelse
                    </div>
                </div>
            </section>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const currentArea = @json($activeArea ?? 'produce');
        const initialTab = @json($initialTab ?? 'post');
        const initialContentId = @json($initialContentId ?? null);
        const initialMentionSeed = @json($initialMentionSeed ?? null);
        const initialReuseSeed = @json($initialReuseSeed ?? null);
        const highlightMentionId = @json(request()->integer('highlight_mention') ?: null);
        const highlightDemandId = @json(request()->integer('highlight_demand') ?: null);
        let contentTemplates = @json($contentTemplates ?? []);
        const editorialPlaybooks = @json($editorialPlaybooks ?? []);
        let currentContent = null;
        let generatedPostBatch = [];
        let draggedOperationCardId = null;
        const REFINE_PRESETS = {
            keep: '',
            enxugar: 'Enxugue o texto, corte excesso e deixe mais direto.',
            humanizar: 'Deixe o texto mais humano, próximo e fácil de entender.',
            institucional: 'Dê um tom mais institucional, seguro e formal.',
            impacto: 'Aumente a força política e o senso de entrega concreta.',
            instagram: 'Adapte para redes sociais com ritmo melhor, abertura forte e fechamento publicável.',
        };
        const VARIATION_PACKS = {
            balanced: ['celebratorio', 'tecnico', 'empatico'],
            social: ['celebratorio', 'informativo', 'empatico'],
            institucional: ['tecnico', 'informativo', 'institucional'],
        };

        // ── Tabs ──────────────────────────────────────────────────────
        function switchTab(name, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            const activeBtn = btn || document.querySelector(`.tab-btn[data-tab="${name}"]`);
            if (activeBtn) {
                activeBtn.classList.add('active');
            }
            ['post', 'image', 'interview', 'crisis'].forEach(function(t) {
                var panel = document.getElementById('tab-' + t);
                if (panel) {
                    panel.style.display = t === name ? 'block' : 'none';
                }
            });

            const builderKind = document.getElementById('template-builder-kind');
            if (builderKind && (name === 'post' || name === 'image')) {
                builderKind.value = name;
            }
        }

        function selectOne(btn, groupId) {
            var group = document.getElementById(groupId);
            if (!group) return;
            group.querySelectorAll('.chip').forEach(c => c.classList.remove('selected'));
            btn.classList.add('selected');
        }

        function getActiveTabName() {
            var active = document.querySelector('.tab-btn.active');
            return active ? active.dataset.tab : 'post';
        }

        function getTemplateById(id) {
            var numericId = parseInt(id, 10);
            if (!numericId) return null;
            return contentTemplates.find(function(template) {
                return parseInt(template.id, 10) === numericId;
            }) || null;
        }

        function getPlaybookById(id) {
            if (!id) return null;
            return editorialPlaybooks.find(function(playbook) {
                return String(playbook.id) === String(id);
            }) || null;
        }

        function defaultPlaybookMeta(kind) {
            return {
                post: 'Escolha um playbook para orientar o contexto da peça, o foco editorial e a execução operacional.',
                interview: 'Use um playbook para puxar a linha de preparação, mensagens-chave e riscos da entrevista.',
                crisis: 'Acione um playbook para puxar a resposta, o timing e os próximos passos da gestão de crise.'
            } [kind] || 'Selecione um playbook operacional.';
        }

        function renderPlaybookMeta(kind, playbook) {
            var meta = document.getElementById(kind + '-playbook-meta');
            if (!meta) return;

            if (!playbook) {
                meta.textContent = defaultPlaybookMeta(kind);
                return;
            }

            var parts = [];
            if (playbook.description) parts.push(playbook.description);
            if (playbook.suggested_channel) parts.push('Canal: ' + playbook.suggested_channel);
            if (playbook.suggested_format) parts.push('Formato: ' + playbook.suggested_format);
            if (playbook.checklist && playbook.checklist.length) parts.push('Checklist: ' + playbook.checklist.slice(0, 2)
                .join(' | '));
            meta.textContent = parts.join(' · ') || ('Playbook ativo: ' + playbook.name);
        }

        function setValueIfEmpty(elementId, value) {
            var element = document.getElementById(elementId);
            if (!element || !value) return;
            if (!String(element.value || '').trim()) {
                element.value = value;
            }
        }

        function applyInitialMentionSeed() {
            if (!initialMentionSeed) return;

            var textarea = document.getElementById('crisis-description');
            if (!textarea) return;

            var parts = [];
            if (initialMentionSeed.title) parts.push('Título da mencao: ' + initialMentionSeed.title);
            if (initialMentionSeed.content) parts.push('Conteudo da mencao: ' + initialMentionSeed.content);
            if (initialMentionSeed.source_label) parts.push('Fonte: ' + initialMentionSeed.source_label);
            if (initialMentionSeed.author) parts.push('Autor ou perfil: ' + initialMentionSeed.author);
            if (initialMentionSeed.sentiment_label) parts.push('Classificacao: ' + initialMentionSeed.sentiment_label);
            if (initialMentionSeed.published_at_human) parts.push('Quando apareceu: ' + initialMentionSeed
                .published_at_human);
            if (initialMentionSeed.url) parts.push('URL: ' + initialMentionSeed.url);

            if (!String(textarea.value || '').trim()) {
                textarea.value = parts.join('\n');
            }

            var playbookSelect = document.getElementById('crisis-playbook-select');
            if (playbookSelect && !playbookSelect.value) {
                playbookSelect.value = 'crisis-fast-response';
                applyPlaybookSelection('crisis');
            }

            var contextNote = document.getElementById('crisis-mention-context');
            if (contextNote) {
                contextNote.style.display = 'block';
                contextNote.textContent = 'Contexto vindo de menção ' +
                    (initialMentionSeed.sentiment_label || 'monitorada') +
                    ' em ' + (initialMentionSeed.source_label || 'fonte externa') + '.';
            }
        }

        function applyPraHojeHighlights() {
            if (currentArea === 'mentions' && highlightMentionId) {
                const mention = document.querySelector(`[data-highlight-mention="${highlightMentionId}"]`);
                if (mention) {
                    mention.classList.add('is-highlighted');
                    mention.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }

            if (currentArea === 'operations' && highlightDemandId) {
                const demand = document.querySelector(`[data-highlight-demand="${highlightDemandId}"]`);
                if (demand) {
                    demand.classList.add('is-highlighted');
                    demand.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }
        }

        function applyInitialReuseSeed() {
            if (!initialReuseSeed || currentArea !== 'produce') return;

            if (initialReuseSeed.type === 'entrevista') {
                switchTab('interview');
                var interviewContext = document.getElementById('interview-context');
                var interviewSensitive = document.getElementById('interview-sensitive');
                if (interviewContext) {
                    interviewContext.value = [
                        initialReuseSeed.title ? 'Base anterior: ' + initialReuseSeed.title : '',
                        initialReuseSeed.content || '',
                    ].filter(Boolean).join('\n\n');
                }
                if (interviewSensitive && initialReuseSeed.archive_memory && initialReuseSeed.archive_memory.outcome_note) {
                    interviewSensitive.value = initialReuseSeed.archive_memory.outcome_note;
                }
                return;
            }

            if (initialReuseSeed.type === 'crise') {
                switchTab('crisis');
                var crisisDescription = document.getElementById('crisis-description');
                if (crisisDescription) {
                    crisisDescription.value = [
                        initialReuseSeed.title ? 'Referencia anterior: ' + initialReuseSeed.title : '',
                        initialReuseSeed.content || '',
                        initialReuseSeed.archive_memory && initialReuseSeed.archive_memory.reference_note ?
                        'Observacao arquivada: ' + initialReuseSeed.archive_memory.reference_note : '',
                    ].filter(Boolean).join('\n\n');
                }
                return;
            }

            if (initialReuseSeed.type === 'imagem_instagram') {
                switchTab('image');
                var imageTheme = document.getElementById('image-theme');
                if (imageTheme) {
                    imageTheme.value = [
                        initialReuseSeed.title ? 'Reuso visual: ' + initialReuseSeed.title : '',
                        initialReuseSeed.content || '',
                    ].filter(Boolean).join('\n\n');
                }
                return;
            }

            switchTab('post');
            var postTheme = document.getElementById('post-theme');
            if (postTheme) {
                postTheme.value = [
                    initialReuseSeed.title ? 'Base anterior: ' + initialReuseSeed.title : '',
                    initialReuseSeed.content || '',
                    initialReuseSeed.archive_memory && initialReuseSeed.archive_memory.reference_note ?
                    'Memoria de uso: ' + initialReuseSeed.archive_memory.reference_note : '',
                ].filter(Boolean).join('\n\n');
            }
            if (initialReuseSeed.channel) {
                setMultiChipSelection('#tab-post .chip[data-channel]', 'channel', [initialReuseSeed.channel]);
            }
            if (initialReuseSeed.tone) {
                setMultiChipSelection('#tab-post .chip[data-tone]', 'tone', [initialReuseSeed.tone]);
            }
        }

        function applyPlaybookSelection(kind) {
            var select = document.getElementById(kind + '-playbook-select');
            var playbook = getPlaybookById(select ? select.value : null);
            renderPlaybookMeta(kind, playbook);

            if (!playbook) return;

            if (kind === 'post') {
                if (playbook.suggested_channel) {
                    setMultiChipSelection('#tab-post .chip[data-channel]', 'channel', [playbook.suggested_channel]);
                }
                if (playbook.default_tones && playbook.default_tones.length) {
                    setMultiChipSelection('#tab-post .chip[data-tone]', 'tone', playbook.default_tones);
                }
                if (playbook.suggested_format) {
                    var formatSelect = document.getElementById('post-format-select');
                    if (formatSelect) formatSelect.value = playbook.suggested_format;
                }
                setValueIfEmpty('post-theme', playbook.starter_text || '');
            }

            if (kind === 'interview') {
                setValueIfEmpty('interview-context', playbook.starter_text || '');
                setValueIfEmpty('interview-sensitive', playbook.sensitive_topics || '');
            }

            if (kind === 'crisis') {
                setValueIfEmpty('crisis-description', playbook.starter_text || '');
            }
        }

        function applyPlaybookFromLibrary(playbookId) {
            var playbook = getPlaybookById(playbookId);
            if (!playbook) return;

            switchTab(playbook.target_tab);

            var select = document.getElementById(playbook.target_tab + '-playbook-select');
            if (select) {
                select.value = playbook.id;
            }

            applyPlaybookSelection(playbook.target_tab);
        }

        function renderTemplateMeta(kind, template) {
            var meta = document.getElementById(kind + '-template-meta');
            if (!meta) return;

            if (!template) {
                meta.textContent = kind === 'image' ?
                    'Use um template visual para reaplicar estilo, formato, paleta e instruções recorrentes de imagem.' :
                    'Escolha um template para reaplicar canal, formato editorial e orientação de texto na geração.';
                return;
            }

            var parts = [];
            if (template.description) parts.push(template.description);
            if (template.channel) parts.push('Canal: ' + template.channel);
            if (template.format) parts.push('Formato: ' + template.format);
            if (template.instruction) parts.push('Guia: ' + template.instruction);
            meta.textContent = parts.join(' · ') || ('Template ativo: ' + template.name);
        }

        function setMultiChipSelection(selector, dataKey, values) {
            var normalized = (values || []).map(function(value) {
                return String(value);
            });

            document.querySelectorAll(selector).forEach(function(chip) {
                chip.classList.toggle('selected', normalized.indexOf(chip.dataset[dataKey]) !== -1);
            });
        }

        function setSingleChipSelection(groupId, dataKey, value) {
            if (!value) return;
            var target = document.querySelector('#' + groupId + ' .chip[data-' + dataKey + '="' + value + '"]');
            if (target) {
                selectOne(target, groupId);
            }
        }

        function applyTemplateSelection(kind) {
            var select = document.getElementById(kind + '-template-select');
            var template = getTemplateById(select ? select.value : null);
            renderTemplateMeta(kind, template);

            if (!template) return;

            if (kind === 'post') {
                if (template.channel) {
                    setMultiChipSelection('#tab-post .chip[data-channel]', 'channel', [template.channel]);
                }
                if (template.default_tones && template.default_tones.length) {
                    setMultiChipSelection('#tab-post .chip[data-tone]', 'tone', template.default_tones);
                }
                if (template.format) {
                    var formatSelect = document.getElementById('post-format-select');
                    if (formatSelect) formatSelect.value = template.format;
                }
            }

            if (kind === 'image') {
                var payload = template.default_payload || {};
                setSingleChipSelection('style-chips', 'style', payload.image_style || '');
                setSingleChipSelection('format-chips', 'format', payload.format || template.format || '');
                setSingleChipSelection('color-chips', 'color', payload.color_tone || '');
            }
        }

        function buildCurrentTemplatePayload(kind) {
            if (kind === 'post') {
                var channels = Array.from(document.querySelectorAll('#tab-post .chip[data-channel].selected')).map(function(
                    chip) {
                    return chip.dataset.channel;
                });
                var tones = Array.from(document.querySelectorAll('#tab-post .chip[data-tone].selected')).map(function(
                    chip) {
                    return chip.dataset.tone;
                });
                var format = document.getElementById('post-format-select').value || null;

                return {
                    kind: 'post',
                    channel: channels[0] || null,
                    format: format,
                    default_tones: tones,
                    default_payload: {},
                };
            }

            if (kind === 'image') {
                var styleChip = document.querySelector('#style-chips .chip.selected');
                var formatChip = document.querySelector('#format-chips .chip.selected');
                var colorChip = document.querySelector('#color-chips .chip.selected');

                return {
                    kind: 'image',
                    channel: 'instagram',
                    format: formatChip ? formatChip.dataset.format : null,
                    default_tones: [],
                    default_payload: {
                        image_style: styleChip ? styleChip.dataset.style : 'moderno',
                        format: formatChip ? formatChip.dataset.format : 'feed',
                        color_tone: colorChip ? colorChip.dataset.color : 'governo',
                    },
                };
            }

            return null;
        }

        function prefillTemplateBuilderFromTab() {
            var activeTab = getActiveTabName();
            var kind = activeTab === 'image' ? 'image' : 'post';
            var builderKind = document.getElementById('template-builder-kind');
            if (builderKind) {
                builderKind.value = kind;
            }

            var selectedTemplate = getTemplateById(document.getElementById(kind + '-template-select') ? document
                .getElementById(kind + '-template-select').value : null);

            if (selectedTemplate) {
                document.getElementById('template-builder-name').value = selectedTemplate.name || '';
                document.getElementById('template-builder-description').value = selectedTemplate.description || '';
                document.getElementById('template-builder-instruction').value = selectedTemplate.instruction || '';
            } else {
                document.getElementById('template-builder-name').value = '';
                document.getElementById('template-builder-description').value = '';
                document.getElementById('template-builder-instruction').value = '';
            }
        }

        async function saveCurrentTemplate() {
            var builderKind = document.getElementById('template-builder-kind');
            var kind = builderKind ? builderKind.value : 'post';
            var name = document.getElementById('template-builder-name').value.trim();
            var description = document.getElementById('template-builder-description').value.trim();
            var instruction = document.getElementById('template-builder-instruction').value.trim();

            if (!name) {
                alert('Informe o nome do template.');
                return;
            }

            var base = buildCurrentTemplatePayload(kind);
            if (!base) {
                alert('Tipo de template inválido.');
                return;
            }

            try {
                var response = await apiFetch('{{ route('mayor.content.templates.store') }}', {
                    name: name,
                    kind: base.kind,
                    channel: base.channel,
                    format: base.format,
                    tone: null,
                    description: description,
                    instruction: instruction,
                    default_tones: base.default_tones,
                    default_payload: base.default_payload,
                    is_active: true
                });
                var data = await response.json();
                if (!data.success) throw new Error(data.error || 'Erro ao salvar template.');

                contentTemplates.unshift(data.template);
                renderTemplateSelectOptions();
                renderTemplateLibrary();

                var select = document.getElementById(kind + '-template-select');
                if (select) {
                    select.value = data.template.id;
                    applyTemplateSelection(kind);
                }

                showToast('✓ Template salvo!');
            } catch (e) {
                showError('Erro ao salvar template: ' + e.message);
            }
        }

        async function deleteTemplate(templateId) {
            if (!confirm('Excluir este template editorial?')) return;

            try {
                var response = await fetch('/mayor/content/templates/' + templateId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json'
                    }
                });
                var data = await response.json();
                if (!data.success) throw new Error(data.error || 'Erro ao excluir template.');

                contentTemplates = contentTemplates.filter(function(template) {
                    return parseInt(template.id, 10) !== parseInt(templateId, 10);
                });

                renderTemplateSelectOptions();
                renderTemplateLibrary();
                showToast('✓ Template removido!');
            } catch (e) {
                showError('Erro ao excluir template: ' + e.message);
            }
        }

        function useTemplateFromLibrary(templateId) {
            var template = getTemplateById(templateId);
            if (!template) return;

            var tabName = template.kind === 'image' ? 'image' : 'post';
            switchTab(tabName);

            var select = document.getElementById(tabName + '-template-select');
            if (select) {
                select.value = template.id;
            }

            applyTemplateSelection(tabName);
        }

        function renderTemplateSelectOptions() {
            ['post', 'image'].forEach(function(kind) {
                var select = document.getElementById(kind + '-template-select');
                if (!select) return;

                var previousValue = select.value;
                var placeholder = kind === 'image' ? 'Sem template fixo' : 'Sem template fixo';
                var options = ['<option value="">' + placeholder + '</option>'];

                contentTemplates.filter(function(template) {
                    return template.kind === kind;
                }).forEach(function(template) {
                    var label = template.name + (template.format ? ' · ' + template.format : '');
                    options.push('<option value="' + template.id + '">' + esc(label) + '</option>');
                });

                select.innerHTML = options.join('');
                select.value = previousValue;
                renderTemplateMeta(kind, getTemplateById(select.value));
            });
        }

        function renderTemplateLibrary() {
            var container = document.getElementById('template-library-list');
            if (!container) return;

            if (!contentTemplates.length) {
                container.innerHTML = '<div class="queue-empty">Nenhum template salvo ainda.</div>';
                return;
            }

            container.innerHTML = contentTemplates.map(function(template) {
                var metaParts = [template.kind_label || template.kind];
                if (template.channel) metaParts.push(template.channel);
                if (template.format) metaParts.push(template.format);

                return '<div class="template-item">' +
                    '<div class="template-item-top">' +
                    '<div>' +
                    '<div class="template-item-title">' + esc(template.name) + '</div>' +
                    '<div class="template-item-meta">' + esc(metaParts.join(' · ')) + '</div>' +
                    '</div>' +
                    '<span class="content-card-status status-draft">' + esc(template.updated_at_human ||
                        'Atualizado agora') +
                    '</span>' +
                    '</div>' +
                    (template.description ? '<div class="template-item-desc">' + esc(template.description) +
                        '</div>' : '') +
                    (template.instruction ? '<div class="template-item-desc">Guia: ' + esc(template.instruction) +
                        '</div>' : '') +
                    '<div class="template-item-actions" style="margin-top:.7rem">' +
                    '<button type="button" class="action-btn" onclick="useTemplateFromLibrary(' + template.id +
                    ')">Aplicar</button>' +
                    '<button type="button" class="action-btn" onclick="deleteTemplate(' + template.id +
                    ')">Excluir</button>' +
                    '</div>' +
                    '</div>';
            }).join('');
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
            const format = document.getElementById('post-format-select').value;
            const templateId = document.getElementById('post-template-select').value || null;
            const playbookId = document.getElementById('post-playbook-select').value || null;
            if (!channels.length) {
                alert('Selecione ao menos um canal.');
                return;
            }
            showSkeleton();
            try {
                const data = await (await apiFetch('{{ route('mayor.content.generate-post') }}', {
                    theme,
                    channels,
                    tones,
                    format,
                    template_id: templateId,
                    playbook_id: playbookId
                })).json();
                if (!data.success) throw new Error(data.error || 'Erro ao gerar');
                if (Array.isArray(data.contents) && data.contents.length > 1) {
                    renderGeneratedPostBatch(data.contents, data.content ? data.content.id : null);
                    showToast('✓ ' + data.contents.length + ' peças geradas em lote!');
                } else {
                    showSinglePostContent(data.content);
                }
            } catch (e) {
                showError('Erro ao gerar post: ' + e.message);
            }
        }

        // ── Gerar Imagem IA ───────────────────────────────────────────
        async function generateImage() {
            const theme = document.getElementById('image-theme').value.trim();
            if (!theme) {
                alert('Descreva o tema da imagem.');
                return;
            }
            const styleChip = document.querySelector('#style-chips .chip.selected');
            const formatChip = document.querySelector('#format-chips .chip.selected');
            const colorChip = document.querySelector('#color-chips .chip.selected');
            const style = styleChip ? styleChip.dataset.style : 'moderno';
            const format = formatChip ? formatChip.dataset.format : 'feed';
            const color = colorChip ? colorChip.dataset.color : 'governo';
            const templateId = document.getElementById('image-template-select').value || null;
            showImageSkeleton();
            try {
                const data = await (await apiFetch('{{ route('mayor.content.generate-image') }}', {
                    theme,
                    image_style: style,
                    format,
                    color_tone: color,
                    template_id: templateId
                })).json();
                if (!data.success) throw new Error(data.error || 'Erro desconhecido');
                renderImageResults(data.content);
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
                    sensitive_topics: document.getElementById('interview-sensitive').value,
                    playbook_id: document.getElementById('interview-playbook-select').value || null
                })).json();
                if (!data.success) throw new Error(data.error || 'Erro ao gerar');
                renderTextResult(data.content);
            } catch (e) {
                showError('Erro ao gerar preparação: ' + e.message);
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
                    crisis_description: description,
                    playbook_id: document.getElementById('crisis-playbook-select').value || null
                })).json();
                if (!data.success) throw new Error(data.error || 'Erro ao processar');
                renderTextResult(data.content);
            } catch (e) {
                showError('Erro ao processar: ' + e.message);
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
                    renderImageResults(data);
                } else if (data.variations && data.variations.length) {
                    showSinglePostContent(data);
                } else {
                    renderTextResult(data);
                }
            } catch (e) {
                alert('Erro ao carregar.');
            }
        }

        function focusResultsArea() {
            var section = document.getElementById('content-review-section');
            if (!section) return;
            section.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }

        function initOperationsDragAndDrop() {
            if (currentArea !== 'operations') return;

            document.querySelectorAll('[data-operation-card]').forEach(function(card) {
                card.addEventListener('dragstart', function() {
                    draggedOperationCardId = card.getAttribute('data-operation-card');
                    card.style.opacity = '0.55';
                });

                card.addEventListener('dragend', function() {
                    draggedOperationCardId = null;
                    card.style.opacity = '';
                    document.querySelectorAll('[data-operation-dropzone]').forEach(function(zone) {
                        zone.style.background = '';
                    });
                });
            });

            document.querySelectorAll('[data-operation-dropzone]').forEach(function(zone) {
                zone.addEventListener('dragover', function(event) {
                    event.preventDefault();
                    zone.style.background = 'rgba(37,99,235,.05)';
                });

                zone.addEventListener('dragleave', function() {
                    zone.style.background = '';
                });

                zone.addEventListener('drop', async function(event) {
                    event.preventDefault();
                    zone.style.background = '';

                    if (!draggedOperationCardId) return;

                    var columnKey = zone.getAttribute('data-operation-dropzone');
                    if (!columnKey) return;

                    try {
                        var data = await (await apiFetch('/mayor/content/operations/' +
                            draggedOperationCardId + '/move', {
                                column_key: columnKey
                            })).json();

                        if (!data.success) throw new Error(data.error || 'Erro ao mover demanda');

                        showToast('✓ Pauta movida para ' + (data.column_label || 'a nova coluna') +
                            '!');
                        window.location.href =
                            '{{ route('mayor.content.index', ['area' => 'operations']) }}';
                    } catch (e) {
                        showError('Erro ao mover pauta: ' + e.message);
                    }
                });
            });
        }

        function showSinglePostContent(contentData) {
            generatedPostBatch = [];
            renderPostCard(contentData);
        }

        function upsertGeneratedBatchContent(contentData) {
            if (!contentData || !contentData.id || !generatedPostBatch.length) return;

            generatedPostBatch = generatedPostBatch.map(function(item) {
                return String(item.id) === String(contentData.id) ? contentData : item;
            });
        }

        function renderGeneratedPostBatch(contents, selectedId) {
            generatedPostBatch = Array.isArray(contents) ? contents.slice() : [];
            if (!generatedPostBatch.length) return;

            var selected = generatedPostBatch.find(function(item) {
                return String(item.id) === String(selectedId);
            }) || generatedPostBatch[0];

            renderPostCard(selected);
            renderGeneratedPostBatchSelector(selected.id);
        }

        function renderGeneratedPostBatchSelector(activeId) {
            if (generatedPostBatch.length < 2) return;

            var panel = document.getElementById('resultsPanel');
            if (!panel) return;

            var selector = document.createElement('div');
            selector.style.cssText =
                'display:flex;flex-direction:column;gap:.75rem;margin-bottom:1rem;padding:1rem;border:1px solid rgba(15,23,42,.08);border-radius:16px;background:#f8fafc';
            selector.innerHTML =
                '<div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap">' +
                '<div><div style="font-weight:700;color:var(--ink)">Lote multicanal gerado</div>' +
                '<div style="font-size:.9rem;color:var(--muted)">Abra cada canal no editor mantendo a mesma base temática e os mesmos tons.</div></div>' +
                '<div style="font-size:.85rem;color:var(--muted)">' + generatedPostBatch.length + ' canais gerados</div>' +
                '</div>' +
                '<div style="display:flex;gap:.6rem;flex-wrap:wrap">' +
                generatedPostBatch.map(function(item) {
                    var isActive = String(item.id) === String(activeId);
                    var label = esc(item.channel_label || ucFirst(item.channel || 'canal'));
                    var toneCount = Array.isArray(item.variations) ? item.variations.length : 0;
                    return '<button type="button" onclick="openGeneratedPostFromBatch(' + item.id +
                        ')" style="border:1px solid ' +
                        (isActive ? 'rgba(37,99,235,.35)' : 'rgba(148,163,184,.35)') +
                        ';background:' + (isActive ? 'rgba(37,99,235,.08)' : '#fff') +
                        ';color:' + (isActive ? 'var(--brand)' : 'var(--ink)') +
                        ';border-radius:999px;padding:.55rem .9rem;font-weight:700;cursor:pointer">' +
                        label + ' · ' + toneCount + ' tons</button>';
                }).join('') +
                '</div>';

            panel.insertBefore(selector, panel.firstChild);
        }

        function openGeneratedPostFromBatch(contentId) {
            var selected = generatedPostBatch.find(function(item) {
                return String(item.id) === String(contentId);
            });

            if (!selected) return;

            renderGeneratedPostBatch(generatedPostBatch, selected.id);
        }

        // ═══════════════════════════════════════════════════════════════
        // RENDER IMAGEM — sem mistura innerHTML+= / appendChild
        // ═══════════════════════════════════════════════════════════════
        function renderImageResults(contentData) {
            currentContent = contentData;
            generatedPostBatch = [];
            const panel = clearResults();
            const prompts = contentData.prompts || [];
            const tips = contentData.design_tips || [];
            focusResultsArea();

            // Título
            const hdr = document.createElement('div');
            hdr.className = 'img-results-header';
            hdr.innerHTML = '<h3>' + esc(contentData.title || 'Prompts de imagem gerados') + '</h3><span>' + prompts
                .length +
                ' op\u00e7\u00f5es criadas · ' + renderStatusText(contentData.status) + '</span>';
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

            panel.appendChild(buildArchiveInfoPanel());
            panel.appendChild(buildArchiveMemoryPanel());
            panel.appendChild(buildCollaborationPanel());
            panel.appendChild(buildEditorialActions());

            // Copiar tudo de um card (prompt + legenda + hashtags)
            function copyAll(i) {
                var promptEl = document.getElementById('pt-' + i);
                var negativeEl = document.getElementById('pn-' + i);
                var captionEl = document.getElementById('pc-' + i);
                var hashtagsEl = document.getElementById('ph-' + i);
                var prompt = promptEl ? promptEl.innerText : '';
                var negative = negativeEl ? negativeEl.innerText : '';
                var caption = captionEl ? captionEl.innerText : '';
                var hashtags = hashtagsEl ? hashtagsEl.innerText : '';
                var full = 'PROMPT:\n' + prompt + '\n\nNEGATIVE PROMPT:\n' + negative + '\n\nLEGENDA:\n' + caption +
                    '\n\nHASHTAGS:\n' + hashtags;
                navigator.clipboard.writeText(full);
                showToast('✓ Tudo copiado!');
            }

        }

        // ── Render: post ──────────────────────────────────────────────
        function renderPostCard(data) {
            currentContent = data;
            activeVar = 'var-0';
            const panel = clearResults();
            const variations = data.variations || [{
                tone: 'geral',
                content: data.content
            }];
            focusResultsArea();
            const hdr = document.createElement('div');
            hdr.style.cssText = 'display:flex;align-items:center;justify-content:space-between';
            hdr.innerHTML = '<h3 style="font-family:\'Outfit\',sans-serif;font-size:1rem;color:var(--ink)">' + esc(data
                .title ||
                'Conteúdo gerado') + '</h3>';
            panel.appendChild(hdr);
            const card = document.createElement('div');
            card.className = 'content-card';
            const channelKey = data.channel || 'instagram';
            card.innerHTML =
                '<div class="content-card-header">' +
                '<div class="channel-icon ' + channelKey + '">' + channelKey.slice(0, 2).toUpperCase() + '</div>' +
                '<div class="content-card-meta">' +
                '<div class="content-card-title">' + channelKey.charAt(0).toUpperCase() + channelKey.slice(1) +
                '</div>' +
                '<div class="content-card-info">' + variations.length + ' variação' + (variations.length > 1 ? 'ões' :
                    '') +
                '</div>' +
                '</div>' +
                '<span class="content-card-status ' + renderStatusClass(data.status) + '">' + renderStatusText(data
                    .status) + '</span>' +
                '</div>' +
                '<div class="variation-tabs">' +
                variations.map(function(v, i) {
                    return '<button class="var-tab ' + (i === 0 ? 'active' : '') +
                        '" onclick="switchVar(this,\'var-' +
                        i + '\')">' + ucFirst(v.tone) + '</button>';
                }).join('') +
                '</div>' +
                variations.map(function(v, i) {
                    return '<div id="var-' + i + '" class="variation-content" style="' + (i > 0 ? 'display:none' :
                            '') +
                        '">' +
                        '<p class="post-text" id="tv-' + i + '">' + esc(v.content) + '</p></div>';
                }).join('') +
                '<div class="content-card-actions">' +
                '<button class="action-btn" onclick="copyActivePost()">Copiar</button>' +
                '<button class="action-btn" onclick="editActivePost()">Editar / salvar</button>' +
                '</div>';
            panel.appendChild(card);
            panel.appendChild(buildArchiveInfoPanel());
            panel.appendChild(buildHistoricalCheckPanel());
            panel.appendChild(buildArchiveMemoryPanel());
            panel.appendChild(buildRefinementPanel());
            panel.appendChild(buildEditorialActions());
            panel.appendChild(buildCollaborationPanel());
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
                if (el.isContentEditable) {
                    saveCurrentContent(el.innerText);
                    el.contentEditable = 'false';
                    return;
                }
                el.contentEditable = 'true';
                el.focus();
            }
        }

        // ── Render: texto ─────────────────────────────────────────────
        function renderTextResult(contentData) {
            currentContent = contentData;
            generatedPostBatch = [];
            const panel = clearResults();
            focusResultsArea();
            const el = document.createElement('div');
            el.className = 'crisis-result';
            el.innerHTML =
                '<h4><svg viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13z"/></svg>' +
                esc(contentData.title || 'Conteúdo gerado') + ' · ' + renderStatusText(contentData.status) + '</h4>' +
                '<div class="crisis-result-body" id="crbody">' + esc(contentData.content || '') + '</div>' +
                '<div style="margin-top:1rem;display:flex;gap:.5rem;flex-wrap:wrap"><button class="action-btn" onclick="copyById(\'crbody\',\'Copiado!\')">Copiar</button><button class="action-btn" onclick="saveCurrentContent(document.getElementById(\'crbody\').innerText)">Salvar edição</button></div>';
            panel.appendChild(el);
            document.getElementById('crbody').contentEditable = 'true';
            panel.appendChild(buildArchiveInfoPanel());
            panel.appendChild(buildHistoricalCheckPanel());
            panel.appendChild(buildCrisisEvolutionPanel());
            panel.appendChild(buildArchiveMemoryPanel());
            panel.appendChild(buildRefinementPanel());
            panel.appendChild(buildEditorialActions());
            panel.appendChild(buildCollaborationPanel());
        }

        // ── Skeletons ─────────────────────────────────────────────────
        function showSkeleton() {
            focusResultsArea();
            clearResults().innerHTML =
                '<div style="background:var(--white);border:1px solid var(--border);border-radius:12px;padding:1.2rem">' +
                '<div style="display:flex;gap:.75rem;margin-bottom:1rem"><div class="skel" style="width:30px;height:30px;border-radius:7px"></div>' +
                '<div style="flex:1"><div class="skel" style="height:12px;width:60%;margin-bottom:.4rem"></div><div class="skel" style="height:10px;width:40%"></div></div></div>' +
                '<div class="skel" style="height:11px;margin-bottom:.5rem"></div>' +
                '<div class="skel" style="height:11px;width:85%;margin-bottom:.5rem"></div>' +
                '<div class="skel" style="height:11px;width:70%"></div></div>';
        }

        function showImageSkeleton() {
            focusResultsArea();
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
            if (!p) {
                return document.createElement('div');
            }
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

        function refreshPagePreservingFocus(delay) {
            window.setTimeout(function() {
                var url = new URL(window.location.href);
                if (currentContent && currentContent.id) {
                    url.searchParams.set('content', currentContent.id);
                }
                window.location.href = url.toString();
            }, delay || 500);
        }

        async function saveCurrentContent(contentText) {
            if (!currentContent || !currentContent.id) return;
            let variations = currentContent.variations || [];
            if (variations.length) {
                const index = Math.max(parseInt((activeVar || 'var-0').replace('var-', ''), 10) || 0, 0);
                variations = variations.map(function(item, itemIndex) {
                    if (itemIndex === index) {
                        return {
                            tone: item.tone,
                            content: contentText
                        };
                    }
                    return item;
                });
            }
            const payload = {
                title: currentContent.title,
                content: contentText,
                variations: variations
            };
            const response = await fetch('/mayor/content/' + currentContent.id, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            if (data.success) {
                currentContent = data.content;
                showToast('✓ Conteúdo salvo!');
                reloadCurrentContent(currentContent);
            }
        }

        async function saveArchiveMemory() {
            if (!currentContent || !currentContent.id) return;
            var referenceEl = document.getElementById('archive-reference-note');
            var outcomeEl = document.getElementById('archive-outcome-note');
            const response = await fetch('/mayor/content/' + currentContent.id, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    archive_reference_note: referenceEl ? referenceEl.value.trim() : '',
                    archive_outcome_note: outcomeEl ? outcomeEl.value.trim() : ''
                }),
            });
            const data = await response.json();
            if (data.success) {
                currentContent = data.content;
                showToast('✓ Memória do arquivo salva!');
                reloadCurrentContent(currentContent);
            }
        }

        async function approveCurrentContent() {
            if (!currentContent || !currentContent.id) return;
            const data = await (await apiFetch('/mayor/content/' + currentContent.id + '/approve', {})).json();
            if (data.success) {
                reloadCurrentContent(data.content);
                showToast('✓ Conteúdo aprovado!');
            }
        }

        async function publishCurrentContent() {
            if (!currentContent || !currentContent.id) return;
            const publishedUrl = window.prompt('URL da publicação (opcional):', currentContent.published_url ||
                    '') ||
                '';
            const data = await (await apiFetch('/mayor/content/' + currentContent.id + '/publish', {
                published_url: publishedUrl
            })).json();
            if (data.success) {
                reloadCurrentContent(data.content);
                showToast('✓ Conteúdo publicado!');
            }
        }

        async function scheduleCurrentContent() {
            if (!currentContent || !currentContent.id) return;
            const currentPlanned = currentContent.planned_at ? currentContent.planned_at.slice(0, 16) : '';
            const plannedAt = window.prompt('Data e hora do agendamento (AAAA-MM-DDTHH:MM):', currentPlanned);
            if (plannedAt === null) return;

            const editorialPromptResult = window.prompt('Observação editorial (opcional):', currentContent
                .editorial_note || '');
            const editorialNote = editorialPromptResult === null ? '' : editorialPromptResult;
            const data = await (await apiFetch('/mayor/content/' + currentContent.id + '/schedule', {
                planned_at: plannedAt.trim() || null,
                editorial_note: editorialNote.trim() || null,
            })).json();

            if (data.success) {
                reloadCurrentContent(data.content);
                showToast(plannedAt.trim() ? '✓ Conteúdo agendado!' : '✓ Agendamento removido!');
                refreshPagePreservingFocus(550);
            }
        }

        async function reorderScheduledContent(event, contentId, direction) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            try {
                var response = await apiFetch('/mayor/content/' + contentId + '/reorder-schedule', {
                    direction: direction
                });
                var data = await response.json();
                if (!data.success) throw new Error(data.error || 'Erro ao reordenar agenda.');

                if (currentContent && parseInt(currentContent.id, 10) === parseInt(contentId, 10)) {
                    currentContent = data.content;
                }

                showToast(direction === 'up' ? '✓ Peça movida para cima!' : '✓ Peça movida para baixo!');
                refreshPagePreservingFocus(450);
            } catch (e) {
                showError('Erro ao reordenar agenda: ' + e.message);
            }
        }

        async function archiveCurrentContent() {
            if (!currentContent || !currentContent.id) return;
            const data = await (await apiFetch('/mayor/content/' + currentContent.id + '/archive', {})).json();
            if (data.success) {
                reloadCurrentContent(data.content);
                showToast('✓ Conteúdo arquivado!');
            }
        }

        async function submitCollaboration(action) {
            if (!currentContent || !currentContent.id) return;

            var noteEl = document.getElementById('collab-note');
            var note = noteEl ? noteEl.value.trim() : '';
            if (!note) {
                alert('Escreva uma observação para registrar essa ação.');
                return;
            }

            try {
                var data = await (await apiFetch('/mayor/content/' + currentContent.id + '/collaborate', {
                    action: action,
                    note: note
                })).json();

                if (!data.success) throw new Error(data.error || 'Erro ao registrar colaboração.');
                reloadCurrentContent(data.content);
                showToast(action === 'approved' ? '✓ Aprovação registrada!' : (action === 'changes_requested' ?
                    '✓ Pedido de ajuste registrado!' : '✓ Observação registrada!'));
            } catch (e) {
                showError('Erro ao registrar colaboração: ' + e.message);
            }
        }

        function buildCollaborationPanel() {
            if (!currentContent) {
                return document.createElement('div');
            }

            var wrap = document.createElement('div');
            wrap.className = 'collab-card';
            var summary = currentContent.collaboration_summary || {};
            var entries = currentContent.collaboration_entries || [];

            wrap.innerHTML =
                '<div class="collab-head">' +
                '<div>' +
                '<h3 class="collab-title">Aprovação colaborativa</h3>' +
                '<p class="collab-subtitle">Registre observações, rejeite com justificativa ou aprove com nota para manter o histórico editorial da equipe.</p>' +
                '</div>' +
                '<span class="content-card-status ' + renderStatusClass(currentContent.status) + '">' +
                renderStatusText(
                    currentContent.status) + '</span>' +
                '</div>' +
                '<div class="collab-summary">' +
                '<span class="collab-chip">Total ' + (summary.total || 0) + '</span>' +
                '<span class="collab-chip">Aprovações ' + (summary.approvals || 0) + '</span>' +
                '<span class="collab-chip">Ajustes ' + (summary.changes_requested || 0) + '</span>' +
                '<span class="collab-chip">Observações ' + (summary.observations || 0) + '</span>' +
                '</div>' +
                '<div class="field" style="margin-bottom:0">' +
                '<label>Observação da revisão</label>' +
                '<textarea id="collab-note" rows="3" placeholder="Ex: reforçar o dado principal, ajustar o tom, aprovar para publicação ou pedir revisão específica."></textarea>' +
                '</div>' +
                '<div class="collab-actions">' +
                '<button class="action-btn" type="button" onclick="submitCollaboration(\'observation\')">Registrar observação</button>' +
                '<button class="action-btn" type="button" onclick="submitCollaboration(\'changes_requested\')">Rejeitar com justificativa</button>' +
                '<button class="action-btn primary" type="button" onclick="submitCollaboration(\'approved\')">Aprovar com observação</button>' +
                '</div>' +
                '<div class="collab-list">' +
                (entries.length ? entries.map(function(entry) {
                    return '<div class="collab-entry">' +
                        '<div class="collab-entry-head">' +
                        '<strong>' + esc(entry.user_name || 'Operador') + '</strong>' +
                        '<span class="performance-status-badge status-' + collaborationStatusClass(entry
                            .action) + '">' +
                        esc(entry.action_label || 'Observou') + '</span>' +
                        '</div>' +
                        '<div class="collab-entry-meta">' +
                        '<span>' + esc(entry.user_role || 'Equipe') + '</span>' +
                        '<span>' + esc(entry.created_at_human || '') + '</span>' +
                        '</div>' +
                        '<div class="collab-entry-note">' + esc(entry.note || '') + '</div>' +
                        '</div>';
                }).join('') : '<div class="queue-empty">Nenhuma observação colaborativa registrada ainda.</div>') +
                '</div>';

            return wrap;
        }

        function buildRefinementPanel() {
            if (!currentContent || !currentContent.is_text_refinable) {
                return document.createElement('div');
            }

            const wrap = document.createElement('div');
            wrap.className = 'assist-card';
            const lastAction = currentContent.last_editorial_ai_action || null;
            const lastActionText = lastAction ?
                'Última ação IA: ' + (lastAction.type === 'variations' ? 'novas variações' : 'refino') +
                (lastAction.executed_at ? ' · ' + formatIsoDate(lastAction.executed_at) : '') +
                (lastAction.instruction ? ' · ' + esc(lastAction.instruction) : '') : '';

            wrap.innerHTML =
                '<div class="assist-head">' +
                '<div>' +
                '<h3 class="assist-title">Refino Assistido</h3>' +
                '<p class="assist-subtitle">Use a IA para lapidar o texto atual ou abrir novas opções editoriais sem perder o fato central.</p>' +
                '</div>' +
                '<div class="assist-badge">IA editorial</div>' +
                '</div>' +
                '<div class="assist-grid">' +
                '<div class="field" style="margin-bottom:0">' +
                '<label>Preset</label>' +
                '<select id="refine-preset">' +
                '<option value="keep">Sem preset</option>' +
                '<option value="enxugar">Enxugar</option>' +
                '<option value="humanizar">Humanizar</option>' +
                '<option value="institucional">Institucional</option>' +
                '<option value="impacto">Mais impacto</option>' +
                '<option value="instagram">Mais publicável</option>' +
                '</select>' +
                '</div>' +
                '<div class="field" style="margin-bottom:0">' +
                '<label>Tom alvo</label>' +
                '<select id="refine-tone">' +
                '<option value="">Manter atual</option>' +
                '<option value="celebratorio">Celebratório</option>' +
                '<option value="tecnico">Técnico</option>' +
                '<option value="empatico">Empático</option>' +
                '<option value="informativo">Informativo</option>' +
                '<option value="institucional">Institucional</option>' +
                '</select>' +
                '</div>' +
                '<div class="field" style="margin-bottom:0">' +
                '<label>Orientação adicional</label>' +
                '<textarea id="refine-instruction" rows="2" placeholder="Ex: deixe mais curto, abra com impacto e feche com prestação de contas concreta."></textarea>' +
                '</div>' +
                '</div>' +
                '<div class="assist-actions">' +
                '<button class="action-btn primary" type="button" onclick="refineCurrentContent()">Refinar texto atual</button>' +
                (currentContent.is_post_like ?
                    '<button class="action-btn" type="button" onclick="generateAssistedVariations()">Gerar novas variações</button>' :
                    '') +
                '<button class="action-btn" type="button" onclick="applyAssistSuggestion(\'Deixe mais curto e fácil de publicar.\')">Mais curto</button>' +
                '<button class="action-btn" type="button" onclick="applyAssistSuggestion(\'Deixe mais humano e próximo do cidadão.\')">Mais humano</button>' +
                '<button class="action-btn" type="button" onclick="applyAssistSuggestion(\'Reforce entrega concreta e impacto para a população.\')">Mais impacto</button>' +
                '</div>' +
                (lastActionText ? '<div class="assist-meta">' + lastActionText + '</div>' : '');

            return wrap;
        }

        function applyAssistSuggestion(text) {
            var input = document.getElementById('refine-instruction');
            if (!input) return;
            input.value = text;
            input.focus();
        }

        function composeAssistInstruction() {
            var presetEl = document.getElementById('refine-preset');
            var noteEl = document.getElementById('refine-instruction');
            var presetValue = presetEl ? presetEl.value : 'keep';
            var presetText = REFINE_PRESETS[presetValue] || '';
            var note = noteEl ? noteEl.value.trim() : '';
            return [presetText, note].filter(Boolean).join(' ');
        }

        function currentVariationIndex() {
            if (!currentContent || !currentContent.variations || !currentContent.variations.length) {
                return null;
            }

            return Math.max(parseInt((activeVar || 'var-0').replace('var-', ''), 10) || 0, 0);
        }

        function currentEditableText() {
            var postEl = document.querySelector('#' + activeVar + ' .post-text');
            if (postEl) {
                return postEl.innerText;
            }

            var bodyEl = document.getElementById('crbody');
            if (bodyEl) {
                return bodyEl.innerText;
            }

            return currentContent && currentContent.content ? currentContent.content : '';
        }

        async function refineCurrentContent() {
            if (!currentContent || !currentContent.id) return;

            var instruction = composeAssistInstruction();
            if (!instruction) {
                alert('Descreva como a IA deve ajustar o texto.');
                return;
            }

            var toneEl = document.getElementById('refine-tone');
            showSkeleton();

            try {
                var data = await (await apiFetch('/mayor/content/' + currentContent.id + '/refine', {
                    instruction: instruction,
                    selected_text: currentEditableText(),
                    target_tone: toneEl ? toneEl.value : '',
                    target_channel: currentContent.channel || '',
                    variation_index: currentVariationIndex()
                })).json();

                if (!data.success) throw new Error(data.error || 'Erro ao refinar');
                reloadCurrentContent(data.content);
                showToast('✓ Texto refinado!');
            } catch (e) {
                showError('Erro ao refinar: ' + e.message);
            }
        }

        async function generateAssistedVariations() {
            if (!currentContent || !currentContent.id) return;

            var instruction = composeAssistInstruction();
            var pack = window.prompt('Pacote de variações: balanced, social ou institucional', 'balanced') ||
                'balanced';
            var tones = VARIATION_PACKS[pack] || VARIATION_PACKS.balanced;
            showSkeleton();

            try {
                var data = await (await apiFetch('/mayor/content/' + currentContent.id + '/variations', {
                    instruction: instruction,
                    base_text: currentEditableText(),
                    target_channel: currentContent.channel || '',
                    tones: tones
                })).json();

                if (!data.success) throw new Error(data.error || 'Erro ao gerar variações');
                reloadCurrentContent(data.content);
                showToast('✓ Novas variações geradas!');
            } catch (e) {
                showError('Erro ao gerar variações: ' + e.message);
            }
        }

        function reloadCurrentContent(contentData) {
            upsertGeneratedBatchContent(contentData);

            if (contentData.type === 'imagem_instagram') {
                renderImageResults(contentData);
            } else if (contentData.variations && contentData.variations.length) {
                if (generatedPostBatch.some(function(item) {
                        return String(item.id) === String(contentData.id);
                    })) {
                    renderGeneratedPostBatch(generatedPostBatch, contentData.id);
                } else {
                    showSinglePostContent(contentData);
                }
            } else {
                renderTextResult(contentData);
            }
        }

        function slaStatusClass(statusKey) {
            return 'sla-status-' + (statusKey || 'on_track');
        }

        function buildCurrentSlaPanel() {
            var sla = currentContent && currentContent.sla ? currentContent.sla : null;
            if (!sla) return '';

            var dueLabel = sla.due_at_human || 'Sem prazo ativo';
            var elapsedLabel = typeof sla.hours_elapsed === 'number' ? sla.hours_elapsed + 'h decorridas' : '';

            return '<div class="workflow-sla">' +
                '<div class="workflow-sla-top">' +
                '<span class="sla-badge ' + slaStatusClass(sla.status_key) + '">' + esc(sla.status_label ||
                    'Dentro do SLA') + '</span>' +
                '<span class="workflow-sla-stage">' + esc(sla.stage_label || 'Etapa atual') + '</span>' +
                '</div>' +
                '<div class="workflow-sla-meta">' +
                '<span>Resumo: ' + esc(sla.summary || 'Sem leitura de SLA') + '</span>' +
                '<span>Limite: ' + esc(dueLabel) + '</span>' +
                '</div>' +
                '<div class="workflow-sla-meta">' +
                '<span>Etapa ativa: ' + esc(sla.stage_label || 'Etapa atual') + '</span>' +
                '<span>' + esc(elapsedLabel) + '</span>' +
                '</div>' +
                '</div>';
        }

        function buildHistoricalCheckPanel() {
            var historicalCheck = currentContent && currentContent.historical_check ? currentContent.historical_check :
                null;
            if (!historicalCheck || !historicalCheck.summary) {
                return document.createDocumentFragment();
            }

            var references = Array.isArray(currentContent.historical_references) ? currentContent.historical_references :
        [];
            var wrap = document.createElement('div');
            var isAttention = String(historicalCheck.status || 'ok') === 'attention';
            wrap.style.cssText =
                'margin-top:1rem;padding:1rem 1.1rem;border-radius:16px;border:1px solid ' +
                (isAttention ? 'rgba(234,88,12,.25)' : 'rgba(16,185,129,.22)') +
                ';background:' + (isAttention ? 'rgba(255,247,237,.96)' : 'rgba(236,253,245,.95)');

            wrap.innerHTML =
                '<div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap">' +
                '<div><div style="font-weight:700;color:var(--ink)">Consistência histórica</div>' +
                '<div style="margin-top:.35rem;color:var(--muted);font-size:.92rem">' + esc(historicalCheck.summary) +
                '</div></div>' +
                '<span style="padding:.35rem .7rem;border-radius:999px;font-size:.78rem;font-weight:700;background:' +
                (isAttention ? 'rgba(234,88,12,.14)' : 'rgba(16,185,129,.14)') +
                ';color:' + (isAttention ? '#c2410c' : '#047857') + '">' + (isAttention ? 'Atenção' : 'Coerente') +
                '</span>' +
                '</div>' +
                (references.length ? '<div style="margin-top:.8rem;display:grid;gap:.55rem">' + references.map(function(
                    reference) {
                    return '<div style="padding:.7rem .8rem;border-radius:12px;background:#fff;border:1px solid rgba(15,23,42,.06)">' +
                        '<div style="font-weight:600;color:var(--ink)">' + esc(reference.title || 'Peça anterior') +
                        '</div>' +
                        '<div style="font-size:.84rem;color:var(--muted);margin-top:.2rem">Canal: ' + esc(reference
                            .channel || 'interno') +
                        ' · Status: ' + esc(reference.status || 'draft') +
                        (reference.updated_at ? ' · Atualizado em ' + esc(reference.updated_at) : '') + '</div>' +
                        (reference.summary ? '<div style="font-size:.88rem;color:var(--ink);margin-top:.35rem">' +
                            esc(reference.summary) + '</div>' : '') +
                        '</div>';
                }).join('') + '</div>' : '');

            return wrap;
        }

        function buildCrisisEvolutionPanel() {
            if (!currentContent || currentContent.type !== 'crise') {
                return document.createDocumentFragment();
            }

            var crisisPlan = currentContent.crisis_plan || null;
            var sections = crisisPlan && Array.isArray(crisisPlan.sections) ? crisisPlan.sections : [];
            var iterations = crisisPlan && Array.isArray(crisisPlan.iterations) ? crisisPlan.iterations.slice(0, 4) : [];
            var wrap = document.createElement('div');
            wrap.style.cssText =
                'margin-top:1rem;padding:1rem 1.1rem;border-radius:18px;border:1px solid rgba(15,23,42,.08);background:#fff';

            wrap.innerHTML =
                '<div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap">' +
                '<div><div style="font-weight:700;color:var(--ink)">Roteiro de crise evolutivo</div>' +
                '<div style="margin-top:.3rem;color:var(--muted);font-size:.92rem">' +
                esc(crisisPlan && crisisPlan.summary ? crisisPlan.summary :
                    'Atualize apenas as seções impactadas quando surgir um fato novo.') +
                '</div></div>' +
                '<div style="font-size:.84rem;color:var(--muted)">' +
                (crisisPlan && crisisPlan.updated_by ? 'Última atualização por ' + esc(crisisPlan.updated_by) :
                    'Roteiro pronto para evolução incremental') +
                '</div></div>' +
                (sections.length ? '<div style="display:grid;gap:.7rem;margin-top:1rem">' + sections.map(function(section) {
                    return '<label style="display:block;padding:.8rem .9rem;border:1px solid rgba(15,23,42,.08);border-radius:14px;background:#f8fafc;cursor:pointer">' +
                        '<div style="display:flex;gap:.7rem;align-items:flex-start">' +
                        '<input type="checkbox" class="crisis-section-check" value="' + esc(section.key) +
                        '" style="margin-top:.2rem"' +
                        (section.key === 'positioning' || section.key === 'next_steps' ? ' checked' : '') + '>' +
                        '<div style="flex:1">' +
                        '<div style="font-weight:700;color:var(--ink)">' + esc(section.label) + '</div>' +
                        '<div style="font-size:.9rem;color:var(--muted);margin-top:.25rem">' + esc(section
                            .content || 'Sem conteúdo nesta seção.') + '</div>' +
                        '</div></div></label>';
                }).join('') + '</div>' : '') +
                '<div style="margin-top:1rem">' +
                '<label for="crisis-evolution-context" style="display:block;font-weight:700;color:var(--ink);margin-bottom:.45rem">Novo fato ou mudança de cenário</label>' +
                '<textarea id="crisis-evolution-context" rows="4" placeholder="Ex: saiu nova nota da imprensa, houve fala de oposição, apareceu novo vídeo, equipe técnica confirmou novo dado..." style="width:100%;padding:.75rem .85rem;border:1px solid #d1d5db;border-radius:12px;resize:vertical;box-sizing:border-box"></textarea>' +
                '</div>' +
                '<div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:.9rem">' +
                '<button type="button" class="action-btn primary" onclick="evolveCurrentCrisis()">Evoluir roteiro</button>' +
                '<button type="button" class="action-btn" onclick="prefillCrisisEvolutionAll()">Marcar todas as seções</button>' +
                '</div>' +
                (iterations.length ? '<div style="margin-top:1rem">' +
                    '<div style="font-weight:700;color:var(--ink);margin-bottom:.55rem">Histórico recente de evoluções</div>' +
                    '<div style="display:grid;gap:.55rem">' + iterations.map(function(iteration) {
                        return '<div style="padding:.75rem .85rem;border-radius:12px;background:#f8fafc;border:1px solid rgba(15,23,42,.06)">' +
                            '<div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap">' +
                            '<div style="font-weight:600;color:var(--ink)">' + esc(iteration.summary ||
                                'Evolução registrada') + '</div>' +
                            '<div style="font-size:.82rem;color:var(--muted)">' + esc(iteration.created_at_human ||
                                '') + '</div>' +
                            '</div>' +
                            (iteration.update_context ?
                                '<div style="margin-top:.35rem;font-size:.9rem;color:var(--muted)">' + esc(iteration
                                    .update_context) + '</div>' : '') +
                            '</div>';
                    }).join('') + '</div></div>' : '');

            return wrap;
        }

        function prefillCrisisEvolutionAll() {
            document.querySelectorAll('.crisis-section-check').forEach(function(input) {
                input.checked = true;
            });
        }

        async function evolveCurrentCrisis() {
            if (!currentContent || currentContent.type !== 'crise') return;

            var contextField = document.getElementById('crisis-evolution-context');
            var updateContext = contextField ? contextField.value.trim() : '';
            if (!updateContext) {
                alert('Descreva o fato novo ou a mudança de cenário.');
                return;
            }

            var affectedSections = Array.from(document.querySelectorAll('.crisis-section-check:checked')).map(function(
                input) {
                return input.value;
            });

            if (!affectedSections.length) {
                alert('Selecione ao menos uma seção impactada.');
                return;
            }

            showSkeleton();

            try {
                var playbookSelect = document.getElementById('crisis-playbook-select');
                var data = await (await apiFetch('/mayor/content/' + currentContent.id + '/crisis-evolve', {
                    update_context: updateContext,
                    affected_sections: affectedSections,
                    playbook_id: playbookSelect ? (playbookSelect.value || null) : null
                })).json();

                if (!data.success) throw new Error(data.error || 'Erro ao evoluir roteiro de crise');

                reloadCurrentContent(data.content);
                showToast('✓ Roteiro de crise evoluído!');
            } catch (e) {
                showError('Erro ao evoluir crise: ' + e.message);
            }
        }

        function buildCurrentPlaybookPanel() {
            var playbook = currentContent && currentContent.playbook ? currentContent.playbook : null;
            if (!playbook || !playbook.name) return '';

            var checklist = Array.isArray(playbook.checklist) ? playbook.checklist : [];
            var workflow = Array.isArray(playbook.workflow) ? playbook.workflow : [];

            return '<div class="workflow-playbook">' +
                '<div class="workflow-playbook-top">' +
                '<div>' +
                '<div class="workflow-playbook-title">Playbook ativo: ' + esc(playbook.name) + '</div>' +
                '<div class="workflow-playbook-meta">' + esc(playbook.situation_label || 'Situacao operacional') +
                '</div>' +
                '</div>' +
                '<span class="playbook-chip">' + esc(playbook.target_tab_label || playbook.target_tab ||
                    'operacao') + '</span>' +
                '</div>' +
                (playbook.description ? '<div class="workflow-playbook-meta">' + esc(playbook.description) + '</div>' :
                    '') +
                (checklist.length ? '<div class="workflow-playbook-list">' + checklist.map(function(item) {
                    return '<span>' + esc(item) + '</span>';
                }).join('') + '</div>' : '') +
                (workflow.length ? '<div class="workflow-playbook-meta">Fluxo: ' + esc(workflow.join(' -> ')) +
                    '</div>' : '') +
                '</div>';
        }

        function buildArchiveInfoPanel() {
            if (!currentContent) return document.createElement('div');

            var wrap = document.createElement('div');
            wrap.className = 'content-card';
            var versions = Array.isArray(currentContent.version_history) ? currentContent.version_history : [];
            var archiveMemory = currentContent.archive_memory || {};
            var generationSession = currentContent.generation_session || null;
            var generationAudit = Array.isArray(currentContent.generation_audit) ? currentContent.generation_audit : [];

            wrap.innerHTML =
                '<div class="content-card-header">' +
                '<div class="content-card-meta">' +
                '<div class="content-card-title">Arquivo e Memória Institucional</div>' +
                '<div class="content-card-info">Criado por ' + esc(currentContent.creator_name || 'Equipe') + ' · ' +
                esc(currentContent.creator_profile_label || 'Equipe') + '</div>' +
                '</div>' +
                '<span class="content-card-status ' + renderStatusClass(currentContent.status) + '">' +
                esc(currentContent.channel_label || 'Interno') + '</span>' +
                '</div>' +
                '<div style="padding:1rem 1.2rem">' +
                '<div class="operations-meta-row" style="margin-top:0">' +
                '<span class="operations-meta-chip">Tipo: ' + esc(currentContent.type_label || 'Conteúdo') + '</span>' +
                '<span class="operations-meta-chip">Canal: ' + esc(currentContent.channel_label || 'Interno') + '</span>' +
                '<span class="operations-meta-chip">Tom: ' + esc(currentContent.tone_label || 'Neutro') + '</span>' +
                (generationSession ? '<span class="operations-meta-chip">Sessão: ' + esc(generationSession.label ||
                    'Sessão editorial') + '</span>' : '') +
                '<span class="operations-meta-chip">Versões: ' + esc(String(currentContent.version_count || 1)) +
                '</span>' +
                '</div>' +
                (archiveMemory.reference_note ? '<div class="archive-item-note">Nota de referência: ' + esc(archiveMemory
                    .reference_note) + '</div>' : '') +
                (generationAudit.length ? '<div class="archive-version-list">' + generationAudit.map(function(entry) {
                    return '<div class="archive-version-item">' +
                        '<strong>' + esc(entry.label || 'Evento editorial') + '</strong>' +
                        '<p>' + esc((entry.provider ? 'Provider: ' + entry.provider + ' · ' : '') + (entry
                            .executed_at_human || '')) + '</p>' +
                        '</div>';
                }).join('') + '</div>' : '') +
                (versions.length ? '<div class="archive-version-list">' + versions.map(function(version) {
                    return '<div class="archive-version-item">' +
                        '<strong>' + esc(version.label || 'Versão') + ' · ' + esc(version.tone || 'Neutro') +
                        '</strong>' +
                        '<p>' + esc(version.content || '') + '</p>' +
                        '</div>';
                }).join('') + '</div>' : '') +
                '</div>';
            return wrap;
        }

        function buildArchiveMemoryPanel() {
            if (!currentContent) return document.createElement('div');

            var wrap = document.createElement('div');
            wrap.className = 'content-card';
            var archiveMemory = currentContent.archive_memory || {};
            var outcomeLabel = currentContent.type === 'entrevista' ?
                'Como a entrevista correu' :
                (currentContent.type === 'crise' ? 'Desfecho da crise' : 'Resultado ou aprendizado');
            var referenceLabel = currentContent.type === 'crise' ?
                'Referência para crises futuras' :
                (currentContent.type === 'entrevista' ? 'Pontos para próxima preparação' : 'Nota de referência');

            wrap.innerHTML =
                '<div class="content-card-header">' +
                '<div class="content-card-meta">' +
                '<div class="content-card-title">Memória Aplicada</div>' +
                '<div class="content-card-info">Registre o que funcionou para fortalecer o Arquivo como memória de crise e media training.</div>' +
                '</div>' +
                '</div>' +
                '<div style="padding:1rem 1.2rem">' +
                '<div class="field">' +
                '<label>' + esc(referenceLabel) + '</label>' +
                '<textarea id="archive-reference-note" rows="3" placeholder="Ex: linha institucional funcionou bem, reforçar dado concreto, evitar entrar na agenda da oposição.">' +
                esc(archiveMemory.reference_note || '') + '</textarea>' +
                '</div>' +
                '<div class="field" style="margin-bottom:0">' +
                '<label>' + esc(outcomeLabel) + '</label>' +
                '<textarea id="archive-outcome-note" rows="3" placeholder="Ex: houve insistência em obras, a entrevista foi segura, a resposta reduziu a tensão local.">' +
                esc(archiveMemory.outcome_note || '') + '</textarea>' +
                '</div>' +
                '<div class="content-card-actions" style="padding:0;margin-top:.9rem">' +
                '<button class="action-btn primary" type="button" onclick="saveArchiveMemory()">Salvar memória</button>' +
                (currentContent.id ? '<a class="action-btn" href="/mayor/content?area=produce&reuse=' + currentContent.id +
                    '">Reusar como base</a>' : '') +
                (currentContent.id ?
                    '<button class="action-btn" type="button" onclick="removeCurrentContentFromArchive()">Remover do arquivo</button>' :
                    '') +
                '</div>' +
                '</div>';
            return wrap;
        }

        async function removeCurrentContentFromArchive() {
            if (!currentContent || !currentContent.id) return;
            if (!window.confirm('Remover este item do Arquivo? A trilha auditável será preservada.')) return;

            try {
                var data = await (await apiFetch('/mayor/content/' + currentContent.id + '/archive-remove', {})).json();
                if (!data.success) throw new Error(data.error || 'Erro ao remover item do arquivo');

                showToast('✓ Item removido do Arquivo com trilha auditável preservada!');
                window.location.href = '{{ route('mayor.content.index', ['area' => 'archive']) }}';
            } catch (e) {
                showError('Erro ao remover do arquivo: ' + e.message);
            }
        }

        function buildEditorialActions() {
            const wrap = document.createElement('div');
            wrap.className = 'content-card';
            wrap.style.marginTop = '0.2rem';
            const createdAtHuman = currentContent && currentContent.created_at_human ? currentContent.created_at_human :
                'Agora';
            const originLabel = currentContent && currentContent.origin_module ? ' · origem ' + currentContent
                .origin_module : '';
            const plannedLabel = currentContent && currentContent.planned_at_human ? ' · agendado ' + currentContent
                .planned_at_human : '';
            const publishedLink = currentContent && currentContent.published_url ?
                '<a class="action-btn" href="' + esc(currentContent.published_url) +
                '" target="_blank" rel="noopener noreferrer">Abrir URL</a>' : '';
            const currentStatus = currentContent && currentContent.status ? currentContent.status : 'draft';
            const slaPanel = buildCurrentSlaPanel();
            const playbookPanel = buildCurrentPlaybookPanel();
            const approvalWorkflow = currentContent && currentContent.approval_workflow ? currentContent.approval_workflow :
                null;
            const approvalPanel = approvalWorkflow ?
                '<div style="margin:.95rem 1.2rem 0;padding:.85rem 1rem;border-radius:14px;border:1px solid rgba(15,23,42,.08);background:#f8fafc">' +
                '<div style="font-weight:700;color:var(--ink)">Aprovação configurada</div>' +
                '<div style="margin-top:.3rem;color:var(--muted);font-size:.92rem">Esta ' + esc(approvalWorkflow
                    .type_label || 'peça') +
                ' exige aprovação final do perfil <strong style="color:var(--ink)">' + esc(approvalWorkflow
                    .required_role_label || 'Prefeito') + '</strong> antes da publicação.</div>' +
                '</div>' : '';
            wrap.innerHTML =
                '<div class="content-card-header">' +
                '<div class="content-card-meta">' +
                '<div class="content-card-title">Workflow editorial</div>' +
                '<div class="content-card-info">' + createdAtHuman + originLabel + plannedLabel + '</div>' +
                '</div>' +
                '<span class="content-card-status ' + renderStatusClass(currentStatus) + '">' + renderStatusText(
                    currentStatus) + '</span>' +
                '</div>' +
                playbookPanel +
                slaPanel +
                approvalPanel +
                '<div class="content-card-actions">' +
                '<button class="action-btn" onclick="approveCurrentContent()">Aprovar</button>' +
                '<button class="action-btn" onclick="scheduleCurrentContent()">Agendar</button>' +
                '<button class="action-btn primary" onclick="publishCurrentContent()">Publicar</button>' +
                '<button class="action-btn" onclick="archiveCurrentContent()">Arquivar</button>' +
                publishedLink +
                '</div>';
            return wrap;
        }

        function collaborationStatusClass(action) {
            return {
                approved: 'good',
                changes_requested: 'medium',
                observation: 'low'
            } [action || 'observation'] || 'low';
        }

        function renderStatusClass(status) {
            return 'status-' + (status || 'draft');
        }

        function renderStatusText(status) {
            return {
                draft: 'Rascunho',
                approved: 'Aprovado',
                published: 'Publicado',
                archived: 'Arquivado'
            } [status || 'draft'] || 'Rascunho';
        }

        function esc(t) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(t || ''));
            return d.innerHTML;
        }

        function ucFirst(s) {
            return s ? s.charAt(0).toUpperCase() + s.slice(1) : s;
        }

        function formatIsoDate(value) {
            if (!value) return '';
            var date = new Date(value);
            if (isNaN(date.getTime())) return value;
            return date.toLocaleString('pt-BR');
        }

        if (document.getElementById('post-template-select') || document.getElementById('image-template-select')) {
            renderTemplateSelectOptions();
        }
        if (document.getElementById('template-library-list')) {
            renderTemplateLibrary();
        }
        if (document.getElementById('post-template-select')) {
            applyTemplateSelection('post');
        }
        if (document.getElementById('image-template-select')) {
            applyTemplateSelection('image');
        }
        if (document.getElementById('post-playbook-select')) {
            applyPlaybookSelection('post');
        }
        if (document.getElementById('interview-playbook-select')) {
            applyPlaybookSelection('interview');
        }
        if (document.getElementById('crisis-playbook-select')) {
            applyPlaybookSelection('crisis');
            applyInitialMentionSeed();
        }
        if (initialReuseSeed) {
            applyInitialReuseSeed();
        }
        applyPraHojeHighlights();
        if (document.querySelector('.tab-btn[data-tab]')) {
            switchTab(initialTab);
        }
        if (initialContentId && document.getElementById('resultsPanel')) {
            loadContent(initialContentId);
        }
        initOperationsDragAndDrop();
    </script>
@endpush
