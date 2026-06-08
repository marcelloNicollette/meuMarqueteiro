@extends('layouts.mayor')

@section('title', 'Resolve ai')
@section('topbar-title', 'Resolve ai')

@push('styles')
    <style>
        .resolve-show {
            padding: 1.75rem 2rem;
            max-width: 1120px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .shell {
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 16px;
            padding: 1.2rem 1.3rem;
        }

        .title {
            font-family: "Outfit", sans-serif;
            font-size: 1.25rem;
            color: var(--ink);
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin-top: .65rem;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            padding: .22rem .58rem;
            border-radius: 999px;
            font-size: .69rem;
            font-weight: 700;
        }

        .split {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 1rem;
        }

        .label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--ink-muted);
            font-weight: 700;
        }

        .value {
            font-size: .88rem;
            color: var(--ink);
            line-height: 1.55;
            margin-top: .25rem;
        }

        .body-copy {
            white-space: pre-wrap;
            font-size: .92rem;
            color: var(--ink);
            line-height: 1.7;
        }

        .section-title {
            font-family: "Outfit", sans-serif;
            font-size: 1rem;
            color: var(--ink);
            margin-bottom: .3rem;
        }

        .section-copy {
            font-size: .8rem;
            color: var(--ink-muted);
            line-height: 1.5;
            margin-bottom: .85rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }

        .full {
            grid-column: 1 / -1;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: .3rem;
        }

        .form-label {
            font-size: .73rem;
            font-weight: 700;
            color: var(--ink-soft);
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: .62rem .78rem;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            background: var(--white);
            color: var(--ink);
            font-size: .84rem;
            outline: none;
        }

        .form-textarea {
            min-height: 88px;
            resize: vertical;
        }

        .actions-row {
            display: flex;
            gap: .55rem;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            margin-top: .8rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .56rem .95rem;
            border-radius: 10px;
            font-size: .8rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: 1.5px solid transparent;
        }

        .btn-dark {
            background: var(--ink);
            color: #fff;
            border-color: var(--ink);
        }

        .btn-outline {
            background: var(--white);
            color: var(--ink);
            border-color: var(--border);
        }

        .btn-gold {
            background: var(--gold);
            color: #fff;
            border-color: var(--gold);
        }

        .timeline {
            display: flex;
            flex-direction: column;
            gap: .7rem;
        }

        .timeline-item {
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: .85rem .95rem;
            background: var(--white);
        }

        .timeline-head {
            display: flex;
            justify-content: space-between;
            gap: .8rem;
            flex-wrap: wrap;
            font-size: .78rem;
            color: var(--ink-muted);
        }

        .timeline-message {
            margin-top: .35rem;
            font-size: .86rem;
            color: var(--ink);
            line-height: 1.55;
            white-space: pre-wrap;
        }

        @media (max-width: 980px) {

            .resolve-show,
            .split,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .resolve-show {
                padding: 1rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $status = match ($demand->status) {
            'registered', 'pending' => ['label' => 'Registrada', 'bg' => '#fffbeb', 'color' => '#b45309'],
            'in_progress' => ['label' => 'Em andamento', 'bg' => '#eff6ff', 'color' => '#1d4ed8'],
            'overdue' => ['label' => 'Atrasada', 'bg' => '#fef2f2', 'color' => '#b91c1c'],
            'awaiting_confirmation' => ['label' => 'Aguardando confirmação', 'bg' => '#f5f3ff', 'color' => '#7c3aed'],
            'completed', 'resolved' => ['label' => 'Concluída', 'bg' => '#ecfdf5', 'color' => '#047857'],
            'reopened' => ['label' => 'Reaberta', 'bg' => '#fff7ed', 'color' => '#c2410c'],
            default => [
                'label' => ucfirst(str_replace('_', ' ', $demand->status)),
                'bg' => '#f3f4f6',
                'color' => '#6b7280',
            ],
        };
        $priority = match ($demand->priority) {
            'alta' => ['label' => 'Alta', 'bg' => '#fef2f2', 'color' => '#b91c1c'],
            'baixa' => ['label' => 'Baixa', 'bg' => '#ecfdf5', 'color' => '#047857'],
            default => ['label' => 'Média', 'bg' => '#eff6ff', 'color' => '#1d4ed8'],
        };
        $dueAt = $demand->due_at ?? ($demand->due_date ? \Carbon\Carbon::parse($demand->due_date)->endOfDay() : null);
        $timelineLabel = function ($type) {
            return match ($type) {
                'registered' => 'Registro',
                'acknowledged' => 'Acuse de recebimento',
                'progress_updated' => 'Andamento atualizado',
                'progress_note' => 'Atualização manual',
                'completion_requested' => 'Conclusão enviada',
                'completion_confirmed' => 'Conclusão confirmada',
                'reopened' => 'Demanda reaberta',
                'details_updated' => 'Dados atualizados',
                'inactivity_followup' => 'Cobrança por inatividade',
                'overdue_followup' => 'Cobrança por atraso',
                'overdue_marked' => 'Atraso automático',
                default => ucfirst(str_replace('_', ' ', $type)),
            };
        };
    @endphp

    <div class="resolve-show">
        <div class="shell">
            <div class="title">{{ $demand->title ?: 'Demanda' }}</div>

            @if (session('success'))
                <div
                    style="background:var(--green-bg);border:1px solid #cfe9d9;color:var(--green);border-radius:12px;padding:.85rem 1rem;font-size:.84rem;margin-top:.85rem">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('warning'))
                <div
                    style="background:#fff7ed;border:1px solid #fed7aa;color:#c2410c;border-radius:12px;padding:.85rem 1rem;font-size:.84rem;margin-top:.85rem">
                    {{ session('warning') }}
                </div>
            @endif

            @if ($errors->any())
                <div
                    style="background:var(--red-bg);border:1px solid #f3caca;color:var(--red);border-radius:12px;padding:.85rem 1rem;font-size:.84rem;margin-top:.85rem;line-height:1.55">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="meta">
                <span class="chip"
                    style="background:{{ $status['bg'] }};color:{{ $status['color'] }}">{{ $status['label'] }}</span>
                <span class="chip"
                    style="background:{{ $priority['bg'] }};color:{{ $priority['color'] }}">{{ $priority['label'] }}</span>
                @if ($demand->input_type === 'voice')
                    <span class="chip" style="background:#fdfaf4;color:#b8902a">Registro por voz</span>
                @endif
                @if ($demand->is_urgent)
                    <span class="chip" style="background:#fef2f2;color:#b91c1c">Urgente</span>
                @endif
                @if ($dueAt)
                    <span class="chip" style="background:#f3f4f6;color:#6b7280">
                        {{ $dueAt->isPast() && !in_array($demand->status, ['completed', 'resolved'], true) ? 'Atrasada há ' . $dueAt->diffForHumans(null, true) : 'Prazo ' . $dueAt->format('d/m/Y H:i') }}
                    </span>
                @endif
            </div>

            <div class="actions-row" style="justify-content:flex-start">
                <a class="btn btn-outline" href="{{ route($routeBase . '.index') }}">Voltar</a>
                @if (!$isSecretaryPanel)
                    <button class="btn btn-gold" type="button" onclick="askAssistant()">Pedir apoio ao assistente</button>
                @endif
            </div>
        </div>

        <div class="split">
            <div class="shell">
                <div class="section-title">Descrição da demanda</div>
                <div class="section-copy">Registro original recebido em campo e contexto principal do Resolve ai.</div>
                <div class="body-copy">{{ $demand->raw_input }}</div>

                <div class="split" style="grid-template-columns:repeat(2,minmax(0,1fr));margin-top:1rem">
                    <div>
                        <div class="label">Secretaria responsável</div>
                        <div class="value">
                            {{ $demand->contactArea?->name ?? ($demand->area ?: 'Não definida') }}
                            @if ($demand->contactArea?->contact_name)
                                <br>{{ $demand->contactArea->contact_name }}
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="label">Criador</div>
                        <div class="value">{{ $demand->registeredBy?->name ?? 'Sistema' }}</div>
                    </div>
                    <div>
                        <div class="label">Localidade</div>
                        <div class="value">{{ $demand->locality ?: 'Não informada' }}</div>
                    </div>
                    <div>
                        <div class="label">Endereço</div>
                        <div class="value">{{ $demand->address ?: 'Não informado' }}</div>
                    </div>
                </div>

                @if ($demand->completion_note || $demand->completion_attachment_path)
                    <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
                        <div class="section-title">Comprovante de conclusão</div>
                        @if ($demand->completion_note)
                            <div class="value">{{ $demand->completion_note }}</div>
                        @endif
                        @if ($demand->completion_attachment_path)
                            <div style="margin-top:.45rem">
                                <a class="btn btn-outline"
                                    href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($demand->completion_attachment_path) }}"
                                    target="_blank">
                                    Abrir anexo: {{ $demand->completion_attachment_name ?: 'arquivo' }}
                                </a>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($demand->reopened_reason)
                    <div
                        style="margin-top:1rem;padding:1rem;border:1px solid #fed7aa;border-radius:12px;background:#fff7ed">
                        <div class="label" style="color:#c2410c">Motivo da reabertura</div>
                        <div class="value">{{ $demand->reopened_reason }}</div>
                    </div>
                @endif

                @if (!$isSecretaryPanel && in_array($demand->status, ['awaiting_confirmation', 'completed', 'resolved'], true))
                    <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
                        <div class="section-title">Integrações estratégicas</div>
                        <div class="section-copy">
                            Transforme a demanda concluída em comunicação pública, narrativa política e cobrança de
                            acompanhamento.
                        </div>

                        <form method="POST" action="{{ route('mayor.mandato.demands.communication-draft', $demand) }}">
                            @csrf
                            <div class="form-grid" style="align-items:end">
                                <div class="form-group">
                                    <label class="form-label">Canal do rascunho</label>
                                    <select name="channel" class="form-select">
                                        <option value="instagram">Instagram</option>
                                        <option value="facebook">Facebook</option>
                                        <option value="whatsapp">WhatsApp</option>
                                    </select>
                                </div>
                                <div class="actions-row" style="justify-content:flex-start;margin-top:0">
                                    <button class="btn btn-gold" type="submit">Gerar rascunho em Comunicação</button>
                                </div>
                            </div>
                        </form>

                        <div class="actions-row" style="justify-content:flex-start;margin-top:.8rem">
                            <form method="POST"
                                action="{{ route('mayor.mandato.demands.strategic-conversation', $demand) }}">
                                @csrf
                                <input type="hidden" name="mode" value="narrative">
                                <button class="btn btn-dark" type="submit">Abrir narrativa no Meu Assistente</button>
                            </form>
                            <form method="POST"
                                action="{{ route('mayor.mandato.demands.strategic-conversation', $demand) }}">
                                @csrf
                                <input type="hidden" name="mode" value="followup">
                                <button class="btn btn-outline" type="submit">Abrir cobrança estratégica</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

            <div class="shell">
                <div class="section-title">Ações do fluxo</div>
                <div class="section-copy">Use o workflow do Resolve ai para registrar andamento, conclusão e confirmação.
                </div>

                @if (in_array($demand->status, ['registered', 'pending', 'reopened', 'overdue'], true))
                    <form method="POST" action="{{ route($routeBase . '.status', $demand) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="acknowledge">
                        <div class="form-group">
                            <label class="form-label">Acuse de recebimento / início</label>
                            <textarea name="message" class="form-textarea"
                                placeholder="Opcional: registre a primeira orientação de encaminhamento..."></textarea>
                        </div>
                        <div class="actions-row">
                            <button class="btn btn-dark" type="submit">Colocar em andamento</button>
                        </div>
                    </form>
                @endif

                @if (in_array($demand->status, ['in_progress', 'overdue', 'reopened'], true))
                    <form method="POST" action="{{ route('mayor.mandato.demands.status', $demand) }}"
                        enctype="multipart/form-data"
                        style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="complete">
                        <div class="form-group">
                            <label class="form-label">Nota de conclusão</label>
                            <textarea name="message" class="form-textarea"
                                placeholder="Descreva o que foi executado e como a demanda foi resolvida."></textarea>
                        </div>
                        <div class="form-group" style="margin-top:.75rem">
                            <label class="form-label">Comprovante</label>
                            <input type="file" name="attachment" class="form-input"
                                accept=".jpg,.jpeg,.png,.mp4,.pdf,.doc,.docx">
                        </div>
                        <div class="actions-row">
                            <button class="btn btn-gold" type="submit">Enviar para confirmação</button>
                        </div>
                    </form>
                @endif

                @if ($demand->status === 'awaiting_confirmation')
                    <form method="POST" action="{{ route($routeBase . '.status', $demand) }}"
                        enctype="multipart/form-data"
                        style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
                        @method('PATCH')
                        <input type="hidden" name="action" value="confirm">
                        <div class="form-group">
                            <label class="form-label">Nota final</label>
                            <textarea name="message" class="form-textarea" placeholder="Opcional: registre a confirmação da entrega."></textarea>
                        </div>
                        <div class="actions-row">
                            <button class="btn btn-dark" type="submit">Confirmar conclusão</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route($routeBase . '.status', $demand) }}" style="margin-top:.8rem">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="reopen">
                        <div class="form-group">
                            <label class="form-label">Justificativa para reabrir</label>
                            <textarea name="message" class="form-textarea"
                                placeholder="Explique por que a demanda precisa voltar para execução."></textarea>
                        </div>
                        <div class="actions-row">
                            <button class="btn btn-outline" type="submit">Reabrir demanda</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <div class="split">
            <div class="shell">
                <div class="section-title">Atualizar dados da demanda</div>
                <div class="section-copy">Ajuste prioridade, prazo e encaminhamento sem perder o histórico.</div>
                <form method="POST" action="{{ route($routeBase . '.update', $demand) }}">
                    @csrf
                    @method('PATCH')
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Prioridade</label>
                            <select name="priority" class="form-select">
                                <option value="alta" @selected($demand->priority === 'alta')>Alta</option>
                                <option value="media" @selected($demand->priority === 'media')>Média</option>
                                <option value="baixa" @selected($demand->priority === 'baixa')>Baixa</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Prazo manual</label>
                            <input type="date" name="due_date" class="form-input"
                                value="{{ optional($demand->due_date)->format('Y-m-d') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Bairro ou localidade</label>
                            <input type="text" name="locality" class="form-input" value="{{ $demand->locality }}"
                                list="resolveAiLocalities">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Endereço complementar</label>
                            <input type="text" name="address" class="form-input" value="{{ $demand->address }}">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Secretaria responsável</label>
                            <select name="contact_area_id" class="form-select">
                                <option value="">Selecione</option>
                                @foreach ($contactAreas as $area)
                                    <option value="{{ $area->id }}" @selected((int) $demand->contact_area_id === (int) $area->id)>{{ $area->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="actions-row">
                        <button class="btn btn-dark" type="submit">Salvar ajustes</button>
                    </div>
                </form>
                @if (!empty($localities) && count($localities) > 0)
                    <datalist id="resolveAiLocalities">
                        @foreach ($localities as $locality)
                            <option value="{{ $locality->name }}">
                                {{ ucfirst($locality->type) }}{{ $locality->zone ? ' · ' . $locality->zone : '' }}
                            </option>
                        @endforeach
                    </datalist>
                @endif
            </div>

            <div class="shell">
                <div class="section-title">Registrar atualização</div>
                <div class="section-copy">Adicione observações de andamento ou cobrança. O histórico do Resolve ai
                    permanece imutável.</div>
                <form method="POST" action="{{ route($routeBase . '.comments.add', $demand) }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Atualização</label>
                        <textarea name="comment" class="form-textarea"
                            placeholder="Ex: equipe vistoriou o local, material em separação, aguardando execução..."></textarea>
                    </div>
                    <div class="actions-row">
                        <button class="btn btn-dark" type="submit">Registrar no histórico</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="shell">
            <div class="section-title">Encaminhamento e notificações</div>
            <div class="section-copy">
                Histórico operacional dos avisos disparados pelo Resolve ai para responsável e criador.
            </div>
            <div
                style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.75rem;margin-bottom:1rem;font-size:.8rem;color:var(--ink-soft)">
                <div><strong>Alta:</strong> {{ $resolveAiSettings['priority_hours']['alta'] ?? 48 }}h</div>
                <div><strong>Média:</strong> {{ $resolveAiSettings['priority_hours']['media'] ?? 168 }}h</div>
                <div><strong>Baixa:</strong> {{ $resolveAiSettings['priority_hours']['baixa'] ?? 360 }}h</div>
                <div><strong>Alerta:</strong> {{ $resolveAiSettings['alert_lead_hours'] ?? 24 }}h antes</div>
                <div><strong>Sem andamento:</strong> {{ $resolveAiSettings['inactivity_followup_hours'] ?? 48 }}h</div>
                <div><strong>Repetição atraso:</strong> {{ $resolveAiSettings['overdue_repeat_hours'] ?? 24 }}h</div>
            </div>
            <div class="timeline">
                @forelse ($demand->notifications as $notification)
                    <div class="timeline-item">
                        <div class="timeline-head">
                            <div>
                                <strong>{{ ucfirst(str_replace('_', ' ', $notification->event_type)) }}</strong>
                                · {{ strtoupper($notification->channel) }}
                                @if ($notification->destination)
                                    · {{ $notification->destination }}
                                @endif
                            </div>
                            <div>
                                {{ $notification->sent_at?->format('d/m/Y H:i') ?? ($notification->failed_at?->format('d/m/Y H:i') ?? $notification->created_at?->format('d/m/Y H:i')) }}
                            </div>
                        </div>
                        <div class="timeline-message">
                            Status: {{ ucfirst($notification->status) }}
                            @if ($notification->error_message)
                                <br>Erro: {{ $notification->error_message }}
                            @endif
                            @if ($notification->message)
                                <br>{{ $notification->message }}
                            @endif
                        </div>
                    </div>
                @empty
                    <div style="font-size:.84rem;color:var(--ink-muted)">Nenhuma notificação disparada ainda para esta
                        demanda.</div>
                @endforelse
            </div>
        </div>

        <div class="shell">
            <div class="section-title">Linha do tempo</div>
            <div class="section-copy">Todas as ações e atualizações registradas na demanda.</div>
            <div class="timeline">
                @forelse ($demand->events as $event)
                    <div class="timeline-item">
                        <div class="timeline-head">
                            <div><strong>{{ $timelineLabel($event->event_type) }}</strong> ·
                                {{ $event->user?->name ?? 'Sistema' }}</div>
                            <div>{{ $event->created_at?->format('d/m/Y H:i') }}</div>
                        </div>
                        @if ($event->message)
                            <div class="timeline-message">{{ $event->message }}</div>
                        @endif
                    </div>
                @empty
                    <div style="font-size:.84rem;color:var(--ink-muted)">Ainda não há eventos registrados para esta
                        demanda.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function askAssistant() {
            const question =
                `Registre e organize esta demanda do Resolve ai:\n\n` +
                `Demanda: "{{ addslashes($demand->raw_input) }}"\n` +
                `Localidade: "{{ addslashes($demand->locality ?? '') }}"\n` +
                `Secretaria: "{{ addslashes($demand->contactArea?->name ?? ($demand->area ?? '')) }}"\n` +
                `Status: "{{ addslashes($demand->status) }}"\n\n` +
                `Sugira prioridade, próximos passos, cobrança e encaminhamento.`;

            sessionStorage.setItem('chatPrefill', question);
            window.location.href = '{{ route('mayor.chat.index') }}';
        }
    </script>
@endpush
