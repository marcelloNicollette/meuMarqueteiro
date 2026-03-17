@extends('layouts.mayor')

@section('title', 'Novo Compromisso')
@section('topbar-title', 'Novo Compromisso')

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

        /* Form grid */
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

        .form-row.full {
            grid-template-columns: 1fr;
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
            font-family: "Open Sans", sans-serif;
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

        /* Priority selector */
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

        /* Section divider */
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

        /* Actions */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: .75rem;
            margin-top: 1.75rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-lt);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .6rem 1.2rem;
            border-radius: 9px;
            font-family: "Open Sans", sans-serif;
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

        @media(max-width:640px) {
            .form-layout {
                padding: 1rem;
            }

            .form-row,
            .form-row.triple {
                grid-template-columns: 1fr;
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
            <h2>Novo compromisso</h2>
            <p>Adicione um compromisso do programa de governo para acompanhar ao longo do mandato.</p>

            <form method="POST" action="{{ route('mayor.mandato.commitments.store') }}">
                @csrf
                <div class="form-grid">

                    {{-- Título --}}
                    <div class="form-group">
                        <label class="form-label">Título <span class="req">*</span></label>
                        <input class="form-input" type="text" name="title" value="{{ old('title') }}"
                            placeholder="Ex: Construção do novo posto de saúde do bairro Norte" required>
                    </div>

                    {{-- Descrição --}}
                    <div class="form-group">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-textarea" name="description" placeholder="Descreva o compromisso com mais detalhes...">{{ old('description') }}</textarea>
                    </div>

                    {{-- Área + Prioridade --}}
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Área temática <span class="req">*</span></label>
                            <select class="form-select" name="area" required>
                                <option value="">Selecione a área</option>
                                <option value="saude" {{ old('area') == 'saude' ? 'selected' : '' }}>🏥 Saúde</option>
                                <option value="educacao" {{ old('area') == 'educacao' ? 'selected' : '' }}>📚 Educação
                                </option>
                                <option value="infraestrutura"{{ old('area') == 'infraestrutura' ? 'selected' : '' }}>🏗
                                    Infraestrutura</option>
                                <option value="social" {{ old('area') == 'social' ? 'selected' : '' }}>🤝 Social</option>
                                <option value="seguranca" {{ old('area') == 'seguranca' ? 'selected' : '' }}>🛡 Segurança
                                </option>
                                <option value="meio_ambiente" {{ old('area') == 'meio_ambiente' ? 'selected' : '' }}>🌿 Meio
                                    Ambiente</option>
                                <option value="economia" {{ old('area') == 'economia' ? 'selected' : '' }}>💼 Economia
                                </option>
                                <option value="cultura" {{ old('area') == 'cultura' ? 'selected' : '' }}>🎭 Cultura
                                </option>
                                <option value="outros" {{ old('area') == 'outros' ? 'selected' : '' }}>📋 Outros</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Prioridade <span class="req">*</span></label>
                            <div class="priority-group">
                                <input class="priority-opt" type="radio" name="priority" id="p_alta" value="alta"
                                    {{ old('priority', 'media') == 'alta' ? 'checked' : '' }}>
                                <label class="priority-lbl" for="p_alta">🔴 Alta</label>
                                <input class="priority-opt" type="radio" name="priority" id="p_media" value="media"
                                    {{ old('priority', 'media') == 'media' ? 'checked' : '' }}>
                                <label class="priority-lbl" for="p_media">🟡 Média</label>
                                <input class="priority-opt" type="radio" name="priority" id="p_baixa" value="baixa"
                                    {{ old('priority') == 'baixa' ? 'checked' : '' }}>
                                <label class="priority-lbl" for="p_baixa">🟢 Baixa</label>
                            </div>
                        </div>
                    </div>

                    {{-- Prazo + Secretaria --}}
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Prazo previsto</label>
                            <input class="form-input" type="date" name="deadline" value="{{ old('deadline') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Secretaria responsável</label>
                            <input class="form-input" type="text" name="responsible_secretary"
                                value="{{ old('responsible_secretary') }}" placeholder="Ex: Secretaria de Saúde">
                        </div>
                    </div>

                    {{-- Financeiro --}}
                    <div class="form-section">
                        <div class="form-section-title">Informações financeiras (opcional)</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Valor orçado (R$)</label>
                                <input class="form-input" type="number" name="budget_allocated"
                                    value="{{ old('budget_allocated') }}" placeholder="0,00" step="0.01" min="0">
                                <span class="form-hint">Valor previsto no orçamento municipal</span>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Fonte do recurso</label>
                                <select class="form-select" name="budget_source">
                                    <option value="">Selecione</option>
                                    <option value="municipal" {{ old('budget_source') == 'municipal' ? 'selected' : '' }}>
                                        Municipal</option>
                                    <option value="federal" {{ old('budget_source') == 'federal' ? 'selected' : '' }}>
                                        Federal
                                    </option>
                                    <option value="estadual" {{ old('budget_source') == 'estadual' ? 'selected' : '' }}>
                                        Estadual
                                    </option>
                                    <option value="convenio" {{ old('budget_source') == 'convenio' ? 'selected' : '' }}>
                                        Convênio
                                    </option>
                                    <option value="misto" {{ old('budget_source') == 'misto' ? 'selected' : '' }}>Misto
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Notas --}}
                    <div class="form-group">
                        <label class="form-label">Notas internas</label>
                        <textarea class="form-textarea" name="notes"
                            placeholder="Anotações de contexto, estratégia ou observações relevantes...">{{ old('notes') }}</textarea>
                    </div>

                </div>

                <div class="form-actions">
                    <a href="{{ route('mayor.mandato.commitments.index') }}" class="btn btn-outline">Cancelar</a>
                    <button type="submit" class="btn btn-dark">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z" />
                        </svg>
                        Salvar compromisso
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
