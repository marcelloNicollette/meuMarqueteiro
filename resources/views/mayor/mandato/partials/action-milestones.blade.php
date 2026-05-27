<div class="form-card">
    <div class="form-card-header" style="background:#0f766e">
        <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px">
            <path
                d="M19 3H5c-1.1 0-2 .9-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5c0-1.1-.9-2-2-2zm-8 14H7v-2h4v2zm6-4H7v-2h10v2zm0-4H7V7h10v2z" />
        </svg>
        Marcos de Execução (opcional)
    </div>
    <div class="form-card-body">
        <div style="font-size:.8rem;color:var(--ink-muted)">
            Para acoes mais complexas, você pode detalhar entregas parciais com nome, data prevista e conclusao.
            Quando o calculo automatico estiver ativo, o sistema deriva o percentual de execução pela proporcao
            de marcos concluidos.
        </div>

        <div
            style="display:flex;align-items:flex-start;gap:.65rem;padding:.8rem .9rem;border:1px solid var(--border);border-radius:10px;background:#f8fafc">
            <input type="checkbox" name="uses_milestones_progress" id="usesMilestonesProgress" value="1"
                {{ !empty($milestonesEnabled) ? 'checked' : '' }} style="margin-top:.2rem">
            <div>
                <label for="usesMilestonesProgress"
                    style="font-weight:700;font-size:.84rem;color:var(--ink);cursor:pointer">
                    Calcular o % de execução automaticamente por marcos
                </label>
                <div id="milestonesProgressHint" style="font-size:.78rem;color:var(--ink-muted);margin-top:.25rem">
                    @if (!empty($milestonesEnabled))
                        O campo manual de percentual fica bloqueado e passa a refletir o total de marcos concluidos.
                    @else
                        O campo manual de percentual continua valendo; os marcos funcionam como detalhamento opcional.
                    @endif
                </div>
            </div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap">
            <div style="font-size:.78rem;color:var(--ink-muted)">
                Use os marcos para registrar entregas como licitacao concluida, início de obra ou entrega oficial.
            </div>
            <button type="button" class="btn-secondary" id="addMilestoneRow">Adicionar marco</button>
        </div>

        <div id="milestoneRows" style="display:flex;flex-direction:column;gap:.75rem">
            @foreach ($milestoneRows as $index => $row)
                <div class="milestone-row"
                    style="border:1px solid var(--border);border-radius:10px;background:#fff;padding:.9rem">
                    <input type="hidden" name="milestones[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}">
                    <div
                        style="display:grid;grid-template-columns:minmax(0,1.8fr) minmax(150px,.9fr) auto auto;gap:.75rem;align-items:end">
                        <div>
                            <label>Nome do marco</label>
                            <input type="text" name="milestones[{{ $index }}][title]"
                                value="{{ $row['title'] ?? '' }}" placeholder="Ex: Licitacao concluida">
                        </div>
                        <div>
                            <label>Data prevista</label>
                            <input type="date" name="milestones[{{ $index }}][due_date]"
                                value="{{ $row['due_date'] ?? '' }}">
                        </div>
                        <label
                            style="display:flex;align-items:center;gap:.45rem;padding:.65rem .7rem;border:1px solid var(--border);border-radius:8px;background:#fafafa;white-space:nowrap">
                            <input type="checkbox" name="milestones[{{ $index }}][completed]" value="1"
                                {{ !empty($row['completed']) ? 'checked' : '' }}>
                            <span style="font-size:.8rem;color:var(--ink)">Concluido</span>
                        </label>
                        <button type="button" class="btn-danger remove-milestone-row">Remover</button>
                    </div>
                </div>
            @endforeach
        </div>

        @if (isset($progressLogs) && $progressLogs->isNotEmpty())
            <div style="border-top:1px solid var(--border);padding-top:.9rem">
                <div style="font-size:.82rem;font-weight:700;color:var(--ink);margin-bottom:.55rem">Histórico de marcos
                    concluidos</div>
                <div style="display:flex;flex-direction:column;gap:.55rem">
                    @foreach ($progressLogs as $log)
                        <div
                            style="display:flex;justify-content:space-between;gap:.75rem;flex-wrap:wrap;padding:.7rem .85rem;border:1px solid var(--border);border-radius:9px;background:#fafafa">
                            <div style="font-size:.82rem;color:var(--ink)">
                                {{ $log->description ?: 'Marco concluido' }}
                            </div>
                            <div style="font-size:.76rem;color:var(--ink-muted)">
                                {{ $log->occurred_at?->format('d/m/Y H:i') }}
                                @if ($log->user)
                                    · {{ $log->user->name }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<template id="milestoneRowTemplate">
    <div class="milestone-row" style="border:1px solid var(--border);border-radius:10px;background:#fff;padding:.9rem">
        <input type="hidden" data-field="id" value="">
        <div
            style="display:grid;grid-template-columns:minmax(0,1.8fr) minmax(150px,.9fr) auto auto;gap:.75rem;align-items:end">
            <div>
                <label>Nome do marco</label>
                <input type="text" data-field="title" placeholder="Ex: Licitacao concluida">
            </div>
            <div>
                <label>Data prevista</label>
                <input type="date" data-field="due_date">
            </div>
            <label
                style="display:flex;align-items:center;gap:.45rem;padding:.65rem .7rem;border:1px solid var(--border);border-radius:8px;background:#fafafa;white-space:nowrap">
                <input type="checkbox" data-field="completed" value="1">
                <span style="font-size:.8rem;color:var(--ink)">Concluido</span>
            </label>
            <button type="button" class="btn-danger remove-milestone-row">Remover</button>
        </div>
    </div>
</template>

<script>
    (function() {
        const rowsContainer = document.getElementById('milestoneRows');
        const addButton = document.getElementById('addMilestoneRow');
        const template = document.getElementById('milestoneRowTemplate');
        const toggle = document.getElementById('usesMilestonesProgress');
        const hint = document.getElementById('milestonesProgressHint');
        const manualProgress = document.querySelector('input[name="physical_progress"]');

        if (!rowsContainer || !addButton || !template || !toggle) {
            return;
        }

        let nextIndex = rowsContainer.querySelectorAll('.milestone-row').length;

        function applyNames(card, index) {
            card.querySelectorAll('[data-field]').forEach(function(field) {
                field.name = 'milestones[' + index + '][' + field.dataset.field + ']';
            });
        }

        function updateProgressModeState() {
            if (!manualProgress) {
                return;
            }

            manualProgress.disabled = toggle.checked;
            manualProgress.style.background = toggle.checked ? '#f3f4f6' : '#fff';

            if (hint) {
                hint.textContent = toggle.checked ?
                    'O campo manual de percentual fica bloqueado e passa a refletir o total de marcos concluidos.' :
                    'O campo manual de percentual continua valendo; os marcos funcionam como detalhamento opcional.';
            }
        }

        function wireRemove(scope) {
            scope.querySelectorAll('.remove-milestone-row').forEach(function(button) {
                button.addEventListener('click', function() {
                    const row = button.closest('.milestone-row');
                    if (row) {
                        row.remove();
                    }
                });
            });
        }

        addButton.addEventListener('click', function() {
            const row = template.content.firstElementChild.cloneNode(true);
            applyNames(row, nextIndex++);
            wireRemove(row);
            rowsContainer.appendChild(row);
        });

        toggle.addEventListener('change', updateProgressModeState);

        wireRemove(rowsContainer);
        updateProgressModeState();
    })();
</script>
