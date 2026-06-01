@extends('layouts.admin')
@section('title', 'Linha do Tempo de Cobertura')
@section('content')
    <div style="padding:2rem;max-width:1200px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1.5rem">
            <div>
                <a href="{{ route('admin.coverage-alerts.index') }}"
                    style="font-size:.85rem;color:#6b7280;text-decoration:none">← Central de alertas</a>
                <h1 style="font-size:1.45rem;font-weight:700;margin-top:.45rem">Linha do tempo de cobertura</h1>
                <p style="font-size:.88rem;color:#6b7280;margin-top:.25rem">{{ $municipality->name }} · histórico completo de alertas, resolução e prontidão atual</p>
            </div>
            <div style="display:flex;gap:.55rem;flex-wrap:wrap">
                <a href="{{ route('admin.municipalities.show', $municipality) }}"
                    style="padding:.6rem 1rem;border:1px solid #d1d5db;border-radius:8px;font-size:.84rem;color:#374151;text-decoration:none">Abrir município</a>
                <a href="{{ route('admin.municipalities.onboarding.show', $municipality) }}"
                    style="padding:.6rem 1rem;border:1px solid #d4af37;border-radius:8px;font-size:.84rem;color:#92400e;text-decoration:none">Revisar onboarding</a>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;margin-bottom:1.25rem">
            <div style="background:#fff;padding:1rem;border-radius:12px;border:1px solid #e5e7eb">
                <div style="font-size:.74rem;color:#6b7280;text-transform:uppercase;font-weight:700">Score atual</div>
                <div style="font-size:1.45rem;font-weight:700;margin-top:.2rem">{{ $configurationSummary['score'] }}%</div>
                <div style="font-size:.77rem;color:#6b7280;margin-top:.25rem">{{ $configurationSummary['summary_label'] }}</div>
            </div>
            <div style="background:#fff;padding:1rem;border-radius:12px;border:1px solid #e5e7eb">
                <div style="font-size:.74rem;color:#6b7280;text-transform:uppercase;font-weight:700">Alertas</div>
                <div style="font-size:1.45rem;font-weight:700;margin-top:.2rem">{{ $timelineStats['total'] }}</div>
                <div style="font-size:.77rem;color:#6b7280;margin-top:.25rem">Histórico registrado</div>
            </div>
            <div style="background:#fff;padding:1rem;border-radius:12px;border:1px solid #e5e7eb">
                <div style="font-size:.74rem;color:#6b7280;text-transform:uppercase;font-weight:700">Ativos</div>
                <div style="font-size:1.45rem;font-weight:700;margin-top:.2rem;color:#b45309">{{ $timelineStats['active'] }}</div>
                <div style="font-size:.77rem;color:#6b7280;margin-top:.25rem">Cobertura pendente</div>
            </div>
            <div style="background:#fff;padding:1rem;border-radius:12px;border:1px solid #e5e7eb">
                <div style="font-size:.74rem;color:#6b7280;text-transform:uppercase;font-weight:700">Resolvidos</div>
                <div style="font-size:1.45rem;font-weight:700;margin-top:.2rem;color:#166534">{{ $timelineStats['resolved'] }}</div>
                <div style="font-size:.77rem;color:#6b7280;margin-top:.25rem">Recuperados</div>
            </div>
            <div style="background:#fff;padding:1rem;border-radius:12px;border:1px solid #e5e7eb">
                <div style="font-size:.74rem;color:#6b7280;text-transform:uppercase;font-weight:700">Alta severidade</div>
                <div style="font-size:1.45rem;font-weight:700;margin-top:.2rem;color:#b91c1c">{{ $timelineStats['high'] }}</div>
                <div style="font-size:.77rem;color:#6b7280;margin-top:.25rem">Máximo histórico</div>
            </div>
        </div>

        <div style="background:#fff;padding:1.2rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1.25rem">
            <div style="font-size:.92rem;font-weight:700;color:#111827;margin-bottom:.25rem">Checklist atual de prontidão</div>
            <div style="font-size:.79rem;color:#6b7280;margin-bottom:.85rem">Estado atual do município nas frentes que alimentam a cobertura da plataforma.</div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem">
                @foreach ($configurationSummary['checklist'] as $item)
                    <div style="padding:.9rem 1rem;border-radius:10px;border:1px solid {{ $item['ready'] ? '#bbf7d0' : '#e5e7eb' }};background:{{ $item['ready'] ? '#f0fdf4' : '#fafafa' }}">
                        <div style="font-size:.82rem;font-weight:700;color:{{ $item['ready'] ? '#166534' : '#374151' }}">{{ $item['label'] }}</div>
                        <div style="font-size:.76rem;color:#6b7280;margin-top:.25rem">{{ $item['ready'] ? 'Atendido agora' : 'Pendente agora' }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div style="display:grid;gap:1rem">
            @forelse ($timelineByDay as $group)
                <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden">
                    <div style="padding:1rem 1.2rem;background:#f9fafb;border-bottom:1px solid #e5e7eb">
                        <div style="font-size:.85rem;font-weight:700;color:#111827">{{ $group['label'] }}</div>
                        <div style="font-size:.76rem;color:#6b7280;margin-top:.15rem">{{ $group['items']->count() }} evento(s) detectado(s)</div>
                    </div>
                    <div style="display:grid;gap:.75rem;padding:1rem 1.2rem">
                        @foreach ($group['items'] as $alert)
                            @php
                                $workflow = $alert->workflow_snapshot ?? [];
                                $history = collect($workflow['history'] ?? [])->take(6);
                                $comments = collect($workflow['comments'] ?? [])->take(4);
                            @endphp
                            <div style="padding:1rem;border-radius:12px;border:1px solid #e5e7eb;background:#fafafa">
                                <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start">
                                    <div>
                                        <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                                            <span style="font-size:.86rem;font-weight:700;color:#111827">{{ $alert->title }}</span>
                                            <span style="padding:.2rem .55rem;border-radius:999px;background:{{ $alert->severity === 'high' ? '#fef2f2' : '#fff7ed' }};color:{{ $alert->severity === 'high' ? '#b91c1c' : '#b45309' }};font-size:.72rem;font-weight:700">{{ strtoupper($alert->severity) }}</span>
                                            <span style="padding:.2rem .55rem;border-radius:999px;background:{{ $alert->status === 'active' ? '#eff6ff' : '#ecfdf5' }};color:{{ $alert->status === 'active' ? '#1d4ed8' : '#166534' }};font-size:.72rem;font-weight:700">{{ strtoupper($alert->status) }}</span>
                                        </div>
                                        <div style="font-size:.78rem;color:#6b7280;margin-top:.35rem;line-height:1.55">{{ $alert->message }}</div>
                                        <div style="display:flex;gap:.45rem;flex-wrap:wrap;margin-top:.45rem">
                                            <span style="padding:.2rem .5rem;border-radius:999px;background:#f3f4f6;color:#374151;font-size:.71rem;font-weight:700">
                                                Owner: {{ $workflow['owner_name'] ?? 'Não definido' }}
                                            </span>
                                            <span style="padding:.2rem .5rem;border-radius:999px;background:{{ ($workflow['acknowledged'] ?? false) ? '#ecfdf5' : '#f3f4f6' }};color:{{ ($workflow['acknowledged'] ?? false) ? '#166534' : '#6b7280' }};font-size:.71rem;font-weight:700">
                                                {{ ($workflow['acknowledged'] ?? false) ? 'Ack por ' . ($workflow['acknowledged_by_name'] ?? '—') : 'Sem acknowledge' }}
                                            </span>
                                            <span style="padding:.2rem .5rem;border-radius:999px;background:{{ ($workflow['owner_sla_status'] ?? '') === 'breached' ? '#fef2f2' : (($workflow['owner_sla_status'] ?? '') === 'warning' ? '#fff7ed' : '#f3f4f6') }};color:{{ ($workflow['owner_sla_status'] ?? '') === 'breached' ? '#b91c1c' : (($workflow['owner_sla_status'] ?? '') === 'warning' ? '#b45309' : '#6b7280') }};font-size:.71rem;font-weight:700">
                                                {{ ($workflow['owner_sla_status'] ?? '') === 'breached' ? 'Owner SLA estourado' : (($workflow['owner_sla_status'] ?? '') === 'warning' ? 'Owner SLA em risco' : 'Owner SLA estável') }}
                                            </span>
                                        </div>
                                    </div>
                                    <div style="font-size:.76rem;color:#6b7280;text-align:right;min-width:170px">
                                        <div>Primeira: {{ optional($alert->first_detected_at)->format('d/m/Y H:i') ?: '—' }}</div>
                                        <div style="margin-top:.15rem">Última: {{ optional($alert->last_detected_at)->format('d/m/Y H:i') ?: '—' }}</div>
                                        <div style="margin-top:.15rem">Resolvido: {{ optional($alert->resolved_at)->format('d/m/Y H:i') ?: '—' }}</div>
                                    </div>
                                </div>
                                <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-top:.75rem">
                                    <div style="font-size:.75rem;color:#9ca3af">
                                        Frente: {{ str_replace('_', ' ', $alert->event_type) }}
                                    </div>
                                    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                                        @if ($alert->action_url)
                                            <a href="{{ $alert->action_url }}"
                                                style="padding:.35rem .7rem;border:1px solid #d1d5db;border-radius:7px;font-size:.77rem;color:#374151;text-decoration:none">
                                                Abrir ação
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.coverage-alerts.index', ['municipality_id' => $municipality->id, 'event_type' => $alert->event_type]) }}"
                                            style="padding:.35rem .7rem;border:1px solid #d1d5db;border-radius:7px;font-size:.77rem;color:#374151;text-decoration:none">
                                            Filtrar na central
                                        </a>
                                    </div>
                                </div>
                                @if ($history->isNotEmpty())
                                    <div style="margin-top:.8rem;padding:.8rem;border-radius:10px;background:#fff;border:1px dashed #e5e7eb">
                                        <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700;margin-bottom:.4rem">
                                            Trilha auditável
                                        </div>
                                        <div style="display:grid;gap:.3rem">
                                            @foreach ($history as $event)
                                                <div style="font-size:.74rem;color:#4b5563;line-height:1.45">
                                                    <strong>{{ ucfirst(str_replace('_', ' ', $event['transition'] ?? 'evento')) }}</strong>
                                                    · {{ $event['actor_name'] ?? 'Sistema' }}
                                                    · {{ !empty($event['details']) ? $event['details'] . ' · ' : '' }}
                                                    {{ !empty($event['at']) ? \Illuminate\Support\Carbon::parse($event['at'])->format('d/m/Y H:i') : '—' }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                @if ($comments->isNotEmpty())
                                    <div style="margin-top:.8rem;padding:.8rem;border-radius:10px;background:#fff;border:1px solid #e5e7eb">
                                        <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700;margin-bottom:.4rem">
                                            Comentários internos
                                        </div>
                                        <div style="display:grid;gap:.3rem">
                                            @foreach ($comments as $comment)
                                                <div style="font-size:.74rem;color:#4b5563;line-height:1.45">
                                                    <strong>{{ $comment['author_name'] ?? 'Operação' }}</strong>
                                                    · {{ !empty($comment['at']) ? \Illuminate\Support\Carbon::parse($comment['at'])->format('d/m/Y H:i') : '—' }}
                                                    <br>{{ $comment['message'] ?? '' }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div style="background:#fff;padding:2rem;border-radius:12px;border:1px solid #e5e7eb;text-align:center;color:#9ca3af">
                    Este município ainda não possui eventos registrados na linha do tempo de cobertura.
                </div>
            @endforelse
        </div>
    </div>
@endsection
