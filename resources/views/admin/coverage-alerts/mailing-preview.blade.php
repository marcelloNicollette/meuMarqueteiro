@extends('layouts.admin')
@section('title', 'Preview do Mailing Executivo')
@section('content')
    <div style="padding:2rem;max-width:1200px;margin:0 auto">
        <div
            style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem">
            <div>
                <a href="{{ route('admin.coverage-alerts.index') }}"
                    style="font-size:.84rem;color:#6b7280;text-decoration:none">← Voltar para a central</a>
                <h1 style="font-size:1.45rem;font-weight:700;margin-top:.45rem">Preview do mailing
                    {{ strtolower($periodLabel) }}</h1>
                <p style="font-size:.88rem;color:#6b7280;margin-top:.25rem;max-width:760px">
                    Pré-visualização aprovada do conteúdo que será enviado no próximo disparo periódico, com identidade
                    institucional e contexto executivo atual.
                </p>
            </div>
            <div style="display:flex;gap:.55rem;flex-wrap:wrap">
                <a href="{{ route('admin.coverage-alerts.ranking.export.pdf') }}"
                    style="padding:.6rem .95rem;border:1px solid #d1d5db;border-radius:8px;font-size:.84rem;color:#374151;text-decoration:none">Baixar
                    PDF</a>
                <a href="{{ route('admin.settings.index') }}"
                    style="padding:.6rem .95rem;border:1px solid #d1d5db;border-radius:8px;font-size:.84rem;color:#374151;text-decoration:none">Ajustar
                    identidade</a>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:.9fr 1.1fr;gap:1rem;margin-bottom:1.25rem">
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:1rem 1.1rem">
                <div style="font-size:.92rem;font-weight:700;color:#111827;margin-bottom:.35rem">Status de aprovação</div>
                <div style="font-size:.8rem;color:#6b7280;margin-bottom:.85rem">
                    {{ $mailingGovernance['settings']['requires_approval'] ?? false ? 'O disparo depende de aprovação manual.' : 'O disparo pode ocorrer sem aprovação manual.' }}
                </div>
                <div style="display:flex;gap:.55rem;flex-wrap:wrap;margin-bottom:.85rem">
                    <span
                        style="padding:.22rem .55rem;border-radius:999px;background:{{ $approval['approved'] ?? false ? '#ecfdf5' : '#fff7ed' }};color:{{ $approval['approved'] ?? false ? '#166534' : '#b45309' }};font-size:.74rem;font-weight:700">
                        {{ $approval['approved'] ?? false ? 'Aprovado' : 'Pendente' }}
                    </span>
                    <span
                        style="padding:.22rem .55rem;border-radius:999px;background:#f3f4f6;color:#374151;font-size:.74rem;font-weight:700">
                        Período {{ $periodLabel }}
                    </span>
                </div>
                <div style="font-size:.82rem;color:#374151;line-height:1.6">
                    @if ($approval['approved'] ?? false)
                        Aprovado por {{ $approval['approved_by_name'] ?? '—' }} ·
                        {{ $approval['approved_by_role'] ?? 'Admin' }}<br>
                        Válido até
                        {{ \Illuminate\Support\Carbon::parse($approval['approved_until'])->format('d/m/Y H:i') }}
                    @else
                        Ainda não há aprovação ativa para este período.
                    @endif
                </div>
                <div style="display:grid;gap:.55rem;margin-top:1rem">
                    <div style="padding:.7rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                        <div style="font-size:.76rem;font-weight:700;color:#111827">
                            {{ $mailingGovernance['levels']['level_one'] ?? 'Nível 1' }}</div>
                        <div style="font-size:.75rem;color:#6b7280;margin-top:.2rem">
                            @php $levelOne = $approval['level_one'] ?? []; @endphp
                            @if (
                                !empty($levelOne['approved']) &&
                                    !empty($levelOne['approved_until']) &&
                                    \Illuminate\Support\Carbon::parse($levelOne['approved_until'])->isFuture())
                                {{ $levelOne['approved_by_name'] ?? '—' }} ·
                                {{ $levelOne['approved_by_role'] ?? 'Admin' }}
                            @else
                                Pendente
                            @endif
                        </div>
                    </div>
                    @if ($mailingGovernance['settings']['two_level_approval'] ?? false)
                        <div style="padding:.7rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                            <div style="font-size:.76rem;font-weight:700;color:#111827">
                                {{ $mailingGovernance['levels']['level_two'] ?? 'Nível 2' }}</div>
                            <div style="font-size:.75rem;color:#6b7280;margin-top:.2rem">
                                @php $levelTwo = $approval['level_two'] ?? []; @endphp
                                @if (
                                    !empty($levelTwo['approved']) &&
                                        !empty($levelTwo['approved_until']) &&
                                        \Illuminate\Support\Carbon::parse($levelTwo['approved_until'])->isFuture())
                                    {{ $levelTwo['approved_by_name'] ?? '—' }} ·
                                    {{ $levelTwo['approved_by_role'] ?? 'Admin' }}
                                @else
                                    Pendente
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
                <div style="margin-top:1rem">
                    <div style="font-size:.82rem;font-weight:700;color:#111827;margin-bottom:.35rem">Destinatários</div>
                    <div style="font-size:.8rem;color:#6b7280;line-height:1.6">
                        @forelse ($recipients as $recipient)
                            <div>{{ $recipient }}</div>
                        @empty
                            <div>Nenhum destinatário configurado.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:1rem 1.1rem">
                <div style="font-size:.92rem;font-weight:700;color:#111827;margin-bottom:.35rem">Identidade e assinaturas
                </div>
                <div
                    style="padding:1rem;border-radius:10px;background:{{ data_get($payload, 'identity.accent_color', '#111827') }};color:#fff">
                    <div style="font-size:.74rem;letter-spacing:.08em;text-transform:uppercase;opacity:.82">
                        {{ data_get($payload, 'identity.department', 'Central Executiva') }}</div>
                    <div style="font-size:1.08rem;font-weight:700;margin-top:.25rem">
                        {{ data_get($payload, 'identity.institution_name', 'Meu Assistente') }}</div>
                    <div style="font-size:.78rem;opacity:.88;margin-top:.25rem">
                        {{ data_get($payload, 'identity.tagline', '') }}</div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem;margin-top:.85rem">
                    @foreach (data_get($payload, 'signatures', []) as $signature)
                        <div style="padding:.8rem;border-radius:10px;background:#fafafa;border:1px solid #e5e7eb">
                            <div style="font-size:.82rem;font-weight:700;color:#111827">
                                {{ $signature['name'] ?: 'Assinatura não definida' }}</div>
                            <div style="font-size:.75rem;color:#6b7280;margin-top:.2rem">
                                {{ $signature['role'] ?: 'Cargo não definido' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden">
            <div
                style="padding:22px 26px;background:{{ data_get($payload, 'identity.accent_color', '#111827') }};color:#fff">
                <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.82">
                    {{ data_get($payload, 'identity.department', 'Central Executiva') }}</div>
                <h2 style="margin:.45rem 0 0;font-size:1.35rem">
                    {{ data_get($payload, 'identity.institution_name', 'Meu Assistente') }}</h2>
                <div style="margin-top:.4rem;font-size:.84rem;opacity:.9">Preview do mailing {{ strtolower($periodLabel) }}
                    · {{ $payload['generated_at']->format('d/m/Y H:i') }}</div>
            </div>
            <div style="padding:1.25rem 1.4rem">
                <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;margin-bottom:1rem">
                    @foreach ([['label' => 'Score executivo médio', 'value' => ($payload['summary']['average_executive_score'] ?? 0) . '%', 'color' => '#1d4ed8'], ['label' => 'Alertas ativos', 'value' => $payload['summary']['active_alerts'] ?? 0, 'color' => '#b45309'], ['label' => 'Breaches SLA', 'value' => $payload['summary']['sla_breaches_total'] ?? 0, 'color' => '#b91c1c'], ['label' => 'Municípios no ranking', 'value' => $payload['ranking_summary']['tracked'] ?? 0, 'color' => '#111827']] as $card)
                        <div style="padding:.9rem;border-radius:10px;background:#fafafa;border:1px solid #e5e7eb">
                            <div style="font-size:1.1rem;font-weight:700;color:{{ $card['color'] }}">{{ $card['value'] }}
                            </div>
                            <div style="font-size:.76rem;color:#6b7280;margin-top:.25rem">{{ $card['label'] }}</div>
                        </div>
                    @endforeach
                </div>

                <table style="width:100%;border-collapse:collapse">
                    <thead>
                        <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                            <th style="padding:.75rem;text-align:left;font-size:.78rem;color:#6b7280">Pos.</th>
                            <th style="padding:.75rem;text-align:left;font-size:.78rem;color:#6b7280">Município</th>
                            <th style="padding:.75rem;text-align:center;font-size:.78rem;color:#6b7280">Score</th>
                            <th style="padding:.75rem;text-align:center;font-size:.78rem;color:#6b7280">Delta</th>
                            <th style="padding:.75rem;text-align:center;font-size:.78rem;color:#6b7280">SLA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (collect($payload['ranking'] ?? [])->take(10) as $row)
                            <tr style="border-bottom:1px solid #f3f4f6">
                                <td style="padding:.75rem;font-size:.82rem;font-weight:700">{{ $row['position'] }}</td>
                                <td style="padding:.75rem;font-size:.82rem">{{ $row['municipality_name'] }}</td>
                                <td style="padding:.75rem;text-align:center;font-size:.82rem">{{ $row['executive_score'] }}
                                </td>
                                <td
                                    style="padding:.75rem;text-align:center;font-size:.82rem;color:{{ ($row['executive_score_delta'] ?? 0) >= 0 ? '#166534' : '#b91c1c' }}">
                                    {{ ($row['executive_score_delta'] ?? 0) > 0 ? '+' : '' }}{{ $row['executive_score_delta'] ?? 0 }}
                                </td>
                                <td style="padding:.75rem;text-align:center;font-size:.82rem">
                                    {{ $row['sla_breaches_total'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
