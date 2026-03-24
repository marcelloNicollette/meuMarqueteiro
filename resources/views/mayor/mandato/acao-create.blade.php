@extends('layouts.mayor')

@section('title', 'Nova Ação de Governo')
@section('topbar-title', 'Mandato · Nova Ação')

@push('styles')
    <style>
        /* ── Botões — alinhados ao layout do projeto ─────────── */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: .83rem;
            font-weight: 500;
            background: var(--ink);
            color: #fff;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background .15s;
        }

        .btn-primary:hover {
            background: #1e2230;
        }

        .btn-primary svg {
            width: 14px;
            height: 14px;
        }

        .btn-primary:disabled {
            background: var(--border);
            cursor: not-allowed;
            color: var(--ink-muted);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: .83rem;
            font-weight: 500;
            background: var(--white);
            color: var(--ink-soft);
            border: 1.5px solid var(--border);
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

        .btn-gold {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: .83rem;
            font-weight: 500;
            background: var(--gold);
            color: #fff;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: opacity .15s;
        }

        .btn-gold:hover {
            opacity: .88;
        }

        .btn-danger {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: .83rem;
            font-weight: 500;
            background: none;
            color: var(--red);
            border: 1.5px solid var(--red-bg);
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
        }

        .btn-danger:hover {
            background: var(--red-bg);
        }

        /* ── Alertas ──────────────────────────────────────────── */
        .alert-success {
            background: var(--green-bg);
            color: var(--green);
            border: 1px solid #c3e6d0;
            border-radius: 8px;
            padding: .75rem 1rem;
            font-size: .84rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .alert-error {
            background: var(--red-bg);
            color: var(--red);
            border: 1px solid #f5c6c6;
            border-radius: 8px;
            padding: .75rem 1rem;
            font-size: .84rem;
        }

        /* ── Inputs ───────────────────────────────────────────── */
        input[type=text],
        input[type=number],
        input[type=date],
        input[type=url],
        input[type=email],
        select,
        textarea {
            width: 100%;
            padding: .5rem .75rem;
            border: 1.5px solid var(--border);
            border-radius: 7px;
            font-family: 'DM Sans', sans-serif;
            font-size: .84rem;
            color: var(--ink);
            background: var(--white);
            transition: border-color .15s;
            outline: none;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--gold);
        }

        input::placeholder,
        textarea::placeholder {
            color: var(--ink-muted);
        }

        /* ── Submit bar ───────────────────────────────────────── */
        .submit-bar {
            display: flex;
            gap: .6rem;
            justify-content: flex-end;
            align-items: center;
            padding-top: .5rem;
        }

        .acao-wrap {
            padding: 1.75rem 2rem;
            max-width: 860px;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .acao-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .acao-header h1 {
            font-family: 'Lora', serif;
            font-size: 1.35rem;
            color: var(--ink);
            margin: 0;
        }

        /* Form card */
        .form-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
        }

        .form-card-header {
            padding: .75rem 1.25rem;
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #fff;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .form-card-body {
            padding: 1.1rem 1.25rem;
            display: flex;
            flex-direction: column;
            gap: .85rem;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .75rem;
        }

        .form-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: .75rem;
        }

        @media(max-width:640px) {

            .form-grid-2,
            .form-grid-3 {
                grid-template-columns: 1fr;
            }
        }

        label {
            font-size: .75rem;
            color: var(--ink-muted);
            display: block;
            margin-bottom: .25rem;
        }

        label span.req {
            color: #b52b2b;
        }

        /* Promise picker */
        .promise-picker {
            display: flex;
            flex-direction: column;
            gap: .5rem;
        }

        .promise-pick-item {
            border: 1px solid var(--border);
            border-radius: 7px;
            padding: .7rem .9rem;
            transition: border-color .15s;
            cursor: pointer;
        }

        .promise-pick-item.selected {
            border-color: var(--gold);
            background: #fffbf0;
        }

        .promise-pick-check {
            display: flex;
            align-items: flex-start;
            gap: .6rem;
        }

        .promise-pick-check input[type=checkbox] {
            margin-top: .15rem;
            flex-shrink: 0;
        }

        .promise-pick-text {
            font-size: .84rem;
            color: var(--ink);
        }

        .promise-pick-level {
            margin-top: .5rem;
            padding-left: 1.4rem;
            display: none;
        }

        .promise-pick-item.selected .promise-pick-level {
            display: block;
        }

        .level-select {
            display: flex;
            gap: .4rem;
            flex-wrap: wrap;
        }

        .level-btn {
            padding: .25rem .6rem;
            border-radius: 5px;
            font-size: .75rem;
            font-weight: 600;
            border: 1px solid var(--border);
            background: var(--bg);
            cursor: pointer;
            color: var(--ink-muted);
            transition: all .15s;
        }

        .level-btn:hover,
        .level-btn.active {
            border-color: var(--gold);
            background: #fffbf0;
            color: var(--gold);
        }

        .level-btn[data-level="100"].active {
            background: #dcfce7;
            border-color: #1e7e48;
            color: #1e7e48;
        }

        .level-btn[data-level="75"].active {
            background: #dcfce7;
            border-color: #1e7e48;
            color: #1e7e48;
        }

        .level-btn[data-level="50"].active {
            background: #fef3c7;
            border-color: #b8902a;
            color: #b8902a;
        }

        .level-btn[data-level="25"].active {
            background: #fef3c7;
            border-color: #b8902a;
            color: #b8902a;
        }

        .level-btn[data-level="0"].active {
            background: #f3f4f6;
            border-color: #aaa;
            color: #666;
        }

        .axis-group-title {
            font-size: .72rem;
            font-weight: 600;
            color: var(--ink-muted);
            text-transform: uppercase;
            letter-spacing: .08em;
            margin: .5rem 0 .3rem;
        }

        /* Submit bar */
        .submit-bar {
            display: flex;
            gap: .6rem;
            justify-content: flex-end;
        }
    </style>
