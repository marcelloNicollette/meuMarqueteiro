@extends('layouts.mayor')

@section('title', 'Demanda')
@section('topbar-title', 'Demanda')

@push('styles')
    <style>
        .demand-show {
            padding: 1.75rem 2rem;
            max-width: 900px;
        }

        .card {
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 14px;
            padding: 1.25rem 1.25rem;
        }

        .demand-title {
            font-family: 'Lora', serif;
            font-size: 1.2rem;
            color: var(--ink);
            margin-bottom: .25rem;
        }

        .demand-meta {
            font-size: .8rem;
            color: var(--ink-muted);
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .tag {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: .25rem .6rem;
            font-size: .72rem;
            color: var(--ink-soft);
        }

        .demand-body {
            font-size: .92rem;
            color: var(--ink);
            line-height: 1.7;
            white-space: pre-wrap;
        }

        .actions {
            display: flex;
            gap: .6rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .status-row {
            display: flex;
            gap: .6rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .status-label {
            font-size: .75rem;
            font-weight: 500;
            color: var(--ink-muted);
        }

        .form-select {
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
            max-width: 240px;
        }

        .form-select:focus {
            border-color: var(--ink);
        }
    </style>
@endpush

@section('content')
    <div class="demand-show">
        <div class="card">
            <div class="demand-title">{{ $demand->title ?: 'Demanda' }}</div>

            @if (session('success'))
                <div style="background:var(--green-bg);border:1px solid #cfe9d9;color:var(--green);border-radius:12px;padding:.85rem 1rem;font-size:.85rem;margin:.85rem 0">
                    {{ session('success') }}
                </div>
            @endif

            <div class="demand-meta">
                @if ($demand->created_at)
                    <span class="tag">{{ $demand->created_at->format('d/m/Y H:i') }}</span>
                @endif
                @if ($demand->locality)
                    <span class="tag">{{ $demand->locality }}</span>
                @endif
                @if ($demand->area)
                    <span class="tag">{{ ucfirst(str_replace('_', ' ', $demand->area)) }}</span>
                @endif
                <span class="tag">
                    {{ match ($demand->status) {'resolved' => '✅ resolvida','in_progress' => '🟦 em andamento','cancelled' => '⛔ cancelada',default => '🟨 pendente'} }}
                </span>
                @if ($demand->priority)
                    <span class="tag">prioridade: {{ $demand->priority }}</span>
                @endif
                @if ($demand->is_urgent)
                    <span class="tag">⚠️ urgente</span>
                @endif
            </div>

            <div class="demand-body">{{ $demand->raw_input }}</div>

            <div class="actions">
                <a class="btn btn-outline" href="{{ route('mayor.mandato.demands.index') }}">Voltar</a>
                <button class="btn btn-gold" type="button" onclick="askAssistant()">
                    Pedir para o assistente organizar
                </button>
            </div>

            <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border-lt)">
                <form method="POST" action="{{ route('mayor.mandato.demands.status', $demand) }}" class="status-row">
                    @csrf
                    @method('PATCH')
                    <label class="status-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="pending" @selected($demand->status === 'pending')>🟨 Pendente</option>
                        <option value="in_progress" @selected($demand->status === 'in_progress')>🟦 Em andamento</option>
                        <option value="resolved" @selected($demand->status === 'resolved')>✅ Resolvida</option>
                        <option value="cancelled" @selected($demand->status === 'cancelled')>⛔ Cancelada</option>
                    </select>
                    <button class="btn btn-dark" type="submit">Salvar status</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function askAssistant() {
            const question =
                `Registre e organize esta demanda que recebi em campo:\n\n` +
                `Demanda: "{{ addslashes($demand->raw_input) }}"\n` +
                `Localidade: "{{ addslashes($demand->locality ?? '') }}"\n` +
                `Área: "{{ addslashes($demand->area ?? '') }}"\n\n` +
                `Organize por tema, localidade e secretaria responsável, e sugira as próximas ações.`;

            sessionStorage.setItem('chatPrefill', question);
            window.location.href = '{{ route('mayor.chat.index') }}';
        }
    </script>
@endpush
