@extends('layouts.admin')

@section('title', 'Dashboard')

@section('breadcrumb')
    <strong>Dashboard</strong>
@endsection

@push('styles')
    <style>
        /* ── Stat cards ─── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.75rem;
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.3rem 1.4rem;
            position: relative;
            overflow: hidden;
            transition: box-shadow .2s;
        }

        .stat-card:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, .06);
        }

        .stat-card-accent {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .stat-eyebrow {
            font-size: .7rem;
            font-weight: 500;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--ink-muted);
            margin-bottom: .75rem;
        }

        .stat-value {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            color: var(--ink);
            line-height: 1;
            margin-bottom: .3rem;
        }

        .stat-delta {
            font-size: .78rem;
            display: flex;
            align-items: center;
            gap: .25rem;
        }

        .stat-delta.up {
            color: var(--green);
        }

        .stat-delta.neutral {
            color: var(--ink-muted);
        }

        /* ── Grid principal ─── */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 1.25rem;
        }

        /* ── Tabela de municípios ─── */
        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: .84rem;
        }

        thead th {
            padding: .65rem 1rem;
            text-align: left;
            font-size: .7rem;
            font-weight: 500;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--ink-muted);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid var(--border-lt);
            transition: background .1s;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody tr:hover {
            background: var(--cream);
        }

        tbody td {
            padding: .85rem 1rem;
            color: var(--ink-soft);
            vertical-align: middle;
        }

        tbody td:first-child {
            color: var(--ink);
            font-weight: 500;
        }

        .mun-flag {
            display: flex;
            align-items: center;
            gap: .7rem;
        }

        .mun-avatar {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--gold-bg);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Playfair Display', serif;
            font-size: .8rem;
            color: var(--gold);
            font-weight: 600;
            flex-shrink: 0;
        }

        /* ── Coluna direita ─── */
        .side-col {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        /* ── Onboarding list ─── */
        .onboarding-item {
            display: flex;
            align-items: center;
            gap: .85rem;
            padding: .85rem 0;
            border-bottom: 1px solid var(--border-lt);
        }

        .onboarding-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .onboarding-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .onboarding-dot.pending {
            background: #f59e0b;
        }

        .onboarding-dot.in-progress {
            background: var(--blue);
        }

        .onboarding-name {
            font-size: .84rem;
            font-weight: 500;
            color: var(--ink);
        }

        .onboarding-status {
            font-size: .75rem;
            color: var(--ink-muted);
            margin-top: .1rem;
        }

        .onboarding-action {
            margin-left: auto;
            font-size: .77rem;
            color: var(--gold);
            text-decoration: none;
            font-weight: 500;
        }

        /* ── Uso da IA ─── */
        .usage-row {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .7rem 0;
            border-bottom: 1px solid var(--border-lt);
            font-size: .84rem;
        }

        .usage-row:last-child {
            border-bottom: none;
        }

        .usage-name {
            flex: 1;
            color: var(--ink);
        }

        .usage-bar-wrap {
            width: 80px;
            height: 4px;
            background: var(--border);
            border-radius: 2px;
        }

        .usage-bar {
            height: 100%;
            border-radius: 2px;
            background: var(--gold);
            transition: width .4s;
        }

        .usage-count {
            font-size: .78rem;
            color: var(--ink-muted);
            min-width: 40px;
            text-align: right;
        }

        @media (max-width: 1100px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')

    <div class="page-header">
        <h1>Visão Geral</h1>
        <p>{{ now()->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</p>
    </div>

    {{-- ── Estatísticas ─────────────────────────────────────────── --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-accent" style="background: var(--gold)"></div>
            <div class="stat-eyebrow">Municípios ativos</div>
            <div class="stat-value">{{ $stats['active_subscriptions'] }}</div>
            <div class="stat-delta neutral">de {{ $stats['total_municipalities'] }} cadastrados</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-accent" style="background: var(--blue)"></div>
            <div class="stat-eyebrow">Em onboarding</div>
            <div class="stat-value">{{ $stats['onboarding_pending'] }}</div>
            <div class="stat-delta neutral">aguardando configuração</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-accent" style="background: var(--green)"></div>
            <div class="stat-eyebrow">Prefeitos ativos hoje</div>
            <div class="stat-value">{{ $stats['mayors_active_today'] }}</div>
            <div class="stat-delta up">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M7 14l5-5 5 5z" />
                </svg>
                de {{ $stats['mayors_total'] }} total
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-accent" style="background: var(--orange)"></div>
            <div class="stat-eyebrow">Briefings hoje</div>
            <div class="stat-value">{{ $stats['active_subscriptions'] }}</div>
            <div class="stat-delta neutral">gerados às 6h30</div>
        </div>
    </div>

    {{-- ── Grid principal ───────────────────────────────────────── --}}
    <div class="dashboard-grid">

        {{-- Municípios recentes --}}
        <div class="card">
            <div class="card-header">
                <h3>Municípios cadastrados</h3>
                <a href="{{ route('admin.municipalities.index') }}" class="btn btn-outline">
                    Ver todos
                </a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Município</th>
                            <th>Estado</th>
                            <th>Plano</th>
                            <th>Status</th>
                            <th>Última sincronização</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentMunicipalities as $mun)
                            <tr>
                                <td>
                                    <div class="mun-flag">
                                        <div class="mun-avatar">{{ strtoupper(substr($mun->name, 0, 2)) }}</div>
                                        <div>
                                            <div>{{ $mun->name }}</div>
                                            <div style="font-size:.75rem;color:var(--ink-muted)">
                                                {{ $mun->mayor->name ?? '—' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $mun->state_code }}</td>
                                <td>
                                    <span
                                        class="badge {{ $mun->subscription_tier === 'parceiro' ? 'badge-gold' : ($mun->subscription_tier === 'estrategico' ? 'badge-blue' : 'badge-green') }}">
                                        {{ $mun->getTierLabel() }}
                                    </span>
                                </td>
                                <td>
                                    @if ($mun->onboarding_status === 'completed')
                                        <span class="badge badge-green">Ativo</span>
                                    @elseif($mun->onboarding_status === 'in_progress')
                                        <span class="badge badge-blue">Em onboarding</span>
                                    @else
                                        <span class="badge badge-orange">Pendente</span>
                                    @endif
                                </td>
                                <td>{{ $mun->data_last_synced_at ? $mun->data_last_synced_at->diffForHumans() : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align:center;padding:2rem;color:var(--ink-muted)">
                                    Nenhum município cadastrado ainda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Coluna direita --}}
        <div class="side-col">

            {{-- Onboarding em andamento --}}
            <div class="card">
                <div class="card-header">
                    <h3>Onboarding em andamento</h3>
                </div>
                <div class="card-body" style="padding-top:.5rem;padding-bottom:.5rem">
                    @forelse($onboardingInProgress as $mun)
                        <div class="onboarding-item">
                            <div
                                class="onboarding-dot {{ $mun->onboarding_status === 'pending' ? 'pending' : 'in-progress' }}">
                            </div>
                            <div>
                                <div class="onboarding-name">{{ $mun->name }}</div>
                                <div class="onboarding-status">
                                    {{ $mun->onboarding_status === 'pending' ? 'Aguardando início' : 'Em configuração' }}
                                </div>
                            </div>
                            <a href="{{ route('admin.municipalities.onboarding.show', $mun) }}" class="onboarding-action">
                                Configurar →
                            </a>
                        </div>
                    @empty
                        <p style="font-size:.84rem;color:var(--ink-muted);padding:.5rem 0">
                            Nenhum onboarding pendente.
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- Ações rápidas --}}
            <div class="card">
                <div class="card-header">
                    <h3>Ações rápidas</h3>
                </div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:.6rem">
                    <a href="{{ route('admin.municipalities.create') }}" class="btn btn-dark"
                        style="justify-content:center">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                        </svg>
                        Novo município
                    </a>
                    <a href="{{ route('admin.knowledge-base.index') }}" class="btn btn-outline"
                        style="justify-content:center">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z" />
                        </svg>
                        Atualizar base de conhecimento
                    </a>
                    <a href="{{ route('admin.integrations.index') }}" class="btn btn-outline"
                        style="justify-content:center">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z" />
                        </svg>
                        Sincronizar dados públicos
                    </a>
                </div>
            </div>

        </div>
    </div>

@endsection