@endpush

@section('content')
    <div class="acao-wrap">

        <div class="acao-header">
            <div>
                <h1>Nova Ação de Governo</h1>
                <p style="font-size:.82rem;color:var(--ink-muted);margin:.2rem 0 0">Cadastre uma ação e vincule os
                    compromisso(s) do Plano de Governo</p>
            </div>
            <a href="{{ route('mayor.mandato.painel') }}" class="btn-secondary" style="font-size:.8rem">← Painel</a>
        </div>

        @if ($errors->any())
            <div class="alert-error">
                @foreach ($errors->all() as $e)
                    <div>{{ $e }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('mayor.mandato.acao.store') }}" id="acaoForm">
            @csrf

            {{-- GRUPO 1: Identificação --}}
            <div class="form-card">
                <div class="form-card-header" style="background:#1e3a5f">
                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z" />
                    </svg>
                    Identificação
                </div>
                <div class="form-card-body">
                    <div>
                        <label>Título da ação <span class="req">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" required
                            placeholder="ex: Construção de nova UBS no bairro Norte">
                    </div>
                    <div class="form-grid-2">
                        <div>
                            <label>Eixo temático <span class="req">*</span></label>
                            <select name="mandate_axis_id" id="axisSelect" required onchange="filterPromises(this.value)">
                                <option value="">Selecione o eixo</option>
                                @foreach ($axes as $axis)
                                    <option value="{{ $axis->id }}"
                                        {{ old('mandate_axis_id', request('axis')) == $axis->id ? 'selected' : '' }}>
                                        {{ $axis->icon ? $axis->icon . ' ' : '' }}{{ $axis->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>Secretaria responsável <span class="req">*</span></label>
                            <input type="text" name="secretaria" value="{{ old('secretaria') }}"
                                placeholder="ex: Secretaria de Saúde">
                        </div>
                    </div>
                    <div>
                        <label>Descrição</label>
                        <textarea name="description" rows="3" placeholder="Descreva a ação, objetivos e resultados esperados...">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- GRUPO 2: Status e progresso --}}
            <div class="form-card">
                <div class="form-card-header" style="background:#028090">
                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px">
                        <path d="M12 2a10 10 0 100 20A10 10 0 0012 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
                    </svg>
                    Status e Progresso
                </div>
                <div class="form-card-body">
                    <div class="form-grid-2">
                        <div>
                            <label>Status <span class="req">*</span></label>
                            <select name="status" required>
                                <option value="planejado" {{ old('status') == 'planejado' ? 'selected' : '' }}>Planejado
                                </option>
                                <option value="em_andamento"
                                    {{ old('status', 'em_andamento') == 'em_andamento' ? 'selected' : '' }}>Em andamento
                                </option>
                                <option value="concluido" {{ old('status') == 'concluido' ? 'selected' : '' }}>Concluído
                                </option>
                                <option value="suspenso" {{ old('status') == 'suspenso' ? 'selected' : '' }}>Suspenso
                                </option>
                            </select>
                        </div>
                        <div>
                            <label>% de execução física</label>
                            <input type="number" name="physical_progress" value="{{ old('physical_progress', 0) }}"
                                min="0" max="100" placeholder="0">
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div>
                            <label>Data de início</label>
                            <input type="date" name="start_date" value="{{ old('start_date') }}">
                        </div>
                        <div>
                            <label>Data de conclusão</label>
                            <input type="date" name="end_date" value="{{ old('end_date') }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- GRUPO 4: Vínculo com promessas (destaque — coração do sistema) --}}
            <div class="form-card">
                <div class="form-card-header" style="background:#b8902a">
                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px">
                        <path
                            d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                    </svg>
                    Vínculo com Compromisso(s) <span class="req">*</span>
                </div>
                <div class="form-card-body">
                    <p style="font-size:.8rem;color:var(--ink-muted);margin:0">
                        Selecione os compromisso(s) que esta ação atende e defina o nível de atendimento para cada uma.
                    </p>

                    <div class="promise-picker" id="promisePicker">
                        @foreach ($axes as $axis)
                            @if ($axis->promises->isNotEmpty())
                                <div class="axis-group" data-axis="{{ $axis->id }}">
                                    <div class="axis-group-title">{{ $axis->icon ?? '' }} {{ $axis->name }}</div>
                                    @foreach ($axis->promises as $promise)
                                        <div class="promise-pick-item" id="pp_{{ $promise->id }}"
                                            onclick="togglePromise({{ $promise->id }})">
                                            <div class="promise-pick-check">
                                                <input type="checkbox" name="promises[{{ $promise->id }}][id]"
                                                    value="{{ $promise->id }}" id="chk_{{ $promise->id }}"
                                                    onclick="event.stopPropagation();"
                                                    onchange="togglePromise({{ $promise->id }})">
                                                <span class="promise-pick-text">{{ $promise->text }}</span>
                                            </div>
                                            <div class="promise-pick-level">
                                                <label
                                                    style="font-size:.72rem;color:var(--ink-muted);margin-bottom:.35rem">Nível
                                                    de atendimento:</label>
                                                <div class="level-select">
                                                    @foreach ([100 => 'Plena (100%)', 75 => 'Parcial 75%', 50 => 'Parcial 50%', 25 => 'Parcial 25%', 0 => 'Pendente (0%)'] as $lv => $lb)
                                                        <button type="button" class="level-btn"
                                                            data-level="{{ $lv }}"
                                                            onclick="setLevel({{ $promise->id }}, {{ $lv }}, this)">
                                                            {{ $lb }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                                <input type="hidden" name="promises[{{ $promise->id }}][level]"
                                                    id="lv_{{ $promise->id }}" value="0">
                                                <div style="margin-top:.4rem">
                                                    <input type="text"
                                                        name="promises[{{ $promise->id }}][justification]"
                                                        placeholder="Justificativa (opcional)" style="font-size:.78rem"
                                                        onclick="event.stopPropagation()">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- GRUPO 3: Recursos e abrangência --}}
            <div class="form-card">
                <div class="form-card-header" style="background:#36454f">
                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px">
                        <path
                            d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z" />
                    </svg>
                    Recursos e Abrangência
                </div>
                <div class="form-card-body">
                    <div class="form-grid-3">
                        <div>
                            <label>Investimento previsto (R$)</label>
                            <input type="number" name="investment" value="{{ old('investment') }}" step="0.01"
                                min="0" placeholder="0,00">
                        </div>
                        <div>
                            <label>Fonte de recurso</label>
                            <input type="text" name="funding_source" value="{{ old('funding_source') }}"
                                placeholder="ex: Federal, Municipal, PAC">
                        </div>
                        <div>
                            <label>Beneficiários estimados</label>
                            <input type="number" name="beneficiaries" value="{{ old('beneficiaries') }}"
                                min="0" placeholder="ex: 5000">
                        </div>
                    </div>
                    <div>
                        <label>Região / Bairro</label>
                        <input type="text" name="region" value="{{ old('region') }}"
                            placeholder="ex: Bairro Norte, Zona Rural">
                    </div>
                </div>
            </div>

            {{-- GRUPO 5: Evidência e comunicação --}}
            <div class="form-card">
                <div class="form-card-header" style="background:#6d2e46">
                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px">
                        <path
                            d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z" />
                    </svg>
                    Evidência e Comunicação
                </div>
                <div class="form-card-body">
                    <div>
                        <label>Link de comprovação</label>
                        <input type="url" name="proof_url" value="{{ old('proof_url') }}"
                            placeholder="https://...">
                    </div>
                    <div style="display:flex;align-items:center;gap:.6rem">
                        <input type="checkbox" name="is_public" id="isPublic" value="1"
                            {{ old('is_public') ? 'checked' : '' }}>
                        <label for="isPublic" style="font-size:.84rem;color:var(--ink);cursor:pointer;margin:0">
                            Visível no painel público (cidadão)
                        </label>
                    </div>
                </div>
            </div>

            <div class="submit-bar">
                <a href="{{ route('mayor.mandato.painel') }}" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary">Cadastrar ação</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function togglePromise(id) {
            const item = document.getElementById('pp_' + id);
            const chk = document.getElementById('chk_' + id);
            chk.checked = !chk.checked;
            item.classList.toggle('selected', chk.checked);
        }

        function setLevel(promiseId, level, btn) {
            event.stopPropagation();
            // Marcar o item como selecionado
            const item = document.getElementById('pp_' + promiseId);
            const chk = document.getElementById('chk_' + promiseId);
            chk.checked = true;
            item.classList.add('selected');

            // Atualizar o valor hidden
            document.getElementById('lv_' + promiseId).value = level;

            // Destacar o botão ativo
            btn.closest('.level-select').querySelectorAll('.level-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }

        function filterPromises(axisId) {
            // Quando seleciona o eixo, destaca o grupo correspondente
            document.querySelectorAll('.axis-group').forEach(g => {
                g.style.opacity = (!axisId || g.dataset.axis === axisId) ? '1' : '0.4';
            });
        }

        // Inicializar com eixo pré-selecionado
        const axisSelect = document.getElementById('axisSelect');
        if (axisSelect.value) filterPromises(axisSelect.value);
    </script>
@endpush
