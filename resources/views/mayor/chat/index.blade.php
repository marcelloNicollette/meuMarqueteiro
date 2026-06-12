@extends('layouts.mayor')

@section('title', 'Meu Assistente')
@section('topbar-title', 'Meu Assistente')

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

        .conv-filters {
            padding: .85rem .9rem .6rem;
            border-bottom: 1px solid var(--border-lt);
            display: flex;
            flex-direction: column;
            gap: .6rem;
        }

        .conv-share-summary {
            display: flex;
            flex-direction: column;
            gap: .18rem;
            padding: .72rem .78rem;
            border-radius: 12px;
            background: rgba(5, 150, 105, .08);
            border: 1px solid rgba(5, 150, 105, .14);
        }

        .conv-share-summary-label {
            font-size: .66rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #047857;
        }

        .conv-share-summary-value {
            font-size: .82rem;
            font-weight: 700;
            color: var(--ink);
        }

        .conv-share-summary-help {
            font-size: .72rem;
            color: var(--ink-muted);
        }

        .conv-search {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--surface);
            padding: .65rem .8rem;
            font-size: .82rem;
            color: var(--ink);
            outline: none;
        }

        .conv-search:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(184, 144, 42, .08);
            background: var(--white);
        }

        .conv-filter-tags {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
        }

        .conv-filter-extra {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
        }

        .conv-period-select {
            min-width: 140px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--white);
            color: var(--ink-muted);
            font-size: .72rem;
            padding: .33rem .8rem;
            outline: none;
        }

        .conv-period-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(184, 144, 42, .08);
        }

        .conv-filter-chip {
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--ink-muted);
            font-size: .68rem;
            border-radius: 999px;
            padding: .28rem .55rem;
            cursor: pointer;
            transition: all .15s;
        }

        .conv-filter-chip:hover {
            border-color: var(--gold);
            color: var(--gold);
        }

        .conv-filter-chip.active {
            background: var(--gold-bg);
            color: var(--gold);
            border-color: rgba(184, 144, 42, .22);
        }

        .conv-filter-empty {
            display: none;
            padding: .85rem;
            font-size: .78rem;
            color: var(--ink-muted);
        }

        .conv-filter-empty.is-visible,
        .conv-item.is-hidden {
            display: block;
        }

        .conv-item.is-hidden {
            display: none;
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

        .conv-item-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .5rem;
            margin-bottom: .35rem;
        }

        .conv-item-origin,
        .chat-context-origin,
        .chat-context-intent {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .18rem .5rem;
            border-radius: 999px;
            font-size: .64rem;
            font-weight: 600;
            letter-spacing: .02em;
            background: var(--gold-bg);
            color: var(--gold);
            border: 1px solid rgba(184, 144, 42, .22);
            white-space: nowrap;
        }

        .conv-item-meta {
            font-size: .72rem;
            color: var(--ink-muted);
            display: flex;
            justify-content: space-between;
        }

        .conv-item-tags,
        .chat-context-tags {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            margin-bottom: .45rem;
        }

        .conv-tag,
        .chat-context-tag {
            display: inline-flex;
            align-items: center;
            padding: .16rem .45rem;
            border-radius: 999px;
            font-size: .66rem;
            color: var(--ink-muted);
            background: var(--surface);
            border: 1px solid var(--border);
            line-height: 1.2;
        }

        .conv-item-share-indicator {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            margin-bottom: .45rem;
            padding: .2rem .5rem;
            border-radius: 999px;
            font-size: .66rem;
            font-weight: 700;
            color: #047857;
            background: rgba(5, 150, 105, .10);
            border: 1px solid rgba(5, 150, 105, .15);
        }

        /* ── Área principal do chat ───────────────────────────── */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: var(--surface);
        }

        .chat-workspace {
            flex: 1;
            min-height: 0;
            display: flex;
            overflow: hidden;
        }

        .chat-primary {
            flex: 1;
            min-width: 0;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-alerts-sidebar {
            width: 240px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: .75rem;
            padding: 1rem 1rem 1rem 0;
            overflow-y: auto;
        }

        .chat-alerts-sidebar.is-hidden {
            display: none;
        }

        .chat-alerts-sidebar-header {
            background: rgba(15, 23, 42, .03);
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            padding: .9rem 1rem;
        }

        .chat-alerts-sidebar-title {
            font-size: .74rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--ink-muted);
            margin-bottom: .22rem;
        }

        .chat-alerts-sidebar-help {
            font-size: .76rem;
            line-height: 1.5;
            color: var(--ink-soft);
        }

        .chat-context-bar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: .5rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .chat-context-bar.is-hidden {
            display: none;
        }

        .chat-context-label {
            font-size: .68rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--ink-muted);
            margin-bottom: .2rem;
        }

        .chat-context-title {
            font-size: .96rem;
            font-weight: 600;
            color: var(--ink);
        }

        .chat-context-side {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: .45rem;
            min-width: min(420px, 100%);
        }

        .chat-context-badges {
            display: none;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: .4rem;
        }

        .chat-context-tag-tools {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: .45rem;
            width: 100%;
        }

        .chat-context-tag-meta {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .45rem;
            flex-wrap: wrap;
        }

        .chat-context-tag-note {
            font-size: .68rem;
            color: var(--ink-muted);
        }

        .chat-context-tag-btn {
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--ink-muted);
            font-size: .72rem;
            font-weight: 600;
            border-radius: 999px;
            padding: .35rem .7rem;
            cursor: pointer;
            transition: all .15s ease;
        }

        .chat-context-tag-btn:hover {
            border-color: var(--gold);
            color: var(--gold);
        }

        .chat-tag-editor {
            width: min(360px, 100%);
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--white);
            padding: .75rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
        }

        .chat-tag-editor.is-hidden {
            display: none;
        }

        .chat-tag-editor-input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--surface);
            color: var(--ink);
            padding: .65rem .8rem;
            font-size: .8rem;
            outline: none;
            box-sizing: border-box;
        }

        .chat-tag-editor-input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(184, 144, 42, .08);
            background: var(--white);
        }

        .chat-tag-editor-help {
            margin-top: .5rem;
            font-size: .7rem;
            line-height: 1.4;
            color: var(--ink-muted);
        }

        .chat-tag-editor-actions {
            display: flex;
            justify-content: flex-end;
            gap: .5rem;
            margin-top: .75rem;
        }

        .chat-tag-editor-action {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--white);
            color: var(--ink-muted);
            font-size: .74rem;
            font-weight: 600;
            padding: .45rem .75rem;
            cursor: pointer;
        }

        .chat-tag-editor-action.primary {
            background: var(--gold);
            border-color: var(--gold);
            color: #fff;
        }

        .chat-tag-editor-action:disabled {
            opacity: .6;
            cursor: wait;
        }

        .chat-alerts-bar {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        .chat-alert-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: .85rem .9rem;
            min-width: 0;
            box-shadow: 0 2px 10px rgba(15, 23, 42, .03);
        }

        .chat-alert-card[data-severity="high"] {
            border-color: rgba(185, 28, 28, .18);
            background: linear-gradient(180deg, rgba(254, 242, 242, .95), rgba(255, 255, 255, 1));
        }

        .chat-alert-card[data-severity="medium"] {
            border-color: rgba(180, 83, 9, .18);
            background: linear-gradient(180deg, rgba(255, 247, 237, .95), rgba(255, 255, 255, 1));
        }

        .chat-alert-card[data-severity="low"] {
            border-color: rgba(37, 99, 235, .14);
            background: linear-gradient(180deg, rgba(239, 246, 255, .95), rgba(255, 255, 255, 1));
        }

        .chat-alert-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .6rem;
            margin-bottom: .45rem;
        }

        .chat-alert-top-right {
            display: flex;
            align-items: center;
            gap: .45rem;
        }

        .chat-alert-kicker {
            font-size: .67rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--ink-muted);
        }

        .chat-alert-level {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: .18rem .48rem;
            font-size: .63rem;
            font-weight: 700;
            letter-spacing: .02em;
            background: var(--surface);
            color: var(--ink-muted);
        }

        .chat-alert-card[data-severity="high"] .chat-alert-level {
            background: #fee2e2;
            color: #b91c1c;
        }

        .chat-alert-card[data-severity="medium"] .chat-alert-level {
            background: #ffedd5;
            color: #b45309;
        }

        .chat-alert-card[data-severity="low"] .chat-alert-level {
            background: #dbeafe;
            color: #2563eb;
        }

        .chat-alert-title {
            font-size: .84rem;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: .35rem;
        }

        .chat-alert-relevance {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            margin-bottom: .45rem;
            border-radius: 999px;
            padding: .22rem .55rem;
            background: rgba(5, 150, 105, .1);
            color: #047857;
            font-size: .68rem;
            font-weight: 700;
        }

        .chat-alert-summary {
            font-size: .76rem;
            line-height: 1.5;
            color: var(--ink-soft);
            margin: 0 0 .65rem;
        }

        .chat-alert-footer {
            display: flex;
            align-items: flex-start;
            flex-direction: column;
            gap: .55rem;
            flex-wrap: wrap;
        }

        .chat-alert-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            border: none;
            background: var(--ink);
            color: var(--white);
            border-radius: 10px;
            padding: .48rem .72rem;
            font-size: .73rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .chat-alert-action:hover {
            opacity: .95;
        }

        .chat-alert-dismiss {
            border: none;
            background: transparent;
            color: var(--ink-muted);
            cursor: pointer;
            font-size: .72rem;
            font-weight: 600;
            padding: .3rem 0;
        }

        .chat-alert-close {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            border: none;
            background: transparent;
            color: var(--ink-muted);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .chat-alert-close:hover,
        .chat-alert-dismiss:hover {
            color: var(--ink);
        }

        .chat-content-tabs {
            padding: .85rem 1.25rem 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .chat-tab-buttons,
        .share-status-filters {
            display: flex;
            align-items: center;
            gap: .45rem;
            flex-wrap: wrap;
        }

        .chat-panel-tab,
        .share-status-filter {
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--ink-muted);
            border-radius: 999px;
            padding: .42rem .78rem;
            font-size: .72rem;
            font-weight: 700;
            cursor: pointer;
            transition: all .15s;
        }

        .chat-panel-tab:hover,
        .share-status-filter:hover {
            border-color: var(--gold);
            color: var(--ink);
        }

        .chat-panel-tab.is-active,
        .share-status-filter.is-active {
            background: var(--gold-bg);
            border-color: rgba(184, 144, 42, .22);
            color: var(--gold);
        }

        .chat-panel-tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.4rem;
            padding: 0 .38rem;
            margin-left: .35rem;
            border-radius: 999px;
            background: rgba(15, 23, 42, .07);
            color: inherit;
            line-height: 1.5;
        }

        .share-status-filters.is-hidden {
            display: none;
        }

        .global-share-filters {
            display: flex;
            align-items: center;
            gap: .55rem;
            flex-wrap: wrap;
        }

        .global-share-filters.is-hidden {
            display: none;
        }

        .global-share-select {
            min-width: 220px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--white);
            color: var(--ink);
            padding: .48rem .85rem;
            font-size: .74rem;
        }

        .chat-panel {
            flex: 1;
            min-height: 0;
            display: none;
        }

        .chat-panel.is-active {
            display: flex;
            flex-direction: column;
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
            font-family: "Outfit", sans-serif;
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

        .chat-empty-kicker {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            font-size: .68rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--gold);
            background: var(--gold-bg);
            border: 1px solid rgba(184, 144, 42, .16);
            border-radius: 999px;
            padding: .3rem .7rem;
            margin-bottom: .9rem;
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

        .message-signals {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin-top: .65rem;
        }

        .message-signal {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .5rem;
            border-radius: 999px;
            background: rgba(15, 23, 42, .05);
            color: var(--ink-muted);
            font-size: .68rem;
            font-weight: 600;
        }

        .message-signal.memory {
            background: rgba(5, 150, 105, .10);
            color: #047857;
        }

        .export-suggestion {
            margin-top: .8rem;
            padding: .8rem .9rem;
            border: 1px solid rgba(184, 144, 42, .18);
            border-radius: 12px;
            background: linear-gradient(180deg, rgba(184, 144, 42, .06), rgba(184, 144, 42, .02));
        }

        .export-suggestion-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .35rem;
        }

        .export-suggestion-title {
            font-size: .76rem;
            font-weight: 700;
            letter-spacing: .03em;
            color: var(--ink);
            text-transform: uppercase;
        }

        .export-suggestion-confidence {
            font-size: .65rem;
            font-weight: 600;
            border-radius: 999px;
            padding: .16rem .45rem;
            color: var(--gold);
            background: var(--gold-bg);
            border: 1px solid rgba(184, 144, 42, .2);
        }

        .export-suggestion p {
            margin: 0 0 .7rem;
            font-size: .8rem;
            color: var(--ink-soft);
            line-height: 1.55;
        }

        .export-suggestion-actions {
            display: flex;
            align-items: center;
            gap: .55rem;
            flex-wrap: wrap;
        }

        .export-btn {
            border: none;
            background: var(--ink);
            color: var(--white);
            border-radius: 10px;
            padding: .5rem .85rem;
            font-size: .76rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .15s, transform .15s;
        }

        .export-btn:hover {
            opacity: .94;
            transform: translateY(-1px);
        }

        .export-btn:disabled {
            opacity: .55;
            cursor: wait;
            transform: none;
        }

        .export-link {
            font-size: .76rem;
            color: var(--gold);
            text-decoration: none;
            font-weight: 600;
        }

        .export-link:hover {
            text-decoration: underline;
        }

        .export-status {
            font-size: .74rem;
            color: var(--ink-muted);
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

        .message-actions {
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .message-share-meta {
            margin-top: .45rem;
            font-size: .72rem;
            color: #047857;
            font-weight: 600;
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

        .share-btn {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--ink-muted);
            border-radius: 999px;
            padding: .28rem .58rem;
            font-size: .68rem;
            cursor: pointer;
            transition: all .15s;
        }

        .share-btn:hover {
            border-color: var(--gold);
            color: var(--ink);
            background: var(--gold-bg);
        }

        .share-btn.is-shared {
            border-color: rgba(5, 150, 105, .18);
            color: #047857;
            background: rgba(5, 150, 105, .08);
        }

        .audio-play-btn {
            border: 1px solid var(--border);
            background: rgba(15, 23, 42, .04);
            color: var(--ink-muted);
            border-radius: 999px;
            padding: .35rem .7rem;
            font-size: .72rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s ease;
        }

        .audio-play-btn:hover {
            border-color: var(--ink);
            color: var(--ink);
        }

        .audio-play-btn.is-playing {
            border-color: rgba(14, 116, 144, .24);
            color: #075985;
            background: rgba(14, 116, 144, .08);
        }

        .audio-play-btn.is-read {
            border-color: rgba(5, 150, 105, .18);
            color: #047857;
            background: rgba(5, 150, 105, .07);
        }

        .share-btn[disabled] {
            opacity: .45;
            cursor: not-allowed;
        }

        .share-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .42);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            z-index: 60;
            overflow-y: auto;
        }

        .share-modal.is-open {
            display: flex;
        }

        .share-modal-card {
            width: min(640px, 90vw);
            max-height: 70vh;
            background: var(--white);
            border-radius: 18px;
            border: 1px solid var(--border);
            box-shadow: 0 24px 80px rgba(15, 23, 42, .18);
            padding: 1.1rem 1.15rem;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            margin: auto;
        }

        .share-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .share-modal-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--ink);
        }

        .share-modal-subtitle {
            color: var(--ink-muted);
            font-size: .8rem;
            margin-top: .2rem;
        }

        .share-modal-close {
            border: none;
            background: transparent;
            color: var(--ink-muted);
            cursor: pointer;
            width: 30px;
            height: 30px;
            border-radius: 999px;
        }

        .share-modal-close:hover {
            background: var(--surface);
            color: var(--ink);
        }

        .share-modal-form {
            display: grid;
            gap: .9rem;
            overflow-y: auto;
            padding-right: .15rem;
        }

        .share-modal-field label {
            display: block;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--ink-muted);
            margin-bottom: .38rem;
        }

        .share-modal-field select,
        .share-modal-field textarea,
        .share-modal-field input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--white);
            color: var(--ink);
            padding: .78rem .85rem;
            font: inherit;
        }

        .share-modal-field textarea {
            min-height: 110px;
            resize: vertical;
        }

        .share-modal-help {
            font-size: .74rem;
            color: var(--ink-muted);
            margin-top: .3rem;
        }

        .share-modal-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .share-modal-status {
            font-size: .78rem;
            color: var(--ink-muted);
        }

        .share-modal-submit {
            border: none;
            border-radius: 10px;
            background: var(--ink);
            color: var(--white);
            padding: .7rem 1rem;
            font-size: .78rem;
            font-weight: 600;
            cursor: pointer;
        }

        .share-history-list {
            display: grid;
            gap: .65rem;
        }

        .share-history-item {
            border: 1px solid var(--border-lt);
            border-radius: 12px;
            background: var(--surface);
            padding: .8rem .9rem;
        }

        .share-history-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .35rem;
        }

        .share-history-recipient {
            font-size: .82rem;
            font-weight: 700;
            color: var(--ink);
        }

        .share-history-meta {
            font-size: .73rem;
            color: var(--ink-muted);
            line-height: 1.5;
        }

        .share-history-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: .18rem .5rem;
            font-size: .66rem;
            font-weight: 700;
            background: rgba(5, 150, 105, .10);
            color: #047857;
            white-space: nowrap;
        }

        .share-history-status.revoked {
            background: rgba(185, 28, 28, .10);
            color: #b91c1c;
        }

        .share-history-actions {
            display: flex;
            align-items: center;
            gap: .55rem;
            flex-wrap: wrap;
            margin-top: .55rem;
        }

        .share-history-link,
        .share-history-revoke {
            border: none;
            background: transparent;
            color: var(--gold);
            font-size: .74rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            padding: 0;
        }

        .share-history-revoke {
            color: #b91c1c;
        }

        .share-history-empty {
            border: 1px dashed var(--border);
            border-radius: 12px;
            padding: .85rem .9rem;
            color: var(--ink-muted);
            font-size: .78rem;
            background: var(--surface);
        }

        .conversation-shares {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 2rem 1.5rem;
        }

        .share-feed-list {
            display: grid;
            gap: .85rem;
        }

        .share-feed-empty {
            border: 1px dashed var(--border);
            border-radius: 14px;
            padding: 1rem 1.1rem;
            color: var(--ink-muted);
            font-size: .82rem;
            background: rgba(255, 255, 255, .75);
        }

        .share-feed-item {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1rem 1.05rem;
            box-shadow: 0 4px 16px rgba(15, 23, 42, .04);
        }

        .share-feed-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .55rem;
        }

        .share-feed-title {
            font-size: .86rem;
            font-weight: 700;
            color: var(--ink);
        }

        .share-feed-meta {
            font-size: .74rem;
            color: var(--ink-muted);
            line-height: 1.6;
        }

        .share-feed-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: .18rem .52rem;
            font-size: .66rem;
            font-weight: 700;
            background: rgba(5, 150, 105, .10);
            color: #047857;
            white-space: nowrap;
        }

        .share-feed-status.revoked {
            background: rgba(185, 28, 28, .10);
            color: #b91c1c;
        }

        .share-feed-excerpt,
        .share-feed-context,
        .share-feed-note {
            margin-top: .65rem;
            padding: .78rem .88rem;
            border-radius: 12px;
            font-size: .78rem;
            line-height: 1.6;
        }

        .share-feed-excerpt {
            background: rgba(184, 144, 42, .08);
            border: 1px solid rgba(184, 144, 42, .16);
            color: var(--ink);
        }

        .share-feed-context {
            background: var(--surface);
            border: 1px solid var(--border-lt);
            color: var(--ink-soft);
            white-space: pre-line;
        }

        .share-feed-note {
            background: rgba(37, 99, 235, .06);
            border: 1px solid rgba(37, 99, 235, .12);
            color: #1d4ed8;
        }

        .share-feed-label {
            display: block;
            font-size: .66rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--ink-muted);
            margin-bottom: .3rem;
        }

        .share-feed-actions {
            display: flex;
            align-items: center;
            gap: .7rem;
            flex-wrap: wrap;
            margin-top: .8rem;
        }

        .share-feed-link,
        .share-feed-revoke {
            border: none;
            background: transparent;
            padding: 0;
            font-size: .75rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }

        .share-feed-link {
            color: var(--gold);
        }

        .share-feed-revoke {
            color: #b91c1c;
        }

        .share-feed-conversation {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            margin-bottom: .3rem;
            font-size: .67rem;
            font-weight: 700;
            letter-spacing: .03em;
            color: var(--gold);
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
            font-family: "Inter", sans-serif;
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

        .btn-voice.is-listening,
        .btn-voice.is-active {
            border-color: #0f766e;
            color: #0f766e;
            background: rgba(15, 118, 110, .08);
        }

        .btn-voice:disabled {
            opacity: .55;
            cursor: not-allowed;
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

        .audio-status {
            display: none;
            margin-top: .28rem;
            color: var(--ink-muted);
        }

        .audio-preferences {
            display: none;
            justify-content: center;
            gap: .45rem;
            flex-wrap: wrap;
            margin-top: .55rem;
        }

        .audio-speed-options {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .audio-speed-btn {
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--ink-muted);
            border-radius: 999px;
            padding: .34rem .72rem;
            font-size: .72rem;
            cursor: pointer;
            transition: all .15s ease;
        }

        .audio-speed-btn.is-active {
            border-color: rgba(14, 116, 144, .24);
            color: #075985;
            background: rgba(14, 116, 144, .08);
            font-weight: 700;
        }

        .audio-speed-btn:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .chat-audio-bar {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: .85rem;
            padding: .7rem 1rem;
            border-bottom: 1px solid var(--border-lt);
            background: rgba(14, 116, 144, .04);
        }

        .chat-audio-bar.is-hidden {
            display: none;
        }

        .chat-audio-bar-main {
            display: flex;
            flex-direction: column;
            gap: .18rem;
            min-width: 0;
        }

        .chat-audio-bar-label {
            font-size: .66rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #075985;
        }

        .chat-audio-bar-status {
            font-size: .8rem;
            color: var(--ink);
            font-weight: 600;
        }

        .chat-audio-bar-help {
            font-size: .72rem;
            color: var(--ink-muted);
        }

        .chat-audio-bar-side {
            display: flex;
            align-items: center;
            gap: .45rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .chat-audio-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: .22rem .55rem;
            font-size: .68rem;
            font-weight: 700;
            color: #075985;
            background: rgba(14, 116, 144, .08);
            border: 1px solid rgba(14, 116, 144, .15);
        }

        .audio-pref-chip {
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--ink-muted);
            border-radius: 999px;
            padding: .34rem .75rem;
            font-size: .72rem;
            cursor: pointer;
            transition: all .15s ease;
        }

        .audio-pref-chip.is-active {
            border-color: rgba(15, 118, 110, .26);
            color: #0f766e;
            background: rgba(15, 118, 110, .09);
            font-weight: 700;
        }

        .audio-pref-chip:disabled {
            opacity: .55;
            cursor: not-allowed;
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

        .message-signal.voice {
            color: #075985;
            background: rgba(14, 116, 144, .10);
            border: 1px solid rgba(14, 116, 144, .16);
        }

        .message-signal.audio-status {
            color: #075985;
            background: rgba(14, 116, 144, .10);
            border: 1px solid rgba(14, 116, 144, .16);
        }

        .message-signal.audio-status.is-read {
            color: #047857;
            background: rgba(5, 150, 105, .10);
            border-color: rgba(5, 150, 105, .16);
        }

        .message-bubble h3,
        .message-bubble h4 {
            font-family: "Outfit", sans-serif;
            font-weight: 500;
            margin: .6rem 0 .3rem;
        }

        @media (max-width: 1180px) {
            .conv-list {
                display: none;
            }

            .chat-context-bar {
                align-items: flex-start;
                flex-direction: column;
            }

            .chat-context-side,
            .chat-context-badges,
            .chat-context-tag-tools,
            .chat-context-tag-meta {
                width: 100%;
                align-items: flex-start;
                justify-content: flex-start;
            }

            .chat-tag-editor {
                width: 100%;
            }

            .chat-workspace {
                flex-direction: column;
            }

            .chat-alerts-sidebar {
                width: auto;
                padding: .75rem 1rem 0;
                overflow: visible;
            }

            .chat-content-tabs,
            .messages-area {
                padding: 1rem;
            }

            .conversation-shares {
                padding: .85rem 1rem 1rem;
            }

            .global-share-select {
                min-width: 0;
                width: 100%;
            }

            .input-area {
                padding: .75rem 1rem 1rem;
            }

            .share-modal {
                padding: .6rem;
                align-items: stretch;
            }

            .share-modal-card {
                width: 90vw;
                max-height: 90vh;
                border-radius: 14px;
                padding: .95rem;
            }

            .share-feed-top {
                flex-direction: column;
            }
        }
    </style>
@endpush

@section('content')

    @php
        $conversationShareHistoryCount = collect($shareStates ?? [])->sum(fn($state) => count($state['shares'] ?? []));
        $globalShareHistoryCount = count($globalShareFeed ?? []);
        $conversationsWithActiveSharesCount = collect($conversations ?? [])
            ->filter(fn($conversation) => ($conversation->active_shares_count ?? 0) > 0)
            ->count();
        $messageIndex = ($messages ?? collect())->mapWithKeys(function ($msg) {
            return [
                $msg->id => [
                    'id' => $msg->id,
                    'role' => $msg->role,
                    'content' => \Illuminate\Support\Str::limit(trim($msg->content), 240),
                    'created_at' => $msg->created_at?->format('d/m/Y H:i'),
                ],
            ];
        });
    @endphp

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
            <div class="conv-filters">
                <div class="conv-share-summary" id="convShareSummary">
                    <div class="conv-share-summary-label">Compartilhamentos ativos</div>
                    <div class="conv-share-summary-value" id="convShareSummaryValue">
                        {{ $conversationsWithActiveSharesCount }} conversas com compartilhamentos ativos
                    </div>
                    <div class="conv-share-summary-help" id="convShareSummaryHelp">
                        Use o filtro para focar apenas nas conversas com trechos ainda ativos.
                    </div>
                </div>
                <input type="search" class="conv-search" id="convSearchInput"
                    placeholder="Buscar conversa, assunto ou tag...">
                <div class="conv-filter-extra">
                    <button type="button" class="conv-filter-chip" id="convShareOnlyChip" data-share-filter="active-only">
                        Com compartilhamentos
                    </button>
                    <select id="convPeriodFilter" class="conv-period-select" aria-label="Filtrar conversas por periodo">
                        <option value="all">Todo periodo</option>
                        <option value="today">Hoje</option>
                        <option value="7d">Ultimos 7 dias</option>
                        <option value="30d">Ultimos 30 dias</option>
                        <option value="90d">Ultimos 90 dias</option>
                    </select>
                </div>
                <div class="conv-filter-tags" id="convFilterTags">
                    <button type="button" class="conv-filter-chip active" data-tag="all">Todas</button>
                </div>
            </div>
            <div class="conv-scroll">
                @php
                    $originLabels = [
                        'chat' => 'Chat',
                        'federal_programs' => 'Radar Federal',
                        'resolve_ai' => 'Resolve ai',
                        'content' => 'Conteudo',
                        'briefings' => 'Briefing',
                    ];
                    $intentLabels = [
                        'planejamento' => 'Planejamento',
                        'comunicação' => 'Comunicação',
                        'captação' => 'Captacao',
                        'gestao_de_crise' => 'Crise',
                        'demanda_operacional' => 'Demanda',
                        'orientacao_geral' => 'Orientacao',
                    ];
                @endphp
                @forelse($conversations as $conv)
                    @php
                        $visibleTags = $conv->display_tags ?? ($conv->auto_tags ?? []);
                    @endphp
                    <a href="{{ route('chat.show', $conv) }}"
                        class="conv-item {{ isset($activeConversation) && $activeConversation->id === $conv->id ? 'active' : '' }}"
                        data-id="{{ $conv->id }}" data-message-count="{{ $conv->messages_count ?? 0 }}"
                        data-active-shares="{{ $conv->active_shares_count ?? 0 }}"
                        data-tags="{{ implode(',', $visibleTags) }}" data-origin="{{ $conv->origin_module ?? '' }}"
                        data-last-message-at="{{ optional($conv->last_message_at)->toIso8601String() }}"
                        data-has-manual-tags="{{ !empty($conv->has_manual_tags) ? '1' : '0' }}"
                        data-search="{{ strtolower(trim(($conv->title ?: 'Nova conversa') . ' ' . implode(' ', $visibleTags) . ' ' . ($originLabels[$conv->origin_module] ?? ($conv->origin_module ?? '')))) }}">
                        <div class="conv-item-top">
                            <div class="conv-item-title">{{ $conv->title ?: 'Nova conversa' }}</div>
                            @if ($conv->origin_module)
                                <span class="conv-item-origin">
                                    {{ $originLabels[$conv->origin_module] ?? ucfirst(str_replace('_', ' ', $conv->origin_module)) }}
                                </span>
                            @endif
                        </div>
                        @if (!empty($visibleTags))
                            <div class="conv-item-tags">
                                @foreach (array_slice($visibleTags, 0, 3) as $tag)
                                    <span class="conv-tag">{{ ucfirst(str_replace('_', ' ', $tag)) }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if (($conv->active_shares_count ?? 0) > 0)
                            <div class="conv-item-share-indicator">
                                Compartilhamentos ativos: {{ $conv->active_shares_count }}
                            </div>
                        @endif
                        <div class="conv-item-meta">
                            <span>{{ $conv->messages_count ?? 0 }} msgs</span>
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
                    <p id="convListEmpty" style="padding:1rem .85rem;font-size:.82rem;color:var(--ink-muted)">
                        Nenhuma conversa ainda.
                    </p>
                @endforelse
                <p class="conv-filter-empty" id="convFilterEmpty">
                    Nenhuma conversa encontrada com esse filtro.
                </p>
            </div>
        </div>

        {{-- ── Chat principal ───────────────────────────────────── --}}
        <div class="chat-main">
            <div class="chat-context-bar {{ isset($activeConversation) ? '' : 'is-hidden' }}" id="chatContextBar">
                <div>
                    <div class="chat-context-label">Conversa ativa</div>
                    <div class="chat-context-title" id="chatContextTitle">
                        {{ $activeConversation?->title ?: 'Nova conversa' }}
                    </div>
                </div>
                <div class="chat-context-side">
                    <div class="chat-context-badges" id="chatContextBadges">
                        @if (isset($activeConversation) && $activeConversation->origin_module)
                            <span class="chat-context-origin">
                                {{ $originLabels[$activeConversation->origin_module] ?? ucfirst(str_replace('_', ' ', $activeConversation->origin_module)) }}
                            </span>
                        @endif
                        @if (($activeConversation->context['intent'] ?? null) && isset($intentLabels[$activeConversation->context['intent']]))
                            <span
                                class="chat-context-intent">{{ $intentLabels[$activeConversation->context['intent']] }}</span>
                        @endif
                    </div>
                    <div class="chat-context-tag-tools">
                        <div class="chat-context-tags" id="chatContextTags">
                            @foreach (array_slice($activeConversationTags ?? [], 0, 4) as $tag)
                                <span class="chat-context-tag">{{ ucfirst(str_replace('_', ' ', $tag)) }}</span>
                            @endforeach
                        </div>
                        <div class="chat-context-tag-meta">
                            <span class="chat-context-tag-note" id="chatContextTagNote">
                                {{ !empty($activeConversationHasManualTags) ? 'Tags manuais ativas' : 'Tags automaticas da conversa' }}
                            </span>
                            <button type="button" class="chat-context-tag-btn" id="chatEditTagsButton">
                                Editar tags
                            </button>
                        </div>
                        <div class="chat-tag-editor is-hidden" id="chatTagEditor">
                            <input type="text" class="chat-tag-editor-input" id="chatTagEditorInput"
                                placeholder="Ex.: saude, entrevista, planejamento"
                                value="{{ implode(', ', $activeConversationTags ?? []) }}">
                            <div class="chat-tag-editor-help">
                                Separe por virgula. Se limpar tudo, a conversa volta a usar apenas as tags automaticas.
                            </div>
                            <div class="chat-tag-editor-actions">
                                <button type="button" class="chat-tag-editor-action" id="chatTagEditorCancel">
                                    Cancelar
                                </button>
                                <button type="button" class="chat-tag-editor-action primary" id="chatTagEditorSave">
                                    Salvar tags
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="chat-audio-bar" id="chatAudioBar">
                <div class="chat-audio-bar-main">
                    <div class="chat-audio-bar-label">Audio do chat</div>
                    <div class="chat-audio-bar-status" id="chatAudioBarStatus">Audio pronto</div>
                    <div class="chat-audio-bar-help" id="chatAudioBarHelp">
                        Saida por audio desligada no momento.
                    </div>
                </div>
                <div class="chat-audio-bar-side">
                    <span class="chat-audio-pill" id="chatAudioQueuePill">Fila: 0</span>
                    <span class="chat-audio-pill" id="chatAudioSpeedPill">Velocidade: 1.0x</span>
                </div>
            </div>

            <div class="chat-workspace">
                <div class="chat-primary">
                    <div class="chat-content-tabs">
                        <div class="chat-tab-buttons">
                            <button type="button" class="chat-panel-tab is-active" data-chat-panel-trigger="messages">
                                Mensagens
                            </button>
                            <button type="button" class="chat-panel-tab" data-chat-panel-trigger="shares">
                                Compartilhamentos
                                <span class="chat-panel-tab-count"
                                    id="conversationSharesCount">{{ $conversationShareHistoryCount }}</span>
                            </button>
                            <button type="button" class="chat-panel-tab" data-chat-panel-trigger="global-shares">
                                Visão geral
                                <span class="chat-panel-tab-count"
                                    id="globalSharesCount">{{ $globalShareHistoryCount }}</span>
                            </button>
                        </div>
                        <div class="share-status-filters is-hidden" id="shareStatusFilters">
                            <button type="button" class="share-status-filter is-active"
                                data-share-filter="all">Todos</button>
                            <button type="button" class="share-status-filter" data-share-filter="active">Ativos</button>
                            <button type="button" class="share-status-filter"
                                data-share-filter="revoked">Revogados</button>
                        </div>
                        <div class="global-share-filters is-hidden" id="globalShareFilters">
                            <select class="global-share-select" id="globalShareConversationFilter">
                                <option value="all">Todas as conversas</option>
                            </select>
                        </div>
                    </div>

                    <div class="chat-panel is-active" data-chat-panel-content="messages">
                        <div class="messages-area" id="messagesArea">

                            @if (!isset($activeConversation) || $activeConversation->messages->isEmpty())
                                {{-- Estado vazio --}}
                                <div class="chat-empty" id="chatEmpty">
                                    <div class="chat-empty-icon">
                                        <img width="100%" src="/images/icone-robo-redondo.png" alt="">
                                    </div>
                                    <div class="chat-empty-kicker">Meu Assistente</div>
                                    <h2>Olá, {{ explode(' ', auth()->user()->name)[0] }}.</h2>
                                    <p>
                                        Posso te orientar com comunicação, mandato, demandas da cidade e oportunidades do
                                        radar
                                        federal. Me fala o que você precisa resolver agora.
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
                                    @php
                                        $shareState = $shareStates[$msg->id] ?? [
                                            'active_count' => 0,
                                            'active_recipients' => [],
                                            'shares' => [],
                                        ];
                                        $activeRecipients = $shareState['active_recipients'] ?? [];
                                        $visibleRecipients = array_slice($activeRecipients, 0, 2);
                                        $remainingRecipients = max(
                                            count($activeRecipients) - count($visibleRecipients),
                                            0,
                                        );
                                    @endphp
                                    <div class="message {{ $msg->role }}" data-id="{{ $msg->id }}"
                                        @if ($msg->role === 'assistant') data-audio-message-id="{{ $msg->id }}"
                                            data-audio-content="{{ e($msg->content) }}" @endif>
                                        <div class="message-avatar">
                                            @if ($msg->role === 'user')
                                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                            @else
                                                <svg viewBox="0 0 24 24" fill="currentColor" width="16"
                                                    height="16">
                                                    <path
                                                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z" />
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="message-bubble">
                                                {!! nl2br(e($msg->content)) !!}

                                                @if ($msg->role === 'user' && $msg->input_type === 'voice')
                                                    <div class="message-signals">
                                                        <span class="message-signal voice">
                                                            Mensagem enviada por voz
                                                        </span>
                                                    </div>
                                                @endif

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

                                                @if ($msg->role === 'assistant' && ($msg->metadata['memory']['active_count'] ?? 0) > 0)
                                                    <div class="message-signals">
                                                        <span class="message-signal memory">
                                                            Memoria ativa: {{ $msg->metadata['memory']['active_count'] }}
                                                        </span>
                                                    </div>
                                                @endif

                                                @if ($msg->role === 'assistant')
                                                    <div class="message-signals" data-audio-status-wrap></div>
                                                @endif

                                                @if ($msg->role === 'assistant' && !empty($exportSuggestions[$msg->id] ?? null))
                                                    <div class="export-suggestion"
                                                        data-export-message-id="{{ $msg->id }}">
                                                        <div class="export-suggestion-header">
                                                            <span class="export-suggestion-title">
                                                                {{ !empty($exportSuggestions[$msg->id]['saved_content']) ? 'Conteudo salvo' : 'Sugestao de exportacao' }}
                                                            </span>
                                                            <span class="export-suggestion-confidence">
                                                                {{ ucfirst($exportSuggestions[$msg->id]['confidence']) }}
                                                            </span>
                                                        </div>
                                                        <p>
                                                            {{ !empty($exportSuggestions[$msg->id]['saved_content']) ? $exportSuggestions[$msg->id]['saved_content']['title'] : $exportSuggestions[$msg->id]['reason'] }}
                                                        </p>
                                                        <div class="export-suggestion-actions">
                                                            @if (!empty($exportSuggestions[$msg->id]['saved_content']))
                                                                <a class="export-link"
                                                                    href="{{ $exportSuggestions[$msg->id]['saved_content']['redirect_url'] }}">
                                                                    Abrir módulo de conteudos
                                                                </a>
                                                                <span class="export-status">Ja salvo como rascunho.</span>
                                                            @else
                                                                <button class="export-btn"
                                                                    data-type="{{ $exportSuggestions[$msg->id]['type'] }}"
                                                                    data-label="{{ $exportSuggestions[$msg->id]['label'] }}"
                                                                    data-reason="{{ $exportSuggestions[$msg->id]['reason'] }}"
                                                                    data-confidence="{{ $exportSuggestions[$msg->id]['confidence'] }}"
                                                                    onclick="exportMessage({{ $msg->id }}, this)">
                                                                    {{ $exportSuggestions[$msg->id]['label'] }}
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="message-meta">
                                                <span>{{ $msg->created_at->format('H:i') }}</span>
                                                <div class="message-actions">
                                                    <button
                                                        class="share-btn {{ ($shareState['active_count'] ?? 0) > 0 ? 'is-shared' : '' }}"
                                                        data-message-id="{{ $msg->id }}"
                                                        data-role="{{ $msg->role }}"
                                                        data-content="{{ e($msg->content) }}"
                                                        onclick="openShareModal(this)">
                                                        {{ ($shareState['active_count'] ?? 0) > 0 ? 'Compartilhado (' . $shareState['active_count'] . ')' : 'Compartilhar' }}
                                                    </button>
                                                    @if ($msg->role === 'assistant')
                                                        <button class="audio-play-btn" type="button"
                                                            data-audio-play-button
                                                            onclick="toggleMessageAudioPlayback({{ $msg->id }}, @js($msg->content))">
                                                            Ouvir
                                                        </button>
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
                                            @if (($shareState['active_count'] ?? 0) > 0)
                                                <div class="message-share-meta" data-message-share-meta>
                                                    Compartilhado com
                                                    {{ implode(', ', $visibleRecipients) }}{{ $remainingRecipients > 0 ? ' +' . $remainingRecipients : '' }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                        </div>
                    </div>

                    <div class="chat-panel" data-chat-panel-content="shares">
                        <div class="conversation-shares" id="conversationSharesPanel">
                            <div class="share-feed-empty" id="conversationSharesEmpty">
                                Os compartilhamentos desta conversa aparecerao aqui para consulta e revogacao segura.
                            </div>
                            <div class="share-feed-list" id="conversationSharesList"></div>
                        </div>
                    </div>

                    <div class="chat-panel" data-chat-panel-content="global-shares">
                        <div class="conversation-shares" id="globalSharesPanel">
                            <div class="share-feed-empty" id="globalSharesEmpty">
                                Os compartilhamentos de todas as conversas aparecerao aqui para consulta e revogacao segura.
                            </div>
                            <div class="share-feed-list" id="globalSharesList"></div>
                        </div>
                    </div>

                    {{-- Input --}}
                    <div class="input-area">
                        <div class="input-box">
                            <textarea id="msg-input" placeholder="Pergunte qualquer coisa sobre seu município, mandato ou comunicação..."
                                rows="1" onkeydown="handleEnter(event)" oninput="autoResize(this)"></textarea>
                            <div class="input-actions">
                                <button class="btn-voice" id="btnVoice" type="button" title="Ditado por voz"
                                    onclick="toggleVoiceInput()">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm-1-9c0-.55.45-1 1-1s1 .45 1 1v6c0 .55-.45 1-1 1s-1-.45-1-1V5zm6 6c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z" />
                                    </svg>
                                </button>
                                <button class="btn-voice" id="btnAudioOutput" type="button"
                                    title="Ler respostas em audio" onclick="toggleAudioOutput()">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77zM3 9v6h4l5 5V4L7 9H3zm8.5 3c0-1.77 1-3.29 2.5-4.03v8.05A4.49 4.49 0 0 1 11.5 12z" />
                                    </svg>
                                </button>
                                <button class="btn-voice" id="btnReplayLastAudio" type="button"
                                    title="Repetir ultima resposta" onclick="replayLastAssistantResponse()">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6a6 6 0 0 1-10.24 4.24l-1.42 1.42A8 8 0 0 0 20 13c0-4.42-3.58-8-8-8z" />
                                    </svg>
                                </button>
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
                            <span class="audio-status" id="audioStatus"></span>
                        </p>
                        <div class="audio-preferences">
                            <button type="button" class="audio-pref-chip" id="audioInputPrefChip"
                                onclick="toggleAudioInputPreference()">
                                Entrada por voz
                            </button>
                            <div class="audio-speed-options" id="audioSpeedOptions">
                                <button type="button" class="audio-speed-btn" data-audio-speed="0.85"
                                    onclick="updateSpeechRatePreference(0.85)">
                                    0.85x
                                </button>
                                <button type="button" class="audio-speed-btn" data-audio-speed="1"
                                    onclick="updateSpeechRatePreference(1)">
                                    1.0x
                                </button>
                                <button type="button" class="audio-speed-btn" data-audio-speed="1.15"
                                    onclick="updateSpeechRatePreference(1.15)">
                                    1.15x
                                </button>
                                <button type="button" class="audio-speed-btn" data-audio-speed="1.3"
                                    onclick="updateSpeechRatePreference(1.3)">
                                    1.3x
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                @if (!empty($chatAlerts ?? []))
                    <aside class="chat-alerts-sidebar" id="chatAlertsSidebar">
                        <div class="chat-alerts-sidebar-header">
                            <div class="chat-alerts-sidebar-title">Alertas proativos</div>
                            <div class="chat-alerts-sidebar-help">
                                Acompanhe riscos, oportunidades e prioridades sem reduzir a area principal do chat.
                            </div>
                        </div>
                        <div class="chat-alerts-bar" id="chatAlertsBar">
                            @foreach ($chatAlerts as $alert)
                                <div class="chat-alert-card" data-severity="{{ $alert['severity'] }}"
                                    data-alert-key="{{ $alert['key'] }}">
                                    <div class="chat-alert-top">
                                        <span class="chat-alert-kicker">Alerta proativo</span>
                                        <div class="chat-alert-top-right">
                                            <span class="chat-alert-level">
                                                {{ $alert['severity'] === 'high' ? 'Agora' : ($alert['severity'] === 'medium' ? 'Atencao' : 'Radar') }}
                                            </span>
                                            <button type="button" class="chat-alert-close" title="Dispensar por hoje"
                                                onclick="dismissAlert(this)">
                                                <svg viewBox="0 0 24 24" fill="currentColor" width="14"
                                                    height="14">
                                                    <path
                                                        d="M18.3 5.71 12 12l6.3 6.29-1.41 1.41L10.59 13.41 4.29 19.7 2.88 18.29 9.17 12 2.88 5.71 4.29 4.3l6.3 6.29 6.29-6.29z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="chat-alert-title">{{ $alert['title'] }}</div>
                                    @if (!empty($alert['related_to_active_conversation']))
                                        <div class="chat-alert-relevance">
                                            {{ $alert['relevance_label'] ?? 'Relacionado com esta conversa' }}
                                        </div>
                                    @endif
                                    <p class="chat-alert-summary">{{ $alert['summary'] }}</p>
                                    <div class="chat-alert-footer">
                                        @if (($alert['action_type'] ?? null) === 'link')
                                            <a class="chat-alert-action" href="{{ $alert['action_value'] }}">
                                                {{ $alert['action_label'] }}
                                            </a>
                                        @elseif (($alert['action_type'] ?? null) === 'prefill')
                                            <button type="button" class="chat-alert-action"
                                                onclick="runAlertAction(this)"
                                                data-prefill="{{ $alert['action_value'] }}">
                                                {{ $alert['action_label'] }}
                                            </button>
                                        @elseif (($alert['action_type'] ?? null) === 'prefill_new')
                                            <button type="button" class="chat-alert-action"
                                                onclick="startAlertConversation(this)"
                                                data-prefill="{{ $alert['action_value'] }}">
                                                {{ $alert['action_label'] }}
                                            </button>
                                        @elseif (($alert['action_type'] ?? null) === 'generate_briefing')
                                            <button type="button" class="chat-alert-action"
                                                onclick="generateBriefingFromAlert(this)"
                                                data-url="{{ $alert['action_value'] }}">
                                                {{ $alert['action_label'] }}
                                            </button>
                                        @endif
                                        <button type="button" class="chat-alert-dismiss" onclick="dismissAlert(this)">
                                            Dispensar hoje
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </aside>
                @endif
            </div>







        </div>
    </div>
    </div>

    <div class="share-modal" id="shareModal">
        <div class="share-modal-card">
            <div class="share-modal-header">
                <div>
                    <div class="share-modal-title">Compartilhar trecho da conversa</div>
                    <div class="share-modal-subtitle">Escolha exatamente o trecho que deve ser enviado para outro
                        usuario.
                    </div>
                </div>
                <button type="button" class="share-modal-close" onclick="closeShareModal()">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                        <path
                            d="M18.3 5.71 12 12l6.3 6.29-1.41 1.41L10.59 13.41 4.29 19.7 2.88 18.29 9.17 12 2.88 5.71 4.29 4.3l6.3 6.29 6.29-6.29z" />
                    </svg>
                </button>
            </div>

            <div class="share-modal-form">
                <input type="hidden" id="shareMessageId">

                <div class="share-modal-field">
                    <label for="shareRecipient">Destinatario</label>
                    <select id="shareRecipient" {{ $shareRecipients->isEmpty() ? 'disabled' : '' }}>
                        <option value="">Selecione...</option>
                        @foreach ($shareRecipients as $recipient)
                            <option value="{{ $recipient->id }}">
                                {{ $recipient->name }} —
                                {{ $recipient->role === 'admin' ? 'Administrador do município' : 'Usuario do município' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="share-modal-help">
                        @if ($shareRecipients->isEmpty())
                            Nenhum destinatario elegivel encontrado no momento.
                        @else
                            O compartilhamento fica restrito ao destinatario escolhido, ao dono da conversa e a
                            administradores autorizados.
                        @endif
                    </div>
                </div>

                <div class="share-modal-field">
                    <label for="shareExcerpt">Trecho compartilhado</label>
                    <textarea id="shareExcerpt" placeholder="Escolha ou edite o trecho que sera compartilhado."></textarea>
                    <div class="share-modal-help">Se você tiver selecionado um trecho no texto, ele entra aqui
                        automaticamente.</div>
                </div>

                <div class="share-modal-field">
                    <label for="shareNote">Observacao opcional</label>
                    <textarea id="shareNote" placeholder="Explique rápidamente por que esta enviando este trecho."></textarea>
                </div>

                <div class="share-modal-field">
                    <label>Histórico deste compartilhamento</label>
                    <div class="share-history-list" id="shareHistoryList">
                        <div class="share-history-empty">Nenhum compartilhamento feito ainda para esta mensagem.</div>
                    </div>
                </div>

                <div class="share-modal-actions">
                    <div class="share-modal-status" id="shareModalStatus"></div>
                    <button type="button" class="share-modal-submit" id="shareModalSubmit" onclick="submitShare()"
                        {{ $shareRecipients->isEmpty() ? 'disabled' : '' }}>
                        Compartilhar trecho
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const mayor = '{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}';
        const originLabels = {
            chat: 'Chat',
            federal_programs: 'Radar Federal',
            resolve_ai: 'Resolve ai',
            content: 'Conteudo',
            briefings: 'Briefing',
        };
        const intentLabels = {
            planejamento: 'Planejamento',
            comunicação: 'Comunicação',
            captação: 'Captacao',
            gestao_de_crise: 'Crise',
            demanda_operacional: 'Demanda',
            orientacao_geral: 'Orientacao',
        };
        const shareRecipients = @json(($shareRecipients ?? collect())->values());
        const shareStatesByMessage = @json($shareStates ?? []);
        const conversationMessageIndex = @json($messageIndex);
        const audioPreferences = @json(data_get(auth()->user()->preferences, 'chat_audio', []));
        const serverAudioCapabilities = @json($audioServerCapabilities ?? []);
        let globalShareFeed = @json($globalShareFeed ?? []);
        let activeTagFilter = 'all';
        let activePeriodFilter = 'all';
        let activeConversationShareFilter = 'all';
        let activeChatPanel = 'messages';
        let activeShareFilter = 'all';
        let activeGlobalConversationFilter = 'all';
        const alertDismissStorageKey = `mm-chat-alert-dismissed:${new Date().toISOString().slice(0, 10)}`;
        const speechRecognitionClass = window.SpeechRecognition || window.webkitSpeechRecognition || null;
        const supportsSpeechOutput = 'speechSynthesis' in window && 'SpeechSynthesisUtterance' in window;
        const supportsServerVoiceInput = Boolean(serverAudioCapabilities.input_enabled);
        const supportsServerAudioOutput = Boolean(serverAudioCapabilities.output_enabled);
        const supportsAnyVoiceInput = Boolean(speechRecognitionClass) || supportsServerVoiceInput;
        const supportsAnyAudioOutput = supportsSpeechOutput || supportsServerAudioOutput;
        let recognition = null;
        let mediaRecorder = null;
        let mediaRecorderStream = null;
        let mediaRecorderChunks = [];
        let isListening = false;
        let isTranscribingVoice = false;
        let currentAudioUtterance = null;
        let currentAudioElement = null;
        let currentServerAudioUrl = null;
        let currentAudioMessageId = null;
        let currentAudioRequestId = 0;
        let audioStopRequested = false;
        let lastAssistantAudioMessage = null;
        let audioQueue = [];
        const readAssistantMessageIds = new Set();
        let pendingInputType = 'text';
        let pendingVoiceTranscript = null;
        let prefersVoiceInput = typeof audioPreferences.input_enabled === 'boolean' ? audioPreferences.input_enabled :
            supportsAnyVoiceInput;
        let prefersAudioOutput = typeof audioPreferences.output_enabled === 'boolean' ? audioPreferences.output_enabled :
            false;
        let speechRate = Number(audioPreferences.speech_rate || 1);

        // ID da conversa ativa (null = nova)
        let activeConvId = @json(optional($activeConversation)->id);

        // ── Textarea auto-resize ─────────────────────────────────
        function autoResize(el) {
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 140) + 'px';
            document.getElementById('btnSend').disabled = el.value.trim().length === 0;
        }

        function resetPendingVoicePayload() {
            pendingInputType = 'text';
            pendingVoiceTranscript = null;
        }

        function applyVoiceTranscript(transcript) {
            const input = document.getElementById('msg-input');
            if (!input) return;

            input.value = transcript;
            pendingInputType = transcript.trim() ? 'voice' : 'text';
            pendingVoiceTranscript = transcript.trim() || null;
            autoResize(input);
            input.focus();
        }

        async function persistAudioPreferences(partialPreferences = {}) {
            try {
                const res = await fetch(`/mayor/chat/preferences/audio`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(partialPreferences),
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.error || 'Nao foi possivel salvar as preferencias de audio.');
                }

                if (typeof data.preferences?.input_enabled === 'boolean') {
                    prefersVoiceInput = data.preferences.input_enabled;
                }

                if (typeof data.preferences?.output_enabled === 'boolean') {
                    prefersAudioOutput = data.preferences.output_enabled;
                }

                if (typeof data.preferences?.speech_rate === 'number') {
                    speechRate = Number(data.preferences.speech_rate) || 1;
                }

                updateAudioControls();
                updateGlobalAudioBar();
            } catch (error) {
                appendError(error.message || 'Nao foi possivel salvar as preferencias de audio.');
            }
        }

        function hasActiveAudioPlayback() {
            return Boolean(currentAudioUtterance || currentAudioElement);
        }

        function releaseServerAudioUrl() {
            if (!currentServerAudioUrl) return;

            URL.revokeObjectURL(currentServerAudioUrl);
            currentServerAudioUrl = null;
        }

        function cleanupServerAudioPlayback() {
            if (currentAudioElement) {
                currentAudioElement.pause();
                currentAudioElement.src = '';
                currentAudioElement = null;
            }

            releaseServerAudioUrl();
        }

        function stopServerRecordingStream() {
            if (!mediaRecorderStream) return;

            mediaRecorderStream.getTracks().forEach(track => track.stop());
            mediaRecorderStream = null;
        }

        function finishAudioPlayback(messageId = null, markAsRead = false, continueQueue = true) {
            if (messageId && markAsRead) {
                readAssistantMessageIds.add(String(messageId));
            }

            if (messageId) {
                renderAssistantAudioState(String(messageId), 'idle');
            }

            audioStopRequested = false;
            currentAudioUtterance = null;
            currentAudioMessageId = null;
            cleanupServerAudioPlayback();
            updateReplayButtonState();
            updateGlobalAudioBar();
            if (continueQueue) {
                playNextAudioInQueue();
            }
        }

        function cancelSpeechOutput() {
            audioStopRequested = true;
            currentAudioRequestId += 1;

            if (supportsSpeechOutput) {
                window.speechSynthesis.cancel();
            }

            cleanupServerAudioPlayback();
            currentAudioUtterance = null;
            currentAudioMessageId = null;
        }

        function normalizeSpeechRate(value) {
            const parsed = Number(value);
            if (!Number.isFinite(parsed)) return 1;
            return Math.min(1.4, Math.max(0.7, parsed));
        }

        function formatSpeechRateLabel(value) {
            return `${normalizeSpeechRate(value).toFixed(2).replace(/0$/, '').replace(/\.00$/, '')}x`;
        }

        async function transcribeServerAudio(audioBlob, mimeType = 'audio/webm') {
            const extension = mimeType.includes('mp4') ? 'm4a' :
                mimeType.includes('ogg') ? 'ogg' :
                (mimeType.includes('mpeg') || mimeType.includes('mp3')) ? 'mp3' :
                'webm';
            const formData = new FormData();

            formData.append('audio', audioBlob, `chat-voice.${extension}`);

            const res = await fetch(`/mayor/chat/audio/transcribe`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Nao foi possivel transcrever o audio agora.');
            }

            return String(data.transcript || '').trim();
        }

        async function startServerVoiceCapture() {
            if (!navigator.mediaDevices?.getUserMedia || typeof MediaRecorder === 'undefined') {
                appendError('Este navegador não consegue gravar audio para o fallback do servidor.');
                return;
            }

            try {
                mediaRecorderStream = await navigator.mediaDevices.getUserMedia({
                    audio: true
                });

                const supportedMimeType = [
                    'audio/webm;codecs=opus',
                    'audio/webm',
                    'audio/mp4',
                    'audio/ogg',
                ].find(type => typeof MediaRecorder.isTypeSupported !== 'function' || MediaRecorder.isTypeSupported(
                    type));

                mediaRecorderChunks = [];
                mediaRecorder = supportedMimeType ?
                    new MediaRecorder(mediaRecorderStream, {
                        mimeType: supportedMimeType
                    }) :
                    new MediaRecorder(mediaRecorderStream);

                mediaRecorder.onstart = () => {
                    isListening = true;
                    isTranscribingVoice = false;
                    updateAudioControls();
                };

                mediaRecorder.ondataavailable = event => {
                    if (event.data && event.data.size > 0) {
                        mediaRecorderChunks.push(event.data);
                    }
                };

                mediaRecorder.onerror = () => {
                    isListening = false;
                    mediaRecorder = null;
                    mediaRecorderChunks = [];
                    stopServerRecordingStream();
                    updateAudioControls();
                    appendError('Nao foi possivel capturar o audio agora.');
                };

                mediaRecorder.onstop = async () => {
                    const mimeType = mediaRecorder?.mimeType || mediaRecorderChunks[0]?.type || 'audio/webm';
                    const audioBlob = mediaRecorderChunks.length > 0 ? new Blob(mediaRecorderChunks, {
                        type: mimeType
                    }) : null;

                    isListening = false;
                    mediaRecorder = null;
                    mediaRecorderChunks = [];
                    stopServerRecordingStream();
                    updateAudioControls();

                    if (!audioBlob || audioBlob.size === 0) {
                        appendError(
                            'Nao consegui ouvir sua fala. Tente novamente em um ambiente mais silencioso.');
                        return;
                    }

                    isTranscribingVoice = true;
                    updateAudioControls();

                    try {
                        const transcript = await transcribeServerAudio(audioBlob, mimeType);
                        applyVoiceTranscript(transcript);

                        if (!prefersVoiceInput) {
                            prefersVoiceInput = true;
                            updateAudioControls();
                            persistAudioPreferences({
                                input_enabled: true,
                                output_enabled: prefersAudioOutput,
                                speech_rate: speechRate,
                            });
                        }
                    } catch (error) {
                        appendError(error.message || 'Nao foi possivel transcrever o audio agora.');
                    } finally {
                        isTranscribingVoice = false;
                        updateAudioControls();
                    }
                };

                mediaRecorder.start();
            } catch (error) {
                mediaRecorder = null;
                mediaRecorderChunks = [];
                isListening = false;
                stopServerRecordingStream();
                updateAudioControls();
                appendError('O navegador bloqueou o microfone. Libere a permissao e tente novamente.');
            }
        }

        function getAssistantMessageNode(messageId) {
            if (!messageId) return null;
            return document.querySelector(`.message[data-audio-message-id="${messageId}"]`);
        }

        function renderAssistantAudioState(messageId, state = 'idle') {
            const message = getAssistantMessageNode(messageId);
            if (!message) return;

            const button = message.querySelector('[data-audio-play-button]');
            const statusWrap = message.querySelector('[data-audio-status-wrap]');
            const isRead = readAssistantMessageIds.has(String(messageId));

            if (button) {
                button.classList.toggle('is-playing', state === 'playing');
                button.classList.toggle('is-read', state !== 'playing' && isRead);
                button.textContent = state === 'playing' ? 'Parar audio' : (isRead ? 'Ouvir novamente' : 'Ouvir');
            }

            if (!statusWrap) return;

            if (state === 'playing') {
                statusWrap.innerHTML =
                    '<span class="message-signal audio-status">Lendo resposta agora</span>';
                return;
            }

            if (isRead) {
                statusWrap.innerHTML =
                    '<span class="message-signal audio-status is-read">Resposta lida</span>';
                return;
            }

            statusWrap.innerHTML = '';
        }

        function renderAllAssistantAudioStates() {
            document.querySelectorAll('.message[data-audio-message-id]').forEach(message => {
                renderAssistantAudioState(message.dataset.audioMessageId, currentAudioMessageId === message.dataset
                    .audioMessageId ? 'playing' : 'idle');
            });
        }

        function setLastAssistantAudioMessage(messageId, content) {
            const text = (content || '').trim();
            if (!messageId || !text) return;

            lastAssistantAudioMessage = {
                id: String(messageId),
                content: text,
            };
        }

        function syncLastAssistantAudioMessageFromDom() {
            const messages = [...document.querySelectorAll('.message[data-audio-message-id]')];
            const lastMessage = messages.at(-1);
            if (!lastMessage) return;

            setLastAssistantAudioMessage(lastMessage.dataset.audioMessageId, lastMessage.dataset.audioContent || '');
        }

        function enqueueAssistantAudio(messageId, content) {
            const text = (content || '').trim();
            if (!messageId || !text) return;

            const normalizedId = String(messageId);
            const alreadyQueued = audioQueue.some(item => item.id === normalizedId);
            if (alreadyQueued || currentAudioMessageId === normalizedId) {
                updateGlobalAudioBar();
                return;
            }

            audioQueue.push({
                id: normalizedId,
                content: text,
            });
            updateGlobalAudioBar();
        }

        function playNextAudioInQueue() {
            if (!prefersAudioOutput || hasActiveAudioPlayback() || audioQueue.length === 0) {
                updateGlobalAudioBar();
                return;
            }

            const nextItem = audioQueue.shift();
            if (!nextItem) {
                updateGlobalAudioBar();
                return;
            }

            speakAssistantResponse(nextItem.content, nextItem.id, false);
        }

        function updateReplayButtonState() {
            const replayButton = document.getElementById('btnReplayLastAudio');
            if (!replayButton) return;

            const disabled = !supportsAnyAudioOutput || !lastAssistantAudioMessage?.content;
            replayButton.disabled = disabled;
            replayButton.classList.toggle('is-active', !disabled && currentAudioMessageId === lastAssistantAudioMessage
                ?.id);
            replayButton.title = disabled ? 'Nenhuma resposta disponível para replay' : 'Repetir ultima resposta';
        }

        function updateGlobalAudioBar() {
            const bar = document.getElementById('chatAudioBar');
            const status = document.getElementById('chatAudioBarStatus');
            const help = document.getElementById('chatAudioBarHelp');
            const queuePill = document.getElementById('chatAudioQueuePill');
            const speedPill = document.getElementById('chatAudioSpeedPill');
            const speedButtons = document.querySelectorAll('[data-audio-speed]');

            if (bar) {
                bar.classList.toggle('is-hidden', !supportsAnyAudioOutput && !supportsAnyVoiceInput);
            }

            if (speedButtons.length > 0) {
                speedButtons.forEach(button => {
                    button.disabled = !supportsAnyAudioOutput;
                    button.classList.toggle('is-active', Number(button.dataset.audioSpeed) === normalizeSpeechRate(
                        speechRate));
                });
            }

            if (speedPill) {
                speedPill.textContent = `Velocidade: ${formatSpeechRateLabel(speechRate)}`;
            }

            if (queuePill) {
                queuePill.textContent = `Fila: ${audioQueue.length}`;
            }

            if (!status || !help) return;

            if (!supportsAnyAudioOutput) {
                status.textContent = 'Leitura por voz indisponível';
                help.textContent = 'Nem o navegador nem o servidor estao disponiveis para reproduzir as respostas.';
                return;
            }

            if (currentAudioMessageId) {
                status.textContent = 'Lendo resposta agora';
                help.textContent = audioQueue.length > 0 ?
                    `${audioQueue.length} resposta(s) aguardando na fila.` :
                    'Sem itens na fila no momento.';
                return;
            }

            if (isTranscribingVoice) {
                status.textContent = 'Transcrevendo audio';
                help.textContent = 'O servidor esta convertendo sua fala em texto para manter a mesma experiencia do chat.';
                return;
            }

            if (!prefersAudioOutput) {
                status.textContent = 'Saida por audio desativada';
                help.textContent = 'Ative a leitura para ouvir automaticamente as novas respostas.';
                return;
            }

            if (audioQueue.length > 0) {
                status.textContent = 'Fila de leitura pronta';
                help.textContent = `${audioQueue.length} resposta(s) aguardando para leitura.`;
                return;
            }

            status.textContent = 'Audio pronto';
            help.textContent = supportsSpeechOutput ?
                'Novas respostas podem ser lidas automaticamente ou por mensagem.' :
                'Fallback server-side pronto para ler as respostas mantendo a mesma UX.';
        }

        async function playServerSynthesizedAudio(messageId, content, interruptCurrent = true) {
            if (!supportsServerAudioOutput || !prefersAudioOutput || !messageId) return;

            const normalizedId = String(messageId);
            const text = (content || '').trim();
            if (!text) return;

            if (interruptCurrent) {
                cancelSpeechOutput();
            } else if (hasActiveAudioPlayback()) {
                enqueueAssistantAudio(normalizedId, text);
                return;
            }

            const requestId = ++currentAudioRequestId;
            audioStopRequested = false;
            currentAudioUtterance = {
                source: 'server'
            };
            currentAudioMessageId = normalizedId;
            setLastAssistantAudioMessage(normalizedId, text);
            renderAssistantAudioState(normalizedId, 'playing');
            updateReplayButtonState();
            updateGlobalAudioBar();

            try {
                const res = await fetch(
                    `/mayor/chat/messages/${normalizedId}/audio?speed=${encodeURIComponent(normalizeSpeechRate(speechRate))}`, {
                        headers: {
                            'Accept': 'audio/mpeg',
                        },
                    });

                if (!res.ok) {
                    const errorText = (await res.text()).trim();
                    throw new Error(errorText || 'Nao foi possivel gerar o audio agora.');
                }

                const audioBlob = await res.blob();

                if (requestId !== currentAudioRequestId || currentAudioMessageId !== normalizedId) {
                    return;
                }

                cleanupServerAudioPlayback();

                const audio = new Audio();
                currentServerAudioUrl = URL.createObjectURL(audioBlob);
                currentAudioElement = audio;
                currentAudioUtterance = null;
                audio.src = currentServerAudioUrl;
                audio.onended = () => finishAudioPlayback(normalizedId, !audioStopRequested, !audioStopRequested);
                audio.onerror = () => finishAudioPlayback(normalizedId, false);
                await audio.play();
                updateReplayButtonState();
                updateGlobalAudioBar();
            } catch (error) {
                if (requestId === currentAudioRequestId) {
                    appendError(error.message || 'Nao foi possivel gerar o audio agora.');
                    finishAudioPlayback(normalizedId, false);
                }
            }
        }

        function speakAssistantResponse(content, messageId = null, interruptCurrent = true) {
            if (!prefersAudioOutput) return;

            const text = (content || '').trim();
            if (!text) return;

            if (!supportsSpeechOutput && supportsServerAudioOutput && messageId) {
                playServerSynthesizedAudio(messageId, text, interruptCurrent);
                return;
            }

            if (!supportsSpeechOutput) return;

            if (interruptCurrent) {
                cancelSpeechOutput();
            } else if (hasActiveAudioPlayback()) {
                enqueueAssistantAudio(messageId, text);
                return;
            }

            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'pt-BR';
            utterance.rate = normalizeSpeechRate(speechRate);
            utterance.pitch = 1;
            audioStopRequested = false;
            currentAudioUtterance = utterance;
            currentAudioMessageId = messageId ? String(messageId) : null;

            if (messageId) {
                setLastAssistantAudioMessage(messageId, text);
            }

            utterance.onstart = () => {
                if (messageId) {
                    renderAssistantAudioState(String(messageId), 'playing');
                }
                updateReplayButtonState();
                updateGlobalAudioBar();
            };

            utterance.onend = () => {
                finishAudioPlayback(messageId, !audioStopRequested, !audioStopRequested);
            };

            utterance.onerror = () => {
                finishAudioPlayback(messageId, false);
            };

            window.speechSynthesis.speak(utterance);
        }

        function toggleMessageAudioPlayback(messageId, content) {
            if (!supportsAnyAudioOutput) {
                appendError('Este ambiente não suporta leitura das respostas agora.');
                return;
            }

            if (!prefersAudioOutput) {
                prefersAudioOutput = true;
                updateAudioControls();
                persistAudioPreferences({
                    input_enabled: prefersVoiceInput,
                    output_enabled: true,
                    speech_rate: speechRate,
                });
            }

            const normalizedId = String(messageId);

            if (currentAudioMessageId === normalizedId) {
                cancelSpeechOutput();
                renderAssistantAudioState(normalizedId, 'idle');
                updateReplayButtonState();
                updateGlobalAudioBar();
                return;
            }

            audioQueue = audioQueue.filter(item => item.id !== normalizedId);
            speakAssistantResponse(content, normalizedId, true);
        }

        function replayLastAssistantResponse() {
            if (!lastAssistantAudioMessage?.content) {
                appendError('Ainda não ha resposta do assistente para reproduzir novamente.');
                return;
            }

            if (!prefersAudioOutput) {
                prefersAudioOutput = true;
                updateAudioControls();
                persistAudioPreferences({
                    input_enabled: prefersVoiceInput,
                    output_enabled: true,
                    speech_rate: speechRate,
                });
            }

            speakAssistantResponse(lastAssistantAudioMessage.content, lastAssistantAudioMessage.id, true);
        }

        function updateSpeechRatePreference(value) {
            speechRate = normalizeSpeechRate(value);
            updateGlobalAudioBar();
            persistAudioPreferences({
                input_enabled: prefersVoiceInput,
                output_enabled: prefersAudioOutput,
                speech_rate: speechRate,
            });
        }

        function updateAudioControls() {
            const voiceButton = document.getElementById('btnVoice');
            const audioOutputButton = document.getElementById('btnAudioOutput');
            const audioInputPrefChip = document.getElementById('audioInputPrefChip');
            const replayButton = document.getElementById('btnReplayLastAudio');
            const audioStatus = document.getElementById('audioStatus');
            const voiceInputAvailable = supportsAnyVoiceInput;

            if (voiceButton) {
                voiceButton.disabled = !voiceInputAvailable || !prefersVoiceInput || isSubmitting || isTranscribingVoice;
                voiceButton.classList.toggle('is-listening', isListening);
                voiceButton.classList.toggle('is-active', prefersVoiceInput && !isListening);
                voiceButton.title = isListening ? 'Parar ditado por voz' : 'Ditado por voz';
            }

            if (audioOutputButton) {
                audioOutputButton.disabled = !supportsAnyAudioOutput;
                audioOutputButton.classList.toggle('is-active', prefersAudioOutput && supportsAnyAudioOutput);
                audioOutputButton.title = prefersAudioOutput ? 'Desativar leitura das respostas' :
                    'Ler respostas em audio';
            }

            if (replayButton) {
                replayButton.disabled = !supportsAnyAudioOutput || !lastAssistantAudioMessage?.content;
            }

            document.querySelectorAll('[data-audio-speed]').forEach(button => {
                button.disabled = !supportsAnyAudioOutput;
                button.classList.toggle('is-active', Number(button.dataset.audioSpeed) === normalizeSpeechRate(
                    speechRate));
            });

            if (audioInputPrefChip) {
                audioInputPrefChip.disabled = !voiceInputAvailable;
                audioInputPrefChip.classList.toggle('is-active', prefersVoiceInput && voiceInputAvailable);
            }

            if (!audioStatus) return;

            if (isListening) {
                audioStatus.textContent = 'Ouvindo agora. Fale e o texto sera colocado no campo.';
                return;
            }

            if (isTranscribingVoice) {
                audioStatus.textContent = 'Transcrevendo audio capturado pelo microfone.';
                return;
            }

            const inputStatus = voiceInputAvailable ?
                (prefersVoiceInput ?
                    (speechRecognitionClass ? 'Entrada por voz ativada.' : 'Entrada por voz ativada via servidor.') :
                    'Entrada por voz desativada.') :
                'Entrada por voz indisponível neste ambiente.';
            const outputStatus = supportsAnyAudioOutput ?
                (prefersAudioOutput ?
                    (supportsSpeechOutput ? 'Leitura das respostas ativada.' :
                        'Leitura das respostas ativada via servidor.') :
                    'Leitura das respostas desativada.') :
                'Leitura das respostas indisponível neste ambiente.';

            audioStatus.textContent = `${inputStatus} ${outputStatus}`;
        }

        function ensureSpeechRecognition() {
            if (!speechRecognitionClass || recognition) return recognition;

            recognition = new speechRecognitionClass();
            recognition.lang = 'pt-BR';
            recognition.interimResults = true;
            recognition.continuous = false;
            recognition.maxAlternatives = 1;

            recognition.onstart = () => {
                isListening = true;
                updateAudioControls();
            };

            recognition.onresult = event => {
                let transcript = '';

                for (let i = event.resultIndex; i < event.results.length; i++) {
                    transcript += event.results[i][0]?.transcript || '';
                }

                applyVoiceTranscript(transcript.trim());

                if (!prefersVoiceInput) {
                    prefersVoiceInput = true;
                    updateAudioControls();
                    persistAudioPreferences({
                        input_enabled: true,
                        output_enabled: prefersAudioOutput,
                        speech_rate: speechRate,
                    });
                }
            };

            recognition.onerror = event => {
                isListening = false;
                updateAudioControls();

                const message = event.error === 'not-allowed' ?
                    'O navegador bloqueou o microfone. Libere a permissao e tente novamente.' :
                    event.error === 'no-speech' ?
                    'Nao consegui ouvir sua fala. Tente novamente em um ambiente mais silencioso.' :
                    'Nao foi possivel capturar o audio agora.';

                appendError(message);
            };

            recognition.onend = () => {
                isListening = false;
                updateAudioControls();
            };

            return recognition;
        }

        function toggleVoiceInput() {
            if (!supportsAnyVoiceInput) {
                appendError('Este ambiente não suporta entrada por voz agora.');
                return;
            }

            if (!prefersVoiceInput) {
                appendError('Ative a preferencia de entrada por voz para usar o microfone.');
                return;
            }

            if (isSubmitting || isTranscribingVoice) return;

            if (!speechRecognitionClass) {
                if (isListening) {
                    mediaRecorder?.stop();
                    return;
                }

                startServerVoiceCapture();
                return;
            }

            const instance = ensureSpeechRecognition();

            if (isListening) {
                instance.stop();
                return;
            }

            try {
                instance.start();
            } catch (error) {
                appendError('Nao foi possivel iniciar o microfone agora.');
            }
        }

        function toggleAudioOutput() {
            if (!supportsAnyAudioOutput) {
                appendError('Este ambiente não suporta leitura automatica das respostas.');
                return;
            }

            prefersAudioOutput = !prefersAudioOutput;
            persistAudioPreferences({
                input_enabled: prefersVoiceInput,
                output_enabled: prefersAudioOutput,
                speech_rate: speechRate,
            });

            if (!prefersAudioOutput) {
                cancelSpeechOutput();
                audioQueue = [];
                renderAllAssistantAudioStates();
            }

            updateAudioControls();
            updateReplayButtonState();
            updateGlobalAudioBar();
        }

        function toggleAudioInputPreference() {
            if (!supportsAnyVoiceInput) {
                appendError('Este ambiente não suporta entrada por voz agora.');
                return;
            }

            if (isListening) {
                if (speechRecognitionClass) {
                    recognition?.stop();
                } else {
                    mediaRecorder?.stop();
                }
            }

            prefersVoiceInput = !prefersVoiceInput;

            if (!prefersVoiceInput) {
                resetPendingVoicePayload();
            }

            updateAudioControls();
            persistAudioPreferences({
                input_enabled: prefersVoiceInput,
                output_enabled: prefersAudioOutput,
                speech_rate: speechRate,
            });
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
            resetPendingVoicePayload();
            input.value = btn.textContent.trim();
            autoResize(input);
            input.focus();
        }

        function runAlertAction(button) {
            const prefill = button?.dataset.prefill || '';
            if (!prefill) return;

            const input = document.getElementById('msg-input');
            resetPendingVoicePayload();
            input.value = prefill;
            autoResize(input);
            input.focus();
        }

        function startAlertConversation(button) {
            const prefill = button?.dataset.prefill || '';
            if (!prefill) return;

            activeConvId = null;
            clearConversationRuntimeState();
            updateConversationContextBar(null);
            setActiveConversationItem(null);
            setActiveChatPanel('messages');
            const messagesArea = document.getElementById('messagesArea');
            if (messagesArea) {
                messagesArea.innerHTML = renderEmptyState(
                    'Nova conversa estrategica',
                    'Vamos transformar esse alerta em um plano objetivo com contexto e proximo passo.',
                    true
                );
            }

            const input = document.getElementById('msg-input');
            resetPendingVoicePayload();
            input.value = prefill;
            autoResize(input);
            input.focus();
        }

        async function generateBriefingFromAlert(button) {
            const url = button?.dataset.url;
            if (!url) return;

            const originalLabel = button.textContent;
            button.disabled = true;
            button.textContent = 'Gerando...';

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                });

                const data = await res.json();

                if (!res.ok || !data.ok) {
                    throw new Error(data.error || 'Nao foi possivel gerar o briefing agora.');
                }

                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }

                button.textContent = 'Briefing pronto';
            } catch (error) {
                button.disabled = false;
                button.textContent = originalLabel;
                appendError(error.message || 'Nao foi possivel gerar o briefing.');
            }
        }

        function getDismissedAlertKeys() {
            try {
                return JSON.parse(localStorage.getItem(alertDismissStorageKey) || '[]');
            } catch (error) {
                return [];
            }
        }

        function setDismissedAlertKeys(keys) {
            localStorage.setItem(alertDismissStorageKey, JSON.stringify([...new Set(keys)]));
        }

        function dismissAlert(button) {
            const card = button?.closest('.chat-alert-card');
            const key = card?.dataset.alertKey;
            if (!card || !key) return;

            const dismissed = getDismissedAlertKeys();
            dismissed.push(key);
            setDismissedAlertKeys(dismissed);
            card.remove();
            syncAlertBarVisibility();
        }

        function syncDismissedAlerts() {
            const dismissed = new Set(getDismissedAlertKeys());
            document.querySelectorAll('.chat-alert-card').forEach(card => {
                card.classList.toggle('is-hidden', dismissed.has(card.dataset.alertKey));
                if (dismissed.has(card.dataset.alertKey)) {
                    card.remove();
                }
            });
            syncAlertBarVisibility();
        }

        function syncAlertBarVisibility() {
            const bar = document.getElementById('chatAlertsBar');
            if (!bar) return;
            const visibleCards = bar.querySelectorAll('.chat-alert-card').length;
            bar.style.display = visibleCards > 0 ? 'flex' : 'none';
        }

        // ── Criar ou obter conversa ───────────────────────────────
        async function ensureConversation() {
            if (activeConvId) return activeConvId;
            const res = await fetch('{{ route('chat.create') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Content-Type': 'application/json'
                },
            });
            const data = await res.json();
            activeConvId = data.id;
            ensureConversationSidebarItem(data);
            updateConversationContextBar(data);
            setActiveConversationItem(activeConvId);
            refreshConversationFilters();
            return activeConvId;
        }

        // ── Enviar mensagem ───────────────────────────────────────
        let isSubmitting = false;

        async function sendMessage() {
            if (isSubmitting) return; // Guard contra duplo disparo
            const input = document.getElementById('msg-input');
            const content = input.value.trim();
            if (!content) return;
            if (isListening) {
                recognition?.stop();
            }
            isSubmitting = true;
            updateAudioControls();
            const requestInputType = pendingInputType;
            const requestVoiceTranscript = pendingInputType === 'voice' ? (pendingVoiceTranscript || content) : null;

            // Esconder sugestões
            document.getElementById('chatEmpty')?.remove();

            // Adicionar bolha do usuário
            appendMessage('user', content, [], null, null, null, null, requestInputType);
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
                        message: content,
                        input_type: requestInputType,
                        voice_transcript: requestVoiceTranscript,
                    }),
                });

                const data = await res.json();
                typingEl.remove();

                // Suporta tanto {success, content} quanto resposta direta {message}
                const responseText = data.content || data.message || null;
                if (data.success || responseText) {
                    rememberConversationMessage(data.message_id || null, 'assistant', responseText || '');
                    appendMessage('assistant', responseText, data.sources || [], data.message_id || null, data
                        .export_suggestion || null, data.memory || null, null, 'text');
                    setActiveChatPanel('messages');
                    if (data.conversation) {
                        updateConversationSidebarItem(data.conversation, 2);
                        updateConversationContextBar(data.conversation);
                        setActiveConversationItem(data.conversation.id);
                        refreshConversationFilters();
                    }
                } else {
                    appendError(data.error || data.message || 'Ocorreu um erro. Tente novamente.');
                }
            } catch (err) {
                typingEl.remove();
                appendError('Não foi possível conectar ao servidor. Verifique sua conexão.');
            } finally {
                isSubmitting = false;
                resetPendingVoicePayload();
                document.getElementById('btnSend').disabled = document.getElementById('msg-input').value.trim()
                    .length === 0;
                updateAudioControls();
            }
        }

        // ── Adicionar bolha ───────────────────────────────────────
        function appendMessage(role, content, sources = [], msgId = null, exportSuggestion = null, memoryMeta = null,
            shareState = null, inputType = 'text') {
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

            const audioPlayerHtml = role === 'assistant' && msgId ?
                `<button class="audio-play-btn" type="button" data-audio-play-button
                    data-audio-content="${escapeHtml(content)}"
                    onclick="toggleMessageAudioPlayback(${msgId}, this.dataset.audioContent)">
                    Ouvir
                </button>` :
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

            const shareHtml = msgId ?
                `<button class="share-btn ${Number(shareState?.active_count || 0) > 0 ? 'is-shared' : ''}"
                    data-message-id="${msgId}"
                    data-role="${escapeHtml(role)}"
                    data-content="${escapeHtml(content)}"
                    onclick="openShareModal(this)">
                    ${renderShareButtonLabel(shareState)}
                </button>` :
                '';

            const exportHtml = role === 'assistant' && msgId && exportSuggestion ?
                renderExportSuggestion(msgId, exportSuggestion) :
                '';

            const memoryHtml = role === 'assistant' && Number(memoryMeta?.active_count || 0) > 0 ?
                `<div class="message-signals">
                    <span class="message-signal memory">Memoria ativa: ${Number(memoryMeta.active_count)}</span>
                </div>` :
                '';

            const voiceHtml = role === 'user' && inputType === 'voice' ?
                `<div class="message-signals">
                    <span class="message-signal voice">Mensagem enviada por voz</span>
                </div>` :
                '';

            const assistantSignalsHtml = role === 'assistant' ?
                `<div class="message-signals" data-audio-status-wrap></div>` :
                '';

            el.innerHTML = `
            <div class="message-avatar">${avatarContent}</div>
            <div>
                <div class="message-bubble">
                    ${escapeHtml(content).replace(/\n/g, '<br>')}
                    ${voiceHtml}
                    ${sourcesHtml}
                    ${memoryHtml}
                    ${assistantSignalsHtml}
                    ${exportHtml}
                </div>
                <div class="message-meta">
                    <span>${new Date().toLocaleTimeString('pt-BR', {hour:'2-digit', minute:'2-digit'})}</span>
                    <div class="message-actions">
                        ${shareHtml}
                        ${audioPlayerHtml}
                        ${feedbackHtml}
                    </div>
                </div>
                ${renderMessageShareMeta(shareState)}
            </div>`;

            if (role === 'assistant' && msgId) {
                el.dataset.audioMessageId = String(msgId);
                el.dataset.audioContent = content;
            }

            area.appendChild(el);
            area.scrollTop = area.scrollHeight;
            if (role === 'assistant') {
                if (msgId) {
                    setLastAssistantAudioMessage(msgId, content);
                }
                renderAssistantAudioState(msgId, 'idle');
                updateReplayButtonState();
                speakAssistantResponse(content, msgId, false);
            }
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
                refreshConversationFilters();
                if (activeConvId === id) {
                    activeConvId = null;
                    clearConversationRuntimeState();
                    updateConversationContextBar(null);
                    setActiveConversationItem(null);
                    setActiveChatPanel('messages');
                    const messagesArea = document.getElementById('messagesArea');
                    if (messagesArea) {
                        messagesArea.innerHTML = renderEmptyState(
                            'Conversa excluida',
                            'Crie uma nova conversa para continuar recebendo orientacoes do Meu Assistente.',
                            false
                        );
                    }
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

        function getShareState(messageId) {
            if (!messageId) return null;
            return shareStatesByMessage[String(messageId)] || null;
        }

        function setShareState(messageId, state) {
            if (!messageId) return;
            if (state) {
                shareStatesByMessage[String(messageId)] = state;
            } else {
                delete shareStatesByMessage[String(messageId)];
            }
            updateConversationShareSummary();
            updateConversationSidebarShareCount(activeConvId, getConversationShares().filter(share => !share.is_revoked)
                .length);
            renderConversationShares();
        }

        function formatMessageRoleLabel(role) {
            return role === 'assistant' ? 'Meu Assistente' : 'Prefeito';
        }

        function rememberConversationMessage(msgId, role, content) {
            if (!msgId) return;
            conversationMessageIndex[String(msgId)] = {
                id: msgId,
                role: role,
                content: (content || '').trim().slice(0, 240),
                created_at: new Date().toLocaleString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }),
            };
        }

        function clearConversationRuntimeState() {
            Object.keys(shareStatesByMessage).forEach(key => delete shareStatesByMessage[key]);
            Object.keys(conversationMessageIndex).forEach(key => delete conversationMessageIndex[key]);
            updateConversationShareSummary();
            renderConversationShares();
        }

        function sortSharesDesc(shares) {
            return [...shares].sort((a, b) => {
                const aTime = Date.parse(a.created_at_iso || '') || 0;
                const bTime = Date.parse(b.created_at_iso || '') || 0;

                if (aTime !== bTime) {
                    return bTime - aTime;
                }

                return Number(b.id || 0) - Number(a.id || 0);
            });
        }

        function getConversationShares() {
            return sortSharesDesc(Object.entries(shareStatesByMessage)
                .flatMap(([messageId, state]) => {
                    const messageMeta = conversationMessageIndex[String(messageId)] || {};
                    const shares = Array.isArray(state?.shares) ? state.shares : [];

                    return shares.map(share => ({
                        ...share,
                        message_id: Number(messageId),
                        message_preview: messageMeta.content || '',
                        message_created_at: messageMeta.created_at || '',
                        message_role_label: formatMessageRoleLabel(share.message_role || messageMeta
                            .role ||
                            'assistant'),
                    }));
                }));
        }

        function filterConversationShares(shares) {
            if (activeShareFilter === 'active') {
                return shares.filter(share => !share.is_revoked);
            }

            if (activeShareFilter === 'revoked') {
                return shares.filter(share => share.is_revoked);
            }

            return shares;
        }

        function buildGlobalConversationOptions() {
            const conversations = sortSharesDesc(globalShareFeed)
                .reduce((acc, share) => {
                    const key = String(share.conversation_id || 'unknown');
                    if (!share.conversation_id || acc.some(item => String(item.id) === key)) {
                        return acc;
                    }

                    acc.push({
                        id: share.conversation_id,
                        title: share.conversation_title || 'Nova conversa',
                    });

                    return acc;
                }, []);

            return conversations.sort((a, b) => a.title.localeCompare(b.title, 'pt-BR'));
        }

        function updateGlobalConversationFilterOptions() {
            const select = document.getElementById('globalShareConversationFilter');
            if (!select) return;

            const options = buildGlobalConversationOptions();
            if (activeGlobalConversationFilter !== 'all' && !options.some(option => String(option.id) === String(
                    activeGlobalConversationFilter))) {
                activeGlobalConversationFilter = 'all';
            }

            select.innerHTML = `
                <option value="all">Todas as conversas</option>
                ${options.map(option => `<option value="${escapeHtml(String(option.id))}">${escapeHtml(option.title)}</option>`).join('')}
            `;
            select.value = String(activeGlobalConversationFilter);
        }

        function getGlobalShares() {
            return sortSharesDesc(globalShareFeed);
        }

        function filterGlobalShares(shares) {
            const statusFiltered = filterConversationShares(shares);

            if (activeGlobalConversationFilter === 'all') {
                return statusFiltered;
            }

            return statusFiltered.filter(share => String(share.conversation_id) === String(activeGlobalConversationFilter));
        }

        function updateGlobalShareSummary() {
            const countEl = document.getElementById('globalSharesCount');
            if (countEl) {
                countEl.textContent = String(globalShareFeed.length);
            }
            updateGlobalConversationFilterOptions();
        }

        function syncGlobalShareFeedForMessage(messageId, shareState) {
            const normalizedMessageId = Number(messageId);
            globalShareFeed = globalShareFeed.filter(share => Number(share.message_id) !== normalizedMessageId);

            const shares = Array.isArray(shareState?.shares) ? shareState.shares : [];
            if (shares.length > 0) {
                globalShareFeed = sortSharesDesc([...shares, ...globalShareFeed]);
            }

            updateGlobalShareSummary();
            renderGlobalShares();
        }

        function updateConversationShareSummary() {
            const shares = getConversationShares();
            const countEl = document.getElementById('conversationSharesCount');
            if (countEl) {
                countEl.textContent = String(shares.length);
            }
        }

        function renderShareCardContent(share) {
            const statusLabel = share.is_revoked ? 'Revogado' : 'Ativo';
            const statusClass = share.is_revoked ? 'share-feed-status revoked' : 'share-feed-status';
            const viewedLabel = share.viewed_at ? `Visualizado em ${escapeHtml(share.viewed_at)}` : 'Ainda não visualizado';
            const revokedLabel = share.is_revoked ?
                `Revogado em ${escapeHtml(share.revoked_at || '')}${share.revoked_by_name ? ` por ${escapeHtml(share.revoked_by_name)}` : ''}` :
                '';
            const actions = [];

            if (!share.is_revoked && share.share_url) {
                actions.push(`<a class="share-feed-link" href="${escapeHtml(share.share_url)}">Abrir compartilhamento</a>`);
            }

            if (share.can_revoke) {
                actions.push(
                    `<button type="button" class="share-feed-revoke" onclick="revokeShare(${share.id}, ${share.message_id}, this)">Revogar acesso</button>`
                );
            }

            return `
                <div class="share-feed-top">
                    <div>
                        <div class="share-feed-title">${escapeHtml(share.recipient_name)}</div>
                        <div class="share-feed-meta">
                            ${escapeHtml(share.recipient_role || 'Usuario')} • ${escapeHtml(share.message_role_label)} • compartilhado em ${escapeHtml(share.created_at || 'agora')}
                        </div>
                    </div>
                    <span class="${statusClass}">${statusLabel}</span>
                </div>
                <div class="share-feed-excerpt">
                    <span class="share-feed-label">Trecho</span>
                    ${escapeHtml(share.excerpt || share.message_preview || 'Trecho compartilhado')}
                </div>
                ${share.note ? `
                                                                                                                                                                                                                                                                                                <div class="share-feed-note">
                                                                                                                                                                                                                                                                                                    <span class="share-feed-label">Observacao</span>
                                                                                                                                                                                                                                                                                                    ${escapeHtml(share.note)}
                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                            ` : ''}
                ${share.context_excerpt ? `
                                                                                                                                                                                                                                                                                                <div class="share-feed-context">
                                                                                                                                                                                                                                                                                                    <span class="share-feed-label">Contexto da conversa</span>
                                                                                                                                                                                                                                                                                                    ${escapeHtml(share.context_excerpt)}
                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                            ` : ''}
                <div class="share-feed-meta" style="margin-top:.65rem">
                    ${share.is_revoked ? revokedLabel : viewedLabel}
                </div>
                ${share.message_created_at ? `<div class="share-feed-meta">Mensagem original em ${escapeHtml(share.message_created_at)}</div>` : ''}
                ${actions.length > 0 ? `<div class="share-feed-actions">${actions.join('')}</div>` : ''}
            `;
        }

        function renderConversationShareItem(share) {
            return `<div class="share-feed-item">${renderShareCardContent(share)}</div>`;
        }

        function renderGlobalShareItem(share) {
            const conversationUrl = share.conversation_id ? `/mayor/chat/${share.conversation_id}` : null;

            return `
                <div class="share-feed-item">
                    <div class="share-feed-conversation">
                        <span>${escapeHtml(share.conversation_title || 'Nova conversa')}</span>
                        ${share.conversation_origin ? `<span>• ${escapeHtml(formatOriginLabel(share.conversation_origin))}</span>` : ''}
                    </div>
                    ${renderShareCardContent(share)}
                    ${conversationUrl ? `
                                                                                                                                                                                                                                                                                                        <div class="share-feed-actions" style="margin-top:.45rem">
                                                                                                                                                                                                                                                                                                            <a class="share-feed-link" href="${escapeHtml(conversationUrl)}">Abrir conversa</a>
                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                    ` : ''}
                </div>
            `;
        }

        function renderConversationShares() {
            const list = document.getElementById('conversationSharesList');
            const empty = document.getElementById('conversationSharesEmpty');
            if (!list || !empty) return;

            const allShares = getConversationShares();
            const filteredShares = filterConversationShares(allShares);

            if (filteredShares.length === 0) {
                list.innerHTML = '';
                empty.style.display = 'block';
                empty.textContent = allShares.length === 0 ?
                    'Os compartilhamentos desta conversa aparecerao aqui para consulta e revogacao segura.' :
                    'Nenhum compartilhamento corresponde ao filtro selecionado.';
                return;
            }

            empty.style.display = 'none';
            list.innerHTML = filteredShares.map(renderConversationShareItem).join('');
        }

        function renderGlobalShares() {
            const list = document.getElementById('globalSharesList');
            const empty = document.getElementById('globalSharesEmpty');
            if (!list || !empty) return;

            const allShares = getGlobalShares();
            const filteredShares = filterGlobalShares(allShares);

            if (filteredShares.length === 0) {
                list.innerHTML = '';
                empty.style.display = 'block';
                empty.textContent = allShares.length === 0 ?
                    'Os compartilhamentos de todas as conversas aparecerao aqui para consulta e revogacao segura.' :
                    'Nenhum compartilhamento corresponde aos filtros selecionados.';
                return;
            }

            empty.style.display = 'none';
            list.innerHTML = filteredShares.map(renderGlobalShareItem).join('');
        }

        function setActiveChatPanel(panel) {
            activeChatPanel = ['shares', 'global-shares'].includes(panel) ? panel : 'messages';

            document.querySelectorAll('[data-chat-panel-trigger]').forEach(button => {
                button.classList.toggle('is-active', button.dataset.chatPanelTrigger === activeChatPanel);
            });

            document.querySelectorAll('[data-chat-panel-content]').forEach(section => {
                section.classList.toggle('is-active', section.dataset.chatPanelContent === activeChatPanel);
            });

            const filterBar = document.getElementById('shareStatusFilters');
            if (filterBar) {
                filterBar.classList.toggle('is-hidden', !['shares', 'global-shares'].includes(activeChatPanel));
            }

            const globalFilterBar = document.getElementById('globalShareFilters');
            if (globalFilterBar) {
                globalFilterBar.classList.toggle('is-hidden', activeChatPanel !== 'global-shares');
            }

            if (activeChatPanel === 'shares') {
                renderConversationShares();
            }

            if (activeChatPanel === 'global-shares') {
                renderGlobalShares();
            }
        }

        function renderShareButtonLabel(shareState) {
            const activeCount = Number(shareState?.active_count || 0);
            return activeCount > 0 ? `Compartilhado (${activeCount})` : 'Compartilhar';
        }

        function buildActiveRecipientsSummary(shareState) {
            const recipients = Array.isArray(shareState?.active_recipients) ? shareState.active_recipients : [];
            if (recipients.length === 0) return '';

            const visible = recipients.slice(0, 2);
            const remaining = recipients.length - visible.length;

            return `Compartilhado com ${visible.join(', ')}${remaining > 0 ? ` +${remaining}` : ''}`;
        }

        function renderMessageShareMeta(shareState) {
            const summary = buildActiveRecipientsSummary(shareState);
            return summary ? `<div class="message-share-meta" data-message-share-meta>${escapeHtml(summary)}</div>` : '';
        }

        function renderShareHistory(messageId) {
            const historyList = document.getElementById('shareHistoryList');
            if (!historyList) return;

            const shareState = getShareState(messageId);
            const shares = Array.isArray(shareState?.shares) ? shareState.shares : [];

            if (shares.length === 0) {
                historyList.innerHTML =
                    '<div class="share-history-empty">Nenhum compartilhamento feito ainda para esta mensagem.</div>';
                return;
            }

            historyList.innerHTML = shares.map(share => {
                const statusLabel = share.is_revoked ? 'Revogado' : 'Ativo';
                const statusClass = share.is_revoked ? 'share-history-status revoked' : 'share-history-status';
                const createdAt = share.created_at ? `Compartilhado em ${escapeHtml(share.created_at)}` :
                    'Compartilhamento recente';
                const viewedAt = share.viewed_at ? `Visualizado em ${escapeHtml(share.viewed_at)}` :
                    'Ainda não visualizado';
                const revokedAt = share.is_revoked && share.revoked_at ?
                    `Revogado em ${escapeHtml(share.revoked_at)}${share.revoked_by_name ? ` por ${escapeHtml(share.revoked_by_name)}` : ''}` :
                    '';
                const actions = [];

                if (!share.is_revoked && share.share_url) {
                    actions.push(
                        `<a class="share-history-link" href="${escapeHtml(share.share_url)}">Abrir compartilhamento</a>`
                    );
                }

                if (share.can_revoke) {
                    actions.push(
                        `<button type="button" class="share-history-revoke" onclick="revokeShare(${share.id}, ${messageId}, this)">Revogar acesso</button>`
                    );
                }

                return `
                    <div class="share-history-item">
                        <div class="share-history-top">
                            <div>
                                <div class="share-history-recipient">${escapeHtml(share.recipient_name)}</div>
                                <div class="share-history-meta">${escapeHtml(share.recipient_role || 'Usuario')}</div>
                            </div>
                            <span class="${statusClass}">${statusLabel}</span>
                        </div>
                        <div class="share-history-meta">${createdAt}</div>
                        <div class="share-history-meta">${share.is_revoked ? revokedAt : viewedAt}</div>
                        ${actions.length > 0 ? `<div class="share-history-actions">${actions.join('')}</div>` : ''}
                    </div>
                `;
            }).join('');
        }

        function refreshMessageShareUI(messageId) {
            const message = document.querySelector(`.message[data-id="${messageId}"]`);
            if (!message) return;

            const shareState = getShareState(messageId);
            const shareButton = message.querySelector('.share-btn');

            if (shareButton) {
                shareButton.textContent = renderShareButtonLabel(shareState);
                shareButton.classList.toggle('is-shared', Number(shareState?.active_count || 0) > 0);
            }

            let shareMeta = message.querySelector('[data-message-share-meta]');
            const summary = buildActiveRecipientsSummary(shareState);

            if (summary) {
                if (!shareMeta) {
                    shareMeta = document.createElement('div');
                    shareMeta.className = 'message-share-meta';
                    shareMeta.dataset.messageShareMeta = 'true';
                    message.querySelector('.message-meta')?.insertAdjacentElement('afterend', shareMeta);
                }
                shareMeta.textContent = summary;
            } else if (shareMeta) {
                shareMeta.remove();
            }
        }

        function getSelectedSnippetForMessage(button) {
            const selection = window.getSelection();
            const message = button?.closest('.message');
            const selectedText = selection ? selection.toString().trim() : '';

            if (message && selectedText && selection?.rangeCount) {
                const range = selection.getRangeAt(0);
                if (message.contains(range.commonAncestorContainer)) {
                    return selectedText;
                }
            }

            return button?.dataset.content || '';
        }

        function openShareModal(button) {
            if (!button) {
                return;
            }

            const modal = document.getElementById('shareModal');
            const excerpt = getSelectedSnippetForMessage(button);
            const messageId = button.dataset.messageId || '';

            document.getElementById('shareMessageId').value = messageId;
            document.getElementById('shareExcerpt').value = excerpt;
            document.getElementById('shareNote').value = '';
            document.getElementById('shareRecipient').value = '';
            document.getElementById('shareModalStatus').textContent = shareRecipients.length === 0 ?
                'Nenhum destinatario elegivel foi encontrado para este compartilhamento.' :
                '';
            renderShareHistory(messageId);
            modal.classList.add('is-open');
        }

        function closeShareModal() {
            document.getElementById('shareModal')?.classList.remove('is-open');
        }

        async function submitShare() {
            const messageId = document.getElementById('shareMessageId')?.value;
            const recipientUserId = document.getElementById('shareRecipient')?.value;
            const excerpt = document.getElementById('shareExcerpt')?.value.trim();
            const note = document.getElementById('shareNote')?.value.trim();
            const submitButton = document.getElementById('shareModalSubmit');
            const status = document.getElementById('shareModalStatus');

            if (!messageId || !recipientUserId || !excerpt) {
                status.textContent = 'Escolha um destinatario e defina o trecho que sera compartilhado.';
                return;
            }

            const originalLabel = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Compartilhando...';
            status.textContent = '';

            try {
                const res = await fetch(`/mayor/chat/messages/${messageId}/share`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        recipient_user_id: recipientUserId,
                        excerpt,
                        note,
                    }),
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.error || 'Nao foi possivel compartilhar este trecho.');
                }

                if (data.message_share_state) {
                    setShareState(messageId, data.message_share_state);
                    syncGlobalShareFeedForMessage(messageId, data.message_share_state);
                    refreshMessageShareUI(messageId);
                    renderShareHistory(messageId);
                }

                status.innerHTML =
                    `Trecho compartilhado com ${escapeHtml(data.recipient_name)}. <a class="export-link" href="${escapeHtml(data.share_url)}">Abrir compartilhamento</a>`;
                submitButton.textContent = 'Compartilhado';
                setTimeout(() => {
                    closeShareModal();
                    submitButton.disabled = false;
                    submitButton.textContent = originalLabel;
                }, 900);
            } catch (error) {
                submitButton.disabled = false;
                submitButton.textContent = originalLabel;
                status.textContent = error.message || 'Nao foi possivel compartilhar este trecho.';
            }
        }

        async function revokeShare(shareId, messageId, button) {
            if (!shareId || !messageId) return;
            if (!confirm('Revogar o acesso deste compartilhamento?')) return;

            const originalLabel = button?.textContent || 'Revogar acesso';
            if (button) {
                button.disabled = true;
                button.textContent = 'Revogando...';
            }

            try {
                const res = await fetch(`/mayor/chat/shares/${shareId}/revoke`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.error || 'Nao foi possivel revogar este compartilhamento.');
                }

                if (data.message_share_state) {
                    setShareState(messageId, data.message_share_state);
                    syncGlobalShareFeedForMessage(messageId, data.message_share_state);
                    refreshMessageShareUI(messageId);
                    renderShareHistory(messageId);
                }

                const status = document.getElementById('shareModalStatus');
                if (status) {
                    status.textContent = 'Compartilhamento revogado com seguranca.';
                }
            } catch (error) {
                if (button) {
                    button.disabled = false;
                    button.textContent = originalLabel;
                }
                appendError(error.message || 'Nao foi possivel revogar este compartilhamento.');
            }
        }

        function renderExportSuggestion(msgId, suggestion, savedContent = null) {
            const confidence = escapeHtml(formatTagLabel(suggestion.confidence || 'media'));
            const reason = escapeHtml(suggestion.reason || 'Essa resposta pode virar um conteudo aproveitavel.');
            const label = escapeHtml(suggestion.label || 'Salvar como conteudo');

            if (savedContent) {
                return `
                    <div class="export-suggestion" data-export-message-id="${msgId}">
                        <div class="export-suggestion-header">
                            <span class="export-suggestion-title">Conteudo salvo</span>
                            <span class="export-suggestion-confidence">${confidence}</span>
                        </div>
                        <p>${escapeHtml(savedContent.title || 'Conteudo salvo com sucesso no módulo de comunicação.')}</p>
                        <div class="export-suggestion-actions">
                            <a class="export-link" href="${escapeHtml(savedContent.redirect_url || '{{ route('mayor.content.index') }}')}">Abrir módulo de conteudos</a>
                            <span class="export-status">Salvo como rascunho.</span>
                        </div>
                    </div>
                `;
            }

            return `
                <div class="export-suggestion" data-export-message-id="${msgId}">
                    <div class="export-suggestion-header">
                        <span class="export-suggestion-title">Sugestao de exportacao</span>
                        <span class="export-suggestion-confidence">${confidence}</span>
                    </div>
                    <p>${reason}</p>
                    <div class="export-suggestion-actions">
                        <button class="export-btn"
                            data-type="${escapeHtml(suggestion.type || '')}"
                            data-label="${label}"
                            data-reason="${reason}"
                            data-confidence="${confidence}"
                            onclick="exportMessage(${msgId}, this)">${label}</button>
                    </div>
                </div>
            `;
        }

        function extractSuggestion(button) {
            return {
                type: button?.dataset.type || '',
                label: button?.dataset.label || 'Salvar como conteudo',
                reason: button?.dataset.reason || '',
                confidence: button?.dataset.confidence || 'media',
            };
        }

        async function exportMessage(msgId, buttonEl) {
            const container = document.querySelector(`.export-suggestion[data-export-message-id="${msgId}"]`);
            const button = buttonEl || container?.querySelector('.export-btn');
            const suggestion = extractSuggestion(button);
            if (button) {
                button.disabled = true;
                button.textContent = 'Salvando...';
            }

            try {
                const res = await fetch(`/mayor/chat/messages/${msgId}/export`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        type: suggestion.type
                    }),
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.error || 'Nao foi possivel salvar este conteudo.');
                }

                if (container) {
                    container.outerHTML = renderExportSuggestion(msgId, suggestion, data);
                }
            } catch (error) {
                if (button) {
                    button.disabled = false;
                    button.textContent = suggestion.label || 'Salvar como conteudo';
                }
                appendError(error.message || 'Nao foi possivel exportar esta resposta.');
            }
        }

        // ── Nova conversa ─────────────────────────────────────────
        document.getElementById('btnNewConv').addEventListener('click', () => {
            activeConvId = null;
            clearConversationRuntimeState();
            updateConversationContextBar(null);
            setActiveConversationItem(null);
            setActiveChatPanel('messages');
            const messagesArea = document.getElementById('messagesArea');
            if (messagesArea) {
                messagesArea.innerHTML = renderEmptyState(
                    'Nova conversa',
                    'Me diga o que você quer destravar agora: comunicação, risco politico, demanda da cidade ou oportunidade de recurso.',
                    true
                );
            }
            document.getElementById('msg-input').focus();
        });

        function getDefaultSuggestionsHtml() {
            return `
                <div class="suggestions" id="suggestions">
                    <button class="suggestion-chip" onclick="fillInput(this)">Qual e a situacao do nosso FUNDEB este ano?</button>
                    <button class="suggestion-chip" onclick="fillInput(this)">Existe algum programa federal aberto para pavimentacao?</button>
                    <button class="suggestion-chip" onclick="fillInput(this)">Crie um post para o Instagram sobre uma obra entregue</button>
                    <button class="suggestion-chip" onclick="fillInput(this)">Quais compromissos do mandato estao em risco?</button>
                    <button class="suggestion-chip" onclick="fillInput(this)">Me prepare para uma entrevista sobre saude publica</button>
                    <button class="suggestion-chip" onclick="fillInput(this)">Resumo da situacao fiscal do município</button>
                </div>
            `;
        }

        function renderEmptyState(title, description, includeSuggestions = true) {
            return `
                <div class="chat-empty" id="chatEmpty">
                    <div class="chat-empty-icon">
                        <img width="100%" src="/images/icone-robo-redondo.png" alt="">
                    </div>
                    <div class="chat-empty-kicker">Meu Assistente</div>
                    <h2>${escapeHtml(title)}</h2>
                    <p>${escapeHtml(description)}</p>
                    ${includeSuggestions ? getDefaultSuggestionsHtml() : ''}
                </div>
            `;
        }

        function formatTagLabel(tag) {
            return (tag || '')
                .replace(/_/g, ' ')
                .replace(/\b\w/g, letter => letter.toUpperCase());
        }

        function normalizeText(text) {
            return (text || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        }

        function formatOriginLabel(origin) {
            return originLabels[origin] || formatTagLabel(origin || 'chat');
        }

        function formatIntentLabel(intent) {
            return intentLabels[intent] || formatTagLabel(intent || '');
        }

        function parseManualTags(rawValue) {
            return [...new Set((rawValue || '')
                .split(',')
                .map(tag => normalizeText(tag).replace(/\s+/g, '_'))
                .map(tag => tag.replace(/[^a-z0-9_]/g, '').replace(/^_+|_+$/g, ''))
                .filter(Boolean)
                .slice(0, 8))];
        }

        function formatTagInputValue(tags) {
            return (tags || []).map(tag => formatTagLabel(tag)).join(', ');
        }

        function setTagEditorOpen(isOpen) {
            const editor = document.getElementById('chatTagEditor');
            if (!editor) return;

            editor.classList.toggle('is-hidden', !isOpen);

            if (isOpen) {
                document.getElementById('chatTagEditorInput')?.focus();
            }
        }

        function syncConversationTagEditor(conversation) {
            const editButton = document.getElementById('chatEditTagsButton');
            const input = document.getElementById('chatTagEditorInput');
            const note = document.getElementById('chatContextTagNote');

            if (editButton) {
                editButton.disabled = !conversation?.id;
            }

            if (!input || !note) return;

            input.value = formatTagInputValue(conversation?.tags || []);
            note.textContent = conversation?.has_manual_tags ?
                'Tags manuais ativas' :
                'Tags automaticas da conversa';
        }

        async function saveConversationTags() {
            if (!activeConvId) {
                appendError('Crie ou abra uma conversa antes de editar as tags.');
                return;
            }

            const saveButton = document.getElementById('chatTagEditorSave');
            const input = document.getElementById('chatTagEditorInput');
            if (!saveButton || !input) return;

            const originalLabel = saveButton.textContent;
            saveButton.disabled = true;
            saveButton.textContent = 'Salvando...';

            try {
                const tags = parseManualTags(input.value);
                const res = await fetch(`/mayor/chat/${activeConvId}/tags`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        tags,
                    }),
                });

                const data = await res.json();

                if (!res.ok || !data.success || !data.conversation) {
                    throw new Error(data.message || data.error || 'Nao foi possivel atualizar as tags agora.');
                }

                updateConversationContextBar(data.conversation);
                updateConversationSidebarItem(data.conversation, 0);
                setActiveConversationItem(data.conversation.id);
                refreshConversationFilters();
                setTagEditorOpen(false);
            } catch (error) {
                appendError(error.message || 'Nao foi possivel atualizar as tags.');
            } finally {
                saveButton.disabled = false;
                saveButton.textContent = originalLabel;
            }
        }

        function renderConversationBadges(conversation) {
            const badges = [];

            if (conversation.origin_module) {
                badges.push(
                    `<span class="chat-context-origin">${escapeHtml(formatOriginLabel(conversation.origin_module))}</span>`
                );
            }

            if (conversation.intent) {
                badges.push(
                    `<span class="chat-context-intent">${escapeHtml(formatIntentLabel(conversation.intent))}</span>`);
            }

            return badges.join('');
        }

        function renderConversationTags(tags, className) {
            return (tags || [])
                .slice(0, 4)
                .map(tag => `<span class="${className}">${escapeHtml(formatTagLabel(tag))}</span>`)
                .join('');
        }

        function updateConversationContextBar(conversation) {
            const bar = document.getElementById('chatContextBar');
            const title = document.getElementById('chatContextTitle');
            const badges = document.getElementById('chatContextBadges');
            const tags = document.getElementById('chatContextTags');

            if (!conversation) {
                bar.classList.add('is-hidden');
                title.textContent = 'Nova conversa';
                badges.innerHTML = '';
                tags.innerHTML = '';
                syncConversationTagEditor(null);
                setTagEditorOpen(false);
                return;
            }

            bar.classList.remove('is-hidden');
            title.textContent = conversation.title || 'Nova conversa';
            badges.innerHTML = renderConversationBadges(conversation);
            tags.innerHTML = renderConversationTags(conversation.tags || [], 'chat-context-tag');
            syncConversationTagEditor(conversation);
        }

        function setActiveConversationItem(id) {
            document.querySelectorAll('.conv-item').forEach(item => {
                item.classList.toggle('active', Number(item.dataset.id) === Number(id));
            });
        }

        function renderConversationShareIndicator(activeShareCount) {
            const count = Number(activeShareCount || 0);
            if (count <= 0) return '';

            return `<div class="conv-item-share-indicator">Compartilhamentos ativos: ${count}</div>`;
        }

        function buildConversationItemMarkup(conversation, messageCount = 0, activeShareCount = 0) {
            const title = escapeHtml(conversation.title || 'Nova conversa');
            const origin = conversation.origin_module ?
                `<span class="conv-item-origin">${escapeHtml(formatOriginLabel(conversation.origin_module))}</span>` :
                '';
            const tags = renderConversationTags(conversation.tags || [], 'conv-tag');

            return `
                <div class="conv-item-top">
                    <div class="conv-item-title">${title}</div>
                    ${origin}
                </div>
                <div class="conv-item-tags">${tags}</div>
                ${renderConversationShareIndicator(activeShareCount)}
                <div class="conv-item-meta">
                    <span>${messageCount} msgs</span>
                    <span style="display:flex;align-items:center;gap:.4rem">
                        agora
                        <button type="button" title="Excluir conversa"
                            onclick="deleteConv(event, ${conversation.id})"
                            style="border:none;background:none;cursor:pointer;color:#dc2626;padding:0">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
                                <path d="M16 9v10H8V9h8m-1.5-6h-5l-1 1H5v2h14V4h-4.5l-1-1z" />
                            </svg>
                        </button>
                    </span>
                </div>
            `;
        }

        function ensureConversationSidebarItem(conversation) {
            const list = document.querySelector('.conv-scroll');
            let item = document.querySelector(`.conv-item[data-id="${conversation.id}"]`);

            if (!item) {
                const emptyState = document.getElementById('convListEmpty');
                if (emptyState) emptyState.remove();

                item = document.createElement('a');
                item.href = `/mayor/chat/${conversation.id}`;
                item.className = 'conv-item';
                item.dataset.id = conversation.id;
                item.dataset.messageCount = '0';
                item.dataset.activeShares = String(Number(conversation.active_shares_count || 0));
                item.dataset.lastMessageAt = conversation.last_message_at_iso || '';
                item.dataset.hasManualTags = conversation.has_manual_tags ? '1' : '0';
                item.innerHTML = buildConversationItemMarkup(conversation, 0, Number(conversation.active_shares_count ||
                    0));
                list.prepend(item);
            }

            return item;
        }

        function updateConversationSidebarItem(conversation, increment = 0) {
            const item = ensureConversationSidebarItem(conversation);
            const currentCount = parseInt(item.dataset.messageCount || '0', 10) || 0;
            const currentActiveShares = parseInt(item.dataset.activeShares || '0', 10) || 0;
            const nextCount = Math.max(currentCount + increment, 0);
            const nextActiveShares = Number.isFinite(Number(conversation.active_shares_count)) ?
                Math.max(Number(conversation.active_shares_count), 0) :
                currentActiveShares;
            item.dataset.messageCount = String(nextCount);
            item.dataset.activeShares = String(nextActiveShares);
            item.dataset.tags = (conversation.tags || []).join(',');
            item.dataset.origin = conversation.origin_module || '';
            item.dataset.lastMessageAt = conversation.last_message_at_iso || item.dataset.lastMessageAt || '';
            item.dataset.hasManualTags = conversation.has_manual_tags ? '1' : '0';
            item.dataset.search = normalizeText([
                conversation.title || 'Nova conversa',
                ...(conversation.tags || []),
                conversation.origin_module || '',
                formatOriginLabel(conversation.origin_module || 'chat'),
            ].join(' '));
            item.innerHTML = buildConversationItemMarkup(conversation, nextCount, nextActiveShares);
        }

        function updateConversationSidebarShareCount(conversationId, activeShareCount) {
            if (!conversationId) return;

            const item = document.querySelector(`.conv-item[data-id="${conversationId}"]`);
            if (!item) return;

            const title = item.querySelector('.conv-item-title')?.textContent?.trim() || 'Nova conversa';
            const tags = (item.dataset.tags || '').split(',').map(tag => tag.trim()).filter(Boolean);
            const originModule = item.dataset.origin || '';
            const messageCount = parseInt(item.dataset.messageCount || '0', 10) || 0;
            const normalizedActiveShareCount = Math.max(parseInt(activeShareCount || '0', 10) || 0, 0);

            item.dataset.activeShares = String(normalizedActiveShareCount);
            item.innerHTML = buildConversationItemMarkup({
                id: conversationId,
                title,
                tags,
                origin_module: originModule,
            }, messageCount, normalizedActiveShareCount);
            applyConversationFilters();
        }

        function updateConversationShareSidebarSummary() {
            const items = [...document.querySelectorAll('.conv-item')];
            const activeShareItems = items.filter(item => (parseInt(item.dataset.activeShares || '0', 10) || 0) > 0);
            const visibleActiveShareItems = activeShareItems.filter(item => !item.classList.contains('is-hidden'));

            const summaryValue = document.getElementById('convShareSummaryValue');
            const summaryHelp = document.getElementById('convShareSummaryHelp');
            if (!summaryValue || !summaryHelp) return;

            const totalCount = activeShareItems.length;
            const visibleCount = visibleActiveShareItems.length;
            summaryValue.textContent = `${totalCount} conversas com compartilhamentos ativos`;

            if (activeConversationShareFilter === 'active-only') {
                summaryHelp.textContent = `${visibleCount} conversas visiveis com o filtro atual.`;
                return;
            }

            summaryHelp.textContent = totalCount > 0 ?
                'Use o filtro para focar apenas nas conversas com trechos ainda ativos.' :
                'Ainda não ha conversas com compartilhamentos ativos.';
        }

        function rebuildConversationFilterChips() {
            const container = document.getElementById('convFilterTags');
            const tags = [...document.querySelectorAll('.conv-item')]
                .flatMap(item => (item.dataset.tags || '').split(','))
                .map(tag => tag.trim())
                .filter(Boolean);

            const uniqueTags = [...new Set(tags)].sort((a, b) => a.localeCompare(b, 'pt-BR'));

            if (activeTagFilter !== 'all' && !uniqueTags.includes(activeTagFilter)) {
                activeTagFilter = 'all';
            }

            container.innerHTML = `
                <button type="button" class="conv-filter-chip ${activeTagFilter === 'all' ? 'active' : ''}" data-tag="all">
                    Todas
                </button>
                ${uniqueTags.map(tag => `
                                                                                                                                                                                                                                                                                                                                                                                                                                    <button type="button" class="conv-filter-chip ${activeTagFilter === tag ? 'active' : ''}" data-tag="${escapeHtml(tag)}">
                                                                                                                                                                                                                                                                                                                                                                                                                                        ${escapeHtml(formatTagLabel(tag))}
                                                                                                                                                                                                                                                                                                                                                                                                                                    </button>
                                                                                                                                                                                                                                                                                                                                                                                                                                `).join('')}
            `;
        }

        function applyConversationFilters() {
            const searchTerm = normalizeText(document.getElementById('convSearchInput')?.value || '');
            const items = [...document.querySelectorAll('.conv-item')];
            let visibleCount = 0;

            items.forEach(item => {
                const itemTags = (item.dataset.tags || '').split(',').map(tag => tag.trim()).filter(Boolean);
                const itemSearch = normalizeText(item.dataset.search || item.textContent);
                const activeShares = parseInt(item.dataset.activeShares || '0', 10) || 0;
                const lastMessageAt = item.dataset.lastMessageAt ? new Date(item.dataset.lastMessageAt) : null;
                const matchTag = activeTagFilter === 'all' || itemTags.includes(activeTagFilter);
                const matchShare = activeConversationShareFilter === 'all' || activeShares > 0;
                const matchSearch = !searchTerm || itemSearch.includes(searchTerm);
                const matchPeriod = (() => {
                    if (activePeriodFilter === 'all') return true;
                    if (!lastMessageAt || Number.isNaN(lastMessageAt.getTime())) return false;

                    const now = new Date();

                    if (activePeriodFilter === 'today') {
                        return lastMessageAt.toDateString() === now.toDateString();
                    }

                    const thresholds = {
                        '7d': 7,
                        '30d': 30,
                        '90d': 90,
                    };

                    const days = thresholds[activePeriodFilter];
                    if (!days) return true;

                    const minDate = new Date();
                    minDate.setDate(now.getDate() - days);

                    return lastMessageAt >= minDate;
                })();
                const isVisible = matchTag && matchShare && matchSearch && matchPeriod;

                item.classList.toggle('is-hidden', !isVisible);
                if (isVisible) visibleCount++;
            });

            const filterEmpty = document.getElementById('convFilterEmpty');
            const hasConversations = items.length > 0;
            filterEmpty.classList.toggle('is-visible', hasConversations && visibleCount === 0);
            updateConversationShareSidebarSummary();
        }

        function refreshConversationFilters() {
            rebuildConversationFilterChips();
            applyConversationFilters();

            const list = document.querySelector('.conv-scroll');
            const hasConversations = document.querySelectorAll('.conv-item').length > 0;
            let emptyState = document.getElementById('convListEmpty');

            if (!hasConversations && !emptyState) {
                emptyState = document.createElement('p');
                emptyState.id = 'convListEmpty';
                emptyState.style.cssText = 'padding:1rem .85rem;font-size:.82rem;color:var(--ink-muted)';
                emptyState.textContent = 'Nenhuma conversa ainda.';
                list.prepend(emptyState);
            }

            if (hasConversations && emptyState) {
                emptyState.remove();
            }
        }

        document.getElementById('convSearchInput')?.addEventListener('input', applyConversationFilters);

        document.getElementById('convPeriodFilter')?.addEventListener('change', event => {
            activePeriodFilter = event.target.value || 'all';
            applyConversationFilters();
        });

        document.getElementById('convFilterTags')?.addEventListener('click', event => {
            const chip = event.target.closest('.conv-filter-chip');
            if (!chip) return;

            activeTagFilter = chip.dataset.tag || 'all';
            refreshConversationFilters();
        });

        document.getElementById('convShareOnlyChip')?.addEventListener('click', event => {
            const chip = event.currentTarget;
            activeConversationShareFilter = activeConversationShareFilter === 'active-only' ? 'all' : 'active-only';
            chip.classList.toggle('active', activeConversationShareFilter === 'active-only');
            applyConversationFilters();
        });

        document.getElementById('chatEditTagsButton')?.addEventListener('click', () => {
            const editor = document.getElementById('chatTagEditor');
            if (!editor || !activeConvId) return;
            setTagEditorOpen(editor.classList.contains('is-hidden'));
        });

        document.getElementById('chatTagEditorCancel')?.addEventListener('click', () => {
            const currentTags = document.getElementById('chatContextTags') ? [...document.querySelectorAll(
                '#chatContextTags .chat-context-tag')].map(tag => tag.textContent.trim()) : [];
            const input = document.getElementById('chatTagEditorInput');
            if (input) input.value = currentTags.join(', ');
            setTagEditorOpen(false);
        });

        document.getElementById('chatTagEditorSave')?.addEventListener('click', saveConversationTags);

        document.querySelectorAll('[data-chat-panel-trigger]').forEach(button => {
            button.addEventListener('click', () => {
                setActiveChatPanel(button.dataset.chatPanelTrigger || 'messages');
            });
        });

        document.getElementById('shareStatusFilters')?.addEventListener('click', event => {
            const button = event.target.closest('[data-share-filter]');
            if (!button) return;

            activeShareFilter = button.dataset.shareFilter || 'all';
            document.querySelectorAll('[data-share-filter]').forEach(filterButton => {
                filterButton.classList.toggle('is-active', filterButton.dataset.shareFilter ===
                    activeShareFilter);
            });
            if (activeChatPanel === 'global-shares') {
                renderGlobalShares();
                return;
            }

            renderConversationShares();
        });

        document.getElementById('globalShareConversationFilter')?.addEventListener('change', event => {
            activeGlobalConversationFilter = event.target.value || 'all';
            renderGlobalShares();
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
        if (area) {
            area.scrollTop = area.scrollHeight;
        }
        syncDismissedAlerts();
        refreshConversationFilters();
        updateConversationShareSummary();
        updateGlobalShareSummary();
        renderConversationShares();
        renderGlobalShares();
        syncLastAssistantAudioMessageFromDom();
        renderAllAssistantAudioStates();
        updateAudioControls();
        updateReplayButtonState();
        updateGlobalAudioBar();
    </script>
@endpush
