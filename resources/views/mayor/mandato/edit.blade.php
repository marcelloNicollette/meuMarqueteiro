@extends('layouts.mayor')

@section('title', 'Editar Compromisso')
@section('topbar-title', 'Editar Compromisso')

@push('styles')
    <style>
        .form-layout {
            padding: 1.75rem 2rem;
            max-width: 760px;
        }

        .form-back {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-size: .8rem;
            color: var(--ink-muted);
            text-decoration: none;
            margin-bottom: 1.25rem;
            transition: color .15s;
        }

        .form-back:hover {
            color: var(--ink);
        }

        .form-back svg {
            width: 15px;
            height: 15px;
        }

        .form-card {
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
        }

        .form-card h2 {
            font-family: 'Lora', serif;
            font-size: 1.25rem;
            color: var(--ink);
            margin-bottom: .35rem;
        }

        .form-card>p {
            font-size: .82rem;
            color: var(--ink-muted);
            margin-bottom: 1.75rem;
        }

        .form-grid {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-row.triple {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }

        .form-label {
            font-size: .77rem;
            font-weight: 500;
            color: var(--ink-soft);
            letter-spacing: .02em;
        }

        .form-label .req {
            color: var(--red);
        }

        .form-input,
        .form-select,
        .form-textarea {
            padding: .6rem .85rem;
            border-radius: 9px;
            border: 1.5px solid var(--border);
            background: var(--white);
            font-family: 'DM Sans', sans-serif;
            font-size: .85rem;
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
            min-height: 80px;
        }

        .form-hint {
            font-size: .72rem;
            color: var(--ink-muted);
            margin-top: .1rem;
        }

        .priority-group {
            display: flex;
            gap: .5rem;
        }

        .priority-opt {
            display: none;
        }

        .priority-lbl {
            flex: 1;
            text-align: center;
            padding: .5rem .6rem;
            border-radius: 8px;
            border: 1.5px solid var(--border);
            font-size: .77rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .15s;
            color: var(--ink-muted);
        }

        .priority-opt:checked+.priority-lbl {
            color: #fff;
        }

        .priority-opt[value="alta"]:checked+.priority-lbl {
            background: var(--red);
            border-color: var(--red);
        }

        .priority-opt[value="media"]:checked+.priority-lbl {
            background: #f59e0b;
            border-color: #f59e0b;
        }

        .priority-opt[value="baixa"]:checked+.priority-lbl {
            background: var(--green);
            border-color: var(--green);
        }

        .form-section {
            margin-top: .5rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border-lt);
        }

        .form-section-title {
            font-size: .72rem;
            font-weight: 500;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--ink-muted);
            margin-bottom: 1rem;
        }

        /* Status selector */
        .status-selector {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .status-opt {
            display: none;
        }

        .status-lbl {
            padding: .45rem .9rem;
            border-radius: 20px;
            border: 1.5px solid var(--border);
            font-size: .77rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .15s;
            color: var(--ink-muted);
            white-space: nowrap;
        }

        .status-opt[value="prometido"]:checked+.status-lbl {
            background: var(--surface);
            border-color: #9ca3af;
            color: var(--ink);
        }

        .status-opt[value="em_andamento"]:checked+.status-lbl {
            background: #e8f0fb;
            border-color: #1a5fa8;
            color: #1a5fa8;
        }

        .status-opt[value="entregue"]:checked+.status-lbl {
            background: var(--green-bg);
            border-color: var(--green);
            color: var(--green);
        }

        .status-opt[value="em_risco"]:checked+.status-lbl {
            background: #fff8e1;
            border-color: #e65100;
            color: #e65100;
        }

        .status-opt[value="cancelado"]:checked+.status-lbl {
            background: var(--red-bg);
            border-color: var(--red);
            color: var(--red);
        }

        /* Progress slider */
        .progress-wrap {
            position: relative;
        }

        .progress-range {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 6px;
            border-radius: 4px;
            background: linear-gradient(to right, var(--ink) 0%, var(--ink) var(--val, 0%), var(--border) var(--val, 0%), var(--border) 100%);
            outline: none;
            cursor: pointer;
        }

        .progress-range::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--ink);
            border: 2px solid #fff;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .2);
            cursor: pointer;
        }

        .progress-display {
            font-size: .95rem;
            font-weight: 500;
            color: var(--ink);
            text-align: center;
            margin-top: .5rem;
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            margin-top: 1.75rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-lt);
        }

        .form-actions-right {
            display: flex;
            gap: .75rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .6rem 1.2rem;
            border-radius: 9px;
            font-family: 'DM Sans', sans-serif;
            font-size: .85rem;
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

        .btn-danger {
            background: none;
            color: var(--red);
            border-color: var(--red-bg);
        }

        .btn-danger:hover {
            background: var(--red-bg);
        }

        @media(max-width:640px) {
            .form-layout {
                padding: 1rem
            }

            .form-row,
            .form-row.triple {
                grid-template-columns: 1fr
            }
        }
    </style>
@endpush

@section('content')
    <div class="form-layout">

        <a href="{{ route('mayor.mandato.commitments.index') }}" class="form-back">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
            </svg>
            Voltar ao programa de governo
        </a>

        <div class="form-card">
            <h2>Editar compromisso</h2>
            <p>Atualize o status, progresso e informações do compromisso.</p>

            <form method="POST" action="{{ route('mayor.mandato.commitments.update', $commitment) }}">
                @csrf
                @method('PUT')
                <div class="form-grid">

                    {{-- Título --}}
                    <div class="form-group">
                        <label class="form-label">Título <span class="req">*</span></label>
                        <input class="form-input" type="text" name="title"
                            value="{{ old('title', $commitment->title) }}" required>
                    </div>

                    {{-- Descrição --}}
                    <div class="form-group">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-textarea" name="description">{{ old('description', $commitment->description) }}</textarea>
                    </div>

                    {{-- Status --}}
                    <div class="form-section">
                        <div class="form-section-title">Status atual</div>
                        <div class="form-group">
                            <div class="status-selector">
                                @foreach (['prometido' => 'Prometido', 'em_andamento' => 'Em andamento', 'entregue' => 'Entregue ✓', 'em_risco' => '⚠ Em risco', 'cancelado' => 'Cancelado'] as $val => $label)
                                    <input class="status-opt" type="radio" name="status" id="s_{{ $val }}"
                                        value="{{ $val }}"
                                        {{ old('status', $commitment->status) == $val ? 'checked' : '' }}>
                                    <label class="status-lbl" for="s_{{ $val }}">{{ $label }}</label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Progresso --}}
                    <div class="form-group">
                        <label class="form-label">Progresso de execução</label>
                        <div class="progress-wrap">
                            <input class="progress-range" type="range" name="progress_percent" id="progressRange"
                                min="0" max="100" step="5"
                                value="{{ old('progress_percent', $commitment->progress_percent) }}"
                                oninput="updateProgress(this)">
                        </div>
                        <div class="progress-display" id="progressDisplay">
                            {{ old('progress_percent', $commitment->progress_percent) }}%</div>
                    </div>

                    {{-- Área + Prioridade --}}
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Área temática <span class="req">*</span></label>
                            <select class="form-select" name="area" required>
                                @foreach (['saude' => '🏥 Saúde', 'educacao' => '📚 Educação', 'infraestrutura' => '🏗 Infraestrutura', 'social' => '🤝 Social', 'seguranca' => '🛡 Segurança', 'meio_ambiente' => '🌿 Meio Ambiente', 'economia' => '💼 Economia', 'cultura' => '🎭 Cultura', 'outros' => '📋 Outros'] as $val => $label)
                                    <option value="{{ $val }}"
                                        {{ old('area', $commitment->area) == $val ? 'selected' : '' }}>{{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Prioridade</label>
                            <div class="priority-group">
                                @foreach (['alta' => '🔴 Alta', 'media' => '🟡 Média', 'baixa' => '🟢 Baixa'] as $val => $label)
                                    <input class="priority-opt" type="radio" name="priority" id="p_{{ $val }}"
                                        value="{{ $val }}"
                                        {{ old('priority', $commitment->priority) == $val ? 'checked' : '' }}>
                                    <label class="priority-lbl" for="p_{{ $val }}">{{ $label }}</label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Prazo + Secretaria --}}
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Prazo previsto</label>
                            <input class="form-input" type="date" name="deadline"
                                value="{{ old('deadline', $commitment->deadline?->format('Y-m-d')) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Secretaria responsável</label>
                            <input class="form-input" type="text" name="responsible_secretary"
                                value="{{ old('responsible_secretary', $commitment->responsible_secretary) }}">
                        </div>
                    </div>

                    {{-- Financeiro --}}
                    <div class="form-section">
                        <div class="form-section-title">Informações financeiras</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Valor orçado (R$)</label>
                                <input class="form-input" type="number" name="budget_allocated"
                                    value="{{ old('budget_allocated', $commitment->budget_allocated) }}" step="0.01"
                                    min="0">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Valor executado (R$)</label>
                                <input class="form-input" type="number" name="budget_spent"
                                    value="{{ old('budget_spent', $commitment->budget_spent) }}" step="0.01"
                                    min="0">
                            </div>
                        </div>
                    </div>

                    {{-- Notas --}}
                    <div class="form-group">
                        <label class="form-label">Notas internas</label>
                        <textarea class="form-textarea" name="notes">{{ old('notes', $commitment->notes) }}</textarea>
                    </div>

                </div>

                <div class="form-actions">
                    <form method="POST" action="{{ route('mayor.mandato.commitments.destroy', $commitment) }}"
                        onsubmit="return confirm('Remover este compromisso?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" />
                            </svg>
                            Excluir
                        </button>
                    </form>
                    <div class="form-actions-right">
                        <a href="{{ route('mayor.mandato.commitments.index') }}" class="btn btn-outline">Cancelar</a>
                        <button type="submit" class="btn btn-dark">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z" />
                            </svg>
                            Atualizar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function updateProgress(input) {
            const val = input.value;
            document.getElementById('progressDisplay').textContent = val + '%';
            input.style.setProperty('--val', val + '%');
        }
        // Init on load
        const range = document.getElementById('progressRange');
        if (range) {
            range.style.setProperty('--val', range.value + '%');
        }
    </script>
@endpush
