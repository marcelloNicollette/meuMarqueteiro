@extends('layouts.admin')
@section('title', $municipality->name)
@section('content')
    <div style="padding:2rem;max-width:900px">
        @if (session('success'))
            <div
                style="background:#d1fae5;border:1px solid #6ee7b7;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#065f46">
                {{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div
                style="background:#fef2f2;border:1px solid #fca5a5;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#991b1b">
                {{ session('error') }}</div>
        @endif

        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem">
            <div>
                <a href="{{ route('admin.municipalities.index') }}"
                    style="font-size:.85rem;color:#6b7280;text-decoration:none">← Municípios</a>
                <h1 style="font-size:1.4rem;font-weight:700;margin-top:.5rem">{{ $municipality->name }}</h1>
                <p style="color:#6b7280;font-size:.88rem">{{ $municipality->state }} · IBGE {{ $municipality->ibge_code }}
                </p>
            </div>
            <div style="display:flex;gap:.75rem">
                <a href="{{ route('admin.municipalities.edit', $municipality) }}"
                    style="padding:.6rem 1rem;border:1px solid #d1d5db;border-radius:8px;font-size:.85rem;text-decoration:none;color:#374151">Editar</a>
                <a href="{{ route('admin.municipalities.contact-areas.index', $municipality) }}"
                    style="padding:.6rem 1rem;border:1px solid #d1d5db;border-radius:8px;font-size:.85rem;text-decoration:none;color:#374151">Áreas
                    de contato</a>
                @if ($municipality->onboarding_status !== 'completed')
                    <a href="{{ route('admin.municipalities.onboarding.show', $municipality) }}"
                        style="padding:.6rem 1rem;background:var(--gold);color:#fff;border-radius:8px;font-size:.85rem;text-decoration:none;font-weight:600">Onboarding</a>
                @endif
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;margin-bottom:1.5rem">
            @foreach (['Compromissos' => $stats['commitments_total'], 'Entregues' => $stats['commitments_done'], 'Em risco' => $stats['commitments_at_risk'], 'Conversas' => $stats['conversations_total'], 'Conteúdos' => $stats['contents_generated']] as $label => $val)
                <div style="background:#fff;padding:1rem;border-radius:10px;border:1px solid #e5e7eb;text-align:center">
                    <div style="font-size:1.5rem;font-weight:700">{{ $val }}</div>
                    <div style="font-size:.75rem;color:#6b7280;margin-top:.2rem">{{ $label }}</div>
                </div>
            @endforeach
        </div>

        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb">
            <h3 style="font-size:.95rem;font-weight:600;margin-bottom:1rem">Informações</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;font-size:.88rem">
                <div><span style="color:#9ca3af">Prefeito:</span> {{ $municipality->mayor?->name ?? '—' }}</div>
                <div><span style="color:#9ca3af">E-mail:</span> {{ $municipality->mayor?->email ?? '—' }}</div>
                <div><span style="color:#9ca3af">Plano:</span> {{ $municipality->getTierLabel() }}</div>
                <div><span style="color:#9ca3af">Status:</span> {{ $municipality->onboarding_status }}</div>
                <div><span style="color:#9ca3af">População:</span>
                    {{ $municipality->population ? number_format($municipality->population, 0, ',', '.') : '—' }}</div>
                <div><span style="color:#9ca3af">IDHM:</span> {{ $municipality->idhm ?? '—' }}</div>
            </div>
        </div>

        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-top:1rem">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1rem">
                <div>
                    <h3 style="font-size:.95rem;font-weight:600;margin-bottom:.35rem">Prontidão consolidada de Configurações
                    </h3>
                    <p style="font-size:.84rem;color:#6b7280">Leitura operacional da cobertura mínima de `Configurações`,
                        `Menções` e `Pra hoje`.</p>
                </div>
                <div
                    style="padding:.45rem .7rem;border-radius:999px;background:{{ $configurationSummary['status'] === 'ok' ? '#ecfdf5' : ($configurationSummary['status'] === 'warning' ? '#fff7ed' : '#fef2f2') }};color:{{ $configurationSummary['status'] === 'ok' ? '#166534' : ($configurationSummary['status'] === 'warning' ? '#b45309' : '#b91c1c') }};font-size:.78rem;font-weight:700">
                    {{ $configurationSummary['summary_label'] }}
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;margin-bottom:1rem">
                <div style="padding:1rem;border-radius:10px;background:#f8fafc;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Score</div>
                    <div style="font-size:1.35rem;font-weight:700;margin-top:.2rem">{{ $configurationSummary['score'] }}%
                    </div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Prontidão geral</div>
                </div>
                <div style="padding:1rem;border-radius:10px;background:#f8fafc;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Canais</div>
                    <div style="font-size:1.35rem;font-weight:700;margin-top:.2rem">
                        {{ count($configurationSummary['active_channels'] ?? []) }}</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Ativos em comunicação</div>
                </div>
                <div style="padding:1rem;border-radius:10px;background:#f8fafc;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Termos</div>
                    <div style="font-size:1.35rem;font-weight:700;margin-top:.2rem">
                        {{ count($configurationSummary['monitoring_terms'] ?? []) }}</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Cobertura de menções</div>
                </div>
                <div style="padding:1rem;border-radius:10px;background:#f8fafc;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Keywords</div>
                    <div style="font-size:1.35rem;font-weight:700;margin-top:.2rem">
                        {{ $configurationSummary['monitoring_keywords_total'] ?? 0 }}</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Palavras-chave ativas</div>
                </div>
                <div style="padding:1rem;border-radius:10px;background:#f8fafc;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Pra hoje</div>
                    <div style="font-size:1rem;font-weight:700;margin-top:.35rem">
                        {{ $configurationSummary['pra_hoje_time'] ?? 'Pendente' }}</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">
                        {{ $configurationSummary['pra_hoje_enabled'] ?? false ? 'Briefing ativo' : 'Briefing desativado' }}
                    </div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem">
                @foreach ($configurationSummary['checklist'] as $item)
                    <div
                        style="padding:1rem;border-radius:10px;border:1px solid {{ $item['ready'] ? '#bbf7d0' : '#e5e7eb' }};background:{{ $item['ready'] ? '#f0fdf4' : '#fafafa' }}">
                        <div style="font-size:.8rem;font-weight:700;color:{{ $item['ready'] ? '#166534' : '#374151' }}">
                            {{ $item['label'] }}</div>
                        <div style="font-size:.76rem;color:#6b7280;margin-top:.25rem">
                            {{ $item['ready'] ? 'Cobertura mínima atendida' : 'Revisão necessária' }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-top:1rem">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1rem">
                <div>
                    <h3 style="font-size:.95rem;font-weight:600;margin-bottom:.35rem">Alertas automáticos de cobertura</h3>
                    <p style="font-size:.84rem;color:#6b7280">Disparados automaticamente quando `Menções`, `Pra hoje` ou
                        `Configurações` perdem a cobertura mínima esperada.</p>
                </div>
                <a href="{{ route('admin.municipalities.onboarding.show', $municipality) }}"
                    style="padding:.55rem .9rem;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem;text-decoration:none;color:#374151">Revisar
                    onboarding</a>
            </div>

            @if ($activeCoverageAlerts->isNotEmpty())
                <div style="display:grid;gap:.75rem;margin-bottom:1rem">
                    @foreach ($activeCoverageAlerts as $alert)
                        <div
                            style="padding:1rem;border-radius:12px;border:1px solid {{ $alert->severity === 'high' ? '#fecaca' : '#fed7aa' }};background:{{ $alert->severity === 'high' ? '#fef2f2' : '#fff7ed' }}">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem">
                                <div>
                                    <div
                                        style="font-size:.86rem;font-weight:700;color:{{ $alert->severity === 'high' ? '#b91c1c' : '#b45309' }}">
                                        {{ $alert->title }}</div>
                                    <div style="font-size:.8rem;color:#6b7280;margin-top:.25rem;line-height:1.55">
                                        {{ $alert->message }}</div>
                                </div>
                                <div style="font-size:.74rem;color:#6b7280;text-align:right">
                                    <div>{{ optional($alert->last_detected_at)->format('d/m/Y H:i') }}</div>
                                    <div style="margin-top:.2rem">{{ strtoupper($alert->severity) }}</div>
                                </div>
                            </div>
                            @if ($alert->action_url)
                                <div style="margin-top:.75rem">
                                    <a href="{{ $alert->action_url }}"
                                        style="font-size:.8rem;font-weight:600;color:#374151;text-decoration:none">Abrir
                                        ação recomendada →</a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div
                    style="padding:1rem;border-radius:12px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;font-size:.84rem;margin-bottom:1rem">
                    Nenhum alerta ativo. Este município está com cobertura mínima preservada nas frentes monitoradas.
                </div>
            @endif

            <div style="padding-top:1rem;border-top:1px solid #f3f4f6">
                <div style="font-size:.8rem;font-weight:700;color:#374151;margin-bottom:.6rem">Histórico recente</div>
                <div style="display:grid;gap:.55rem">
                    @forelse ($recentCoverageAlerts as $alert)
                        <div style="display:flex;justify-content:space-between;gap:1rem;font-size:.8rem;color:#4b5563">
                            <span>{{ $alert->title }} <span style="color:#9ca3af">·
                                    {{ $alert->status === 'active' ? 'ativo' : 'resolvido' }}</span></span>
                            <span
                                style="color:#9ca3af">{{ optional($alert->last_detected_at)->format('d/m/Y H:i') }}</span>
                        </div>
                    @empty
                        <div style="font-size:.8rem;color:#9ca3af">Nenhum evento de cobertura registrado ainda.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-top:1rem">
            <div
                style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1rem;flex-wrap:wrap">
                <div>
                    <h3 style="font-size:.95rem;font-weight:600;margin-bottom:.35rem">Governança executiva e thresholds</h3>
                    <p style="font-size:.84rem;color:#6b7280">Define quando a plataforma deve sinalizar piora automática
                        deste município no ranking executivo.</p>
                </div>
                <a href="{{ route('admin.coverage-alerts.municipality', $municipality) }}"
                    style="padding:.55rem .9rem;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem;text-decoration:none;color:#374151">Abrir
                    drill-down</a>
            </div>

            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1rem">
                @foreach ([
            ['label' => 'Config. mínima', 'value' => $coverageGovernance['minimum_configuration_score'] . '%', 'note' => 'Threshold de prontidão'],
            ['label' => 'Score executivo mínimo', 'value' => $coverageGovernance['minimum_executive_score'], 'note' => 'Threshold do ranking'],
            ['label' => 'Queda máxima', 'value' => $coverageGovernance['maximum_negative_score_delta'] . ' pts', 'note' => 'Delta negativo tolerado'],
            ['label' => 'Perda máxima de posição', 'value' => $coverageGovernance['maximum_position_loss'], 'note' => 'Posições toleradas'],
            ['label' => 'Alertas ativos', 'value' => $coverageGovernance['maximum_active_alerts'], 'note' => 'Teto aceitável'],
            ['label' => 'Breaches SLA', 'value' => $coverageGovernance['maximum_sla_breaches'], 'note' => 'Teto de violações'],
            ['label' => 'Status', 'value' => $coverageGovernance['enabled'] ? 'Ativo' : 'Desligado', 'note' => 'Piora automática'],
            ['label' => 'Cobertura atual', 'value' => $activeCoverageAlerts->where('event_type', 'executive_ranking_worsened')->count(), 'note' => 'Alertas de piora ativos'],
        ] as $card)
                    <div style="padding:1rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                        <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">
                            {{ $card['label'] }}</div>
                        <div style="font-size:1.2rem;font-weight:700;margin-top:.25rem">{{ $card['value'] }}</div>
                        <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">{{ $card['note'] }}</div>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('admin.municipalities.coverage-governance', $municipality) }}"
                style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.85rem;align-items:end">
                @csrf
                <label style="display:flex;align-items:center;gap:.5rem;font-size:.84rem;color:#111827;grid-column:1/-1">
                    <input type="checkbox" name="enabled" value="1"
                        {{ $coverageGovernance['enabled'] ? 'checked' : '' }}>
                    Ativar piora automática e thresholds executivos para este município
                </label>
                <div>
                    <label
                        style="display:block;font-size:.8rem;font-weight:600;color:#6b7280;margin-bottom:.35rem">Configuração
                        mínima</label>
                    <input type="number" name="minimum_configuration_score" min="0" max="100"
                        value="{{ $coverageGovernance['minimum_configuration_score'] }}"
                        style="width:100%;padding:.62rem .75rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.84rem">
                </div>
                <div>
                    <label style="display:block;font-size:.8rem;font-weight:600;color:#6b7280;margin-bottom:.35rem">Score
                        executivo mínimo</label>
                    <input type="number" name="minimum_executive_score" min="0" max="100"
                        value="{{ $coverageGovernance['minimum_executive_score'] }}"
                        style="width:100%;padding:.62rem .75rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.84rem">
                </div>
                <div>
                    <label style="display:block;font-size:.8rem;font-weight:600;color:#6b7280;margin-bottom:.35rem">Queda
                        máxima de score</label>
                    <input type="number" name="maximum_negative_score_delta" min="0" max="100"
                        value="{{ $coverageGovernance['maximum_negative_score_delta'] }}"
                        style="width:100%;padding:.62rem .75rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.84rem">
                </div>
                <div>
                    <label style="display:block;font-size:.8rem;font-weight:600;color:#6b7280;margin-bottom:.35rem">Perda
                        máxima de posição</label>
                    <input type="number" name="maximum_position_loss" min="0" max="50"
                        value="{{ $coverageGovernance['maximum_position_loss'] }}"
                        style="width:100%;padding:.62rem .75rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.84rem">
                </div>
                <div>
                    <label style="display:block;font-size:.8rem;font-weight:600;color:#6b7280;margin-bottom:.35rem">Máximo
                        de alertas ativos</label>
                    <input type="number" name="maximum_active_alerts" min="0" max="100"
                        value="{{ $coverageGovernance['maximum_active_alerts'] }}"
                        style="width:100%;padding:.62rem .75rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.84rem">
                </div>
                <div>
                    <label style="display:block;font-size:.8rem;font-weight:600;color:#6b7280;margin-bottom:.35rem">Máximo
                        de breaches SLA</label>
                    <input type="number" name="maximum_sla_breaches" min="0" max="100"
                        value="{{ $coverageGovernance['maximum_sla_breaches'] }}"
                        style="width:100%;padding:.62rem .75rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.84rem">
                </div>
                <div style="grid-column:1/-1;display:flex;justify-content:flex-end">
                    <button type="submit"
                        style="padding:.68rem 1rem;background:#111827;color:#fff;border:none;border-radius:8px;font-size:.84rem;font-weight:600;cursor:pointer">
                        Salvar thresholds
                    </button>
                </div>
            </form>
        </div>

        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-top:1rem">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1rem">
                <div>
                    <h3 style="font-size:.95rem;font-weight:600;margin-bottom:.35rem">Banco de Projetos</h3>
                    <p style="font-size:.84rem;color:#6b7280">Curadoria operacional da biblioteca e reexecução manual por
                        município.</p>
                </div>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                    <a href="{{ route('admin.municipalities.onboarding.show', $municipality) }}"
                        style="padding:.55rem .9rem;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem;text-decoration:none;color:#374151">Abrir
                        onboarding</a>
                    @if ($municipality->subscription_active && $municipality->onboarding_status === 'completed')
                        <form method="POST"
                            action="{{ route('admin.municipalities.project-bank.refresh', $municipality) }}">
                            @csrf
                            <button type="submit"
                                style="padding:.55rem .9rem;background:#111827;color:#fff;border:none;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer">
                                Reexecutar curadoria
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem">
                <div style="padding:1rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Teses</div>
                    <div style="font-size:1.3rem;font-weight:700;margin-top:.2rem">
                        {{ $projectBankSummary['library_size'] }}</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Biblioteca atual</div>
                </div>
                <div style="padding:1rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Bootstrap</div>
                    <div style="font-size:1rem;font-weight:700;margin-top:.2rem">
                        {{ !empty($projectBankSummary['bootstrapped_at']) ? \Illuminate\Support\Carbon::parse($projectBankSummary['bootstrapped_at'])->format('d/m/Y H:i') : 'Pendente' }}
                    </div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Carga inicial do Banco</div>
                </div>
                <div style="padding:1rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Última curadoria
                    </div>
                    <div style="font-size:1rem;font-weight:700;margin-top:.2rem">
                        {{ !empty($projectBankSummary['last_curated_at']) ? \Illuminate\Support\Carbon::parse($projectBankSummary['last_curated_at'])->format('d/m/Y H:i') : 'Ainda não executada' }}
                    </div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Último refresh consolidado</div>
                </div>
                <div style="padding:1rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Status</div>
                    <div
                        style="font-size:1rem;font-weight:700;margin-top:.2rem;color:{{ $projectBankSummary['status_tone'] === 'warning' ? '#b45309' : ($projectBankSummary['status_tone'] === 'success' ? '#065f46' : '#374151') }}">
                        {{ $projectBankSummary['status_label'] }}
                    </div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">
                        {{ $projectBankSummary['refresh_reason'] ?: 'Sem pendências operacionais registradas.' }}
                    </div>
                </div>
            </div>
            <div
                style="padding:1rem;border-radius:10px;background:#f8fafc;border:1px solid #e5e7eb;margin-top:1rem;font-size:.82rem;color:#374151;line-height:1.55">
                Use `Reexecutar curadoria` quando o município receber novo material estratégico, quando o onboarding for
                revisado
                ou quando a equipe quiser recompor a biblioteca imediatamente sem esperar o ciclo diário.
            </div>
        </div>

        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-top:1rem">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1rem">
                <div>
                    <h3 style="font-size:.95rem;font-weight:600;margin-bottom:.35rem">Resolve ai</h3>
                    <p style="font-size:.84rem;color:#6b7280">Configuração operacional atual do módulo para este município.
                    </p>
                </div>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                    <a href="{{ route('admin.municipalities.contact-areas.index', $municipality) }}"
                        style="padding:.55rem .9rem;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem;text-decoration:none;color:#374151">Secretarias</a>
                    <a href="{{ route('admin.municipalities.localities.index', $municipality) }}"
                        style="padding:.55rem .9rem;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem;text-decoration:none;color:#374151">Localidades</a>
                    <a href="{{ route('admin.municipalities.onboarding.show', $municipality) }}"
                        style="padding:.55rem .9rem;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem;text-decoration:none;color:#374151">Abrir
                        onboarding</a>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem">
                <div style="padding:1rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Alta</div>
                    <div style="font-size:1.3rem;font-weight:700;margin-top:.2rem">
                        {{ $resolveAiSettings['priority_hours']['alta'] ?? 48 }}h</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Prazo padrão</div>
                </div>
                <div style="padding:1rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Média</div>
                    <div style="font-size:1.3rem;font-weight:700;margin-top:.2rem">
                        {{ $resolveAiSettings['priority_hours']['media'] ?? 168 }}h</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Prazo padrão</div>
                </div>
                <div style="padding:1rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Baixa</div>
                    <div style="font-size:1.3rem;font-weight:700;margin-top:.2rem">
                        {{ $resolveAiSettings['priority_hours']['baixa'] ?? 360 }}h</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Prazo padrão</div>
                </div>
                <div style="padding:1rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Alerta</div>
                    <div style="font-size:1.3rem;font-weight:700;margin-top:.2rem">
                        {{ $resolveAiSettings['alert_lead_hours'] ?? 24 }}h</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Antes do vencimento</div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem;font-size:.85rem">
                <div style="padding:1rem;border-radius:10px;background:#fafafa;border:1px solid #e5e7eb">
                    <div style="font-weight:600;margin-bottom:.45rem">Canais ativos</div>
                    <div style="color:#374151">Interno:
                        {{ $resolveAiSettings['channels']['internal'] ?? false ? 'Sim' : 'Não' }}</div>
                    <div style="color:#374151">E-mail:
                        {{ $resolveAiSettings['channels']['email'] ?? false ? 'Sim' : 'Não' }}</div>
                    <div style="color:#9ca3af">WhatsApp:
                        {{ $resolveAiSettings['channels']['whatsapp'] ?? false ? 'Preparado' : 'Desligado' }}</div>
                </div>
                <div style="padding:1rem;border-radius:10px;background:#fafafa;border:1px solid #e5e7eb">
                    <div style="font-weight:600;margin-bottom:.45rem">Comprovante obrigatório</div>
                    <div style="color:#374151">
                        {{ collect($resolveAiSettings['attachment_required_priorities'] ?? ['alta'])->map(fn($item) => ucfirst($item))->implode(', ') }}
                    </div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-top:1rem">
                <div style="padding:1rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Secretarias</div>
                    <div style="font-size:1.3rem;font-weight:700;margin-top:.2rem">{{ $stats['contact_areas_total'] }}
                    </div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">{{ $stats['contact_areas_ready'] }}
                        prontas para receber notificação</div>
                </div>
                <div style="padding:1rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Localidades</div>
                    <div style="font-size:1.3rem;font-weight:700;margin-top:.2rem">{{ $stats['localities_total'] }}</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Base territorial usada no formulário do
                        Resolve ai</div>
                </div>
                <div style="padding:1rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Prontidão</div>
                    <div style="font-size:1.3rem;font-weight:700;margin-top:.2rem">
                        {{ $stats['contact_areas_ready'] > 0 && $stats['localities_total'] > 0 ? 'OK' : 'Pendente' }}</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Secretaria com canal + base mínima de
                        localidades</div>
                </div>
            </div>
        </div>

        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-top:1rem">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1rem">
                <div>
                    <h3 style="font-size:.95rem;font-weight:600;margin-bottom:.35rem">Comunicação</h3>
                    <p style="font-size:.84rem;color:#6b7280">Configuração atual do SLA editorial por etapa para este
                        município.</p>
                </div>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                    <a href="{{ route('admin.municipalities.onboarding.show', $municipality) }}"
                        style="padding:.55rem .9rem;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem;text-decoration:none;color:#374151">Abrir
                        onboarding</a>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem">
                <div style="padding:1rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Revisão inicial
                    </div>
                    <div style="font-size:1.3rem;font-weight:700;margin-top:.2rem">
                        {{ $communicationSettings['sla']['draft_review_hours'] ?? 24 }}h</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Tempo para tirar do rascunho e revisar
                    </div>
                </div>
                <div style="padding:1rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Aprovado para
                        publicar</div>
                    <div style="font-size:1.3rem;font-weight:700;margin-top:.2rem">
                        {{ $communicationSettings['sla']['approved_publish_hours'] ?? 24 }}h</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Janela entre aprovação e publicação</div>
                </div>
                <div style="padding:1rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Antecedência do
                        agendado</div>
                    <div style="font-size:1.3rem;font-weight:700;margin-top:.2rem">
                        {{ $communicationSettings['sla']['scheduled_lead_hours'] ?? 6 }}h</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Margem de segurança para execução da
                        agenda
                    </div>
                </div>
            </div>
            <div
                style="padding:1rem;border-radius:10px;background:#f8fafc;border:1px solid #e5e7eb;margin-top:1rem;font-size:.82rem;color:#374151;line-height:1.55">
                Esses valores abastecem o painel de SLA do módulo `Comunicação`, incluindo leitura por etapa, fila crítica
                de vencimento e prioridade operacional na fila editorial.
            </div>
        </div>
    </div>
@endsection
