@extends('layouts.mayor')

@section('title', 'Trecho Compartilhado')
@section('topbar-title', 'Trecho Compartilhado')

@push('styles')
    <style>
        .shared-chat-wrap {
            max-width: 920px;
            margin: 0 auto;
            padding: 2rem 1.25rem 3rem;
        }

        .shared-chat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 1.4rem;
            box-shadow: 0 8px 28px rgba(15, 23, 42, .05);
        }

        .shared-chat-kicker {
            font-size: .72rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--ink-muted);
            margin-bottom: .6rem;
        }

        .shared-chat-title {
            font-family: 'Lora', serif;
            font-size: 1.35rem;
            color: var(--ink);
            margin-bottom: .6rem;
        }

        .shared-chat-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem 1rem;
            margin-bottom: 1.25rem;
            color: var(--ink-muted);
            font-size: .84rem;
        }

        .shared-chat-box {
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            padding: 1rem 1.1rem;
            background: var(--surface);
            margin-bottom: 1rem;
        }

        .shared-chat-label {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--ink-muted);
            margin-bottom: .45rem;
        }

        .shared-chat-text {
            color: var(--ink-soft);
            line-height: 1.75;
            white-space: pre-wrap;
        }

        .shared-chat-actions {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
            margin-top: 1.4rem;
        }

        .shared-chat-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            padding: .7rem 1rem;
            background: var(--ink);
            color: var(--white);
            text-decoration: none;
            font-weight: 600;
        }

        .shared-chat-link.secondary {
            background: var(--white);
            color: var(--ink);
            border: 1px solid var(--border);
        }
    </style>
@endpush

@section('content')
    <div class="shared-chat-wrap">
        <div class="shared-chat-card">
            <div class="shared-chat-kicker">Compartilhamento seletivo</div>
            <div class="shared-chat-title">{{ $share->conversation->title ?: 'Trecho do Meu Marqueteiro' }}</div>

            <div class="shared-chat-meta">
                <span>Compartilhado por {{ $share->owner->name }}</span>
                <span>Para {{ $share->recipient->name }}</span>
                <span>{{ $share->created_at->format('d/m/Y H:i') }}</span>
            </div>

            @if ($share->revoked_at)
                <div class="shared-chat-box">
                    <div class="shared-chat-label">Status</div>
                    <div class="shared-chat-text">
                        Compartilhamento revogado em {{ $share->revoked_at->format('d/m/Y H:i') }}
                        @if ($share->revokedBy)
                            por {{ $share->revokedBy->name }}
                        @endif
                        .
                    </div>
                </div>
            @endif

            @if ($share->note)
                <div class="shared-chat-box">
                    <div class="shared-chat-label">Observacao</div>
                    <div class="shared-chat-text">{{ $share->note }}</div>
                </div>
            @endif

            @if ($share->context_excerpt)
                <div class="shared-chat-box">
                    <div class="shared-chat-label">Contexto da conversa</div>
                    <div class="shared-chat-text">{{ $share->context_excerpt }}</div>
                </div>
            @endif

            <div class="shared-chat-box">
                <div class="shared-chat-label">Trecho compartilhado</div>
                <div class="shared-chat-text">{{ $share->excerpt }}</div>
            </div>

            <div class="shared-chat-actions">
                <a href="{{ route('chat.show', $share->conversation) }}" class="shared-chat-link">
                    Abrir conversa original
                </a>
                <a href="{{ route('chat.index') }}" class="shared-chat-link secondary">
                    Voltar ao chat
                </a>
            </div>
        </div>
    </div>
@endsection
