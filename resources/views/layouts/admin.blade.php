<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Meu Assistente</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" type="image/x-icon" href="/images/logo-borda-black.png">
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap"
        rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --noite: #111318;
            --mandato: #1c243a;
            --gestao: #373e50;
            --apoio: #80869a;
            --clareza: #f6f4f0;
            --ouro: #b8902a;
            --acao: #d97706;
            --institucional: #1a5fa8;
            --desenvolvimento: #1e7e48;
            --ink: var(--noite);
            --ink-soft: var(--gestao);
            --ink-muted: var(--apoio);
            --gold: var(--ouro);
            --gold-lt: #d1af5b;
            --gold-bg: #fdf8ee;
            --cream: var(--clareza);
            --white: #ffffff;
            --border: #e1ddd6;
            --border-lt: #ece7df;
            --sidebar-w: 274px;
            --green: var(--desenvolvimento);
            --green-bg: #edf7f1;
            --orange: var(--acao);
            --orange-bg: #fff3e8;
            --red: #b52b2b;
            --red-bg: #fdf0f0;
            --blue: var(--institucional);
            --blue-bg: #eff5ff;
            --shadow-soft: 0 18px 44px rgba(17, 19, 24, 0.08);
        }

        html,
        body {
            height: 100%;
            font-family: "Inter", sans-serif;
            background: var(--cream);
            color: var(--ink);
        }

        body {
            background:
                radial-gradient(circle at top right, rgba(184, 144, 42, 0.07), transparent 24%),
                linear-gradient(180deg, #faf8f4 0%, var(--cream) 100%);
        }

        /* ── Sidebar ──────────────────────────────────────────── */
        .sidebar {
            position: fixed;
            top: 16px;
            left: 16px;
            bottom: 16px;
            width: var(--sidebar-w);
            background: linear-gradient(180deg, var(--noite) 0%, var(--mandato) 100%);
            display: flex;
            flex-direction: column;
            z-index: 100;
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, .06);
            box-shadow: 0 28px 50px rgba(17, 19, 24, 0.22);
        }

        .sidebar-logo {
            padding: 1.55rem 1.4rem 1.35rem;
            display: flex;
            align-items: center;
            gap: .8rem;
            border-bottom: 1px solid rgba(255, 255, 255, .06);
        }

        .sidebar-logo-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, rgba(184, 144, 42, 0.2) 0%, rgba(184, 144, 42, 0.35) 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sidebar-logo-icon img {
            width: 28px;
            height: 28px;
        }

        .sidebar-logo-text {
            font-family: "Outfit", sans-serif;
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--clareza);
        }

        .sidebar-badge {
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            background: rgba(184, 144, 42, 0.18);
            color: var(--gold-lt);
            padding: .2rem .48rem;
            border-radius: 999px;
            margin-left: .3rem;
        }

        .sidebar-section {
            padding: 1.2rem 1rem .4rem;
        }

        .sidebar-section-label {
            font-size: .62rem;
            font-weight: 600;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: rgba(246, 244, 240, 0.28);
            padding: 0 .4rem;
            margin-bottom: .3rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .78rem .85rem;
            border-radius: 16px;
            font-size: .87rem;
            color: rgba(246, 244, 240, 0.62);
            text-decoration: none;
            transition: background .15s, color .15s, transform .15s;
            margin-bottom: .18rem;
            border: 1px solid transparent;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, .06);
            color: var(--clareza);
            transform: translateX(2px);
        }

        .nav-item.active {
            background: rgba(184, 144, 42, .16);
            color: var(--gold-lt);
            border-color: rgba(184, 144, 42, 0.18);
            box-shadow: inset 0 0 0 1px rgba(184, 144, 42, 0.2);
        }

        .nav-item svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            opacity: .7;
        }

        .nav-item.active svg {
            opacity: 1;
        }

        .nav-item .nav-badge {
            margin-left: auto;
            background: var(--gold);
            color: var(--noite);
            font-size: .65rem;
            font-weight: 600;
            padding: .15rem .45rem;
            border-radius: 999px;
        }

        .sidebar-spacer {
            flex: 1;
        }

        .sidebar-user {
            padding: 1rem 1.2rem;
            border-top: 1px solid rgba(255, 255, 255, .06);
            display: flex;
            align-items: center;
            gap: .7rem;
        }

        .sidebar-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Outfit", sans-serif;
            font-size: .9rem;
            color: var(--clareza);
            font-weight: 600;
            flex-shrink: 0;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sidebar-user-name {
            font-size: .82rem;
            font-weight: 500;
            color: rgba(255, 255, 255, .78);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }

        .sidebar-user-role {
            font-size: .7rem;
            color: rgba(255, 255, 255, .4);
            margin-top: .05rem;
        }

        .sidebar-logout {
            color: rgba(246, 244, 240, 0.54);
            text-decoration: none;
            transition: color .15s;
        }

        .sidebar-logout:hover {
            color: rgba(255, 255, 255, .85);
        }

        .sidebar-logout svg {
            width: 15px;
            height: 15px;
        }

        /* ── Main ─────────────────────────────────────────────── */
        .main {
            margin-left: calc(var(--sidebar-w) + 32px);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 16px 18px 16px 0;
        }

        .topbar {
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(225, 221, 214, 0.9);
            padding: 1rem 1.4rem;
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 16px;
            z-index: 50;
            border-radius: 24px;
            backdrop-filter: blur(16px);
            box-shadow: var(--shadow-soft);
            gap: 1rem;
            flex-wrap: wrap;
        }

        .topbar-breadcrumb {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .84rem;
            color: var(--ink-muted);
        }

        .topbar-breadcrumb strong {
            color: var(--mandato);
            font-weight: 600;
            font-family: "Outfit", sans-serif;
            font-size: 1.15rem;
        }

        .topbar-breadcrumb span {
            opacity: .4;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .topbar-search {
            display: flex;
            align-items: center;
            gap: .5rem;
            background: #fbfaf7;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: .62rem .85rem;
        }

        .topbar-search svg {
            width: 14px;
            height: 14px;
            color: var(--ink-muted);
        }

        .topbar-search input {
            background: none;
            border: none;
            outline: none;
            font-family: "Inter", sans-serif;
            font-size: .83rem;
            color: var(--ink);
            width: 180px;
        }

        .topbar-search input::placeholder {
            color: var(--ink-muted);
        }

        .content {
            padding: 1rem 0 0;
            flex: 1;
        }

        /* ── Componentes reutilizáveis ────────────────────────── */
        .page-header {
            margin-bottom: 1.75rem;
        }

        .page-header h1 {
            font-family: "Outfit", sans-serif;
            font-size: 1.9rem;
            font-weight: 600;
            color: var(--mandato);
            margin-bottom: .35rem;
            letter-spacing: -0.02em;
        }

        .page-header p {
            font-size: .87rem;
            color: var(--ink-muted);
        }

        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 12px 28px rgba(17, 19, 24, 0.05);
        }

        .card-header {
            padding: 1.1rem 1.35rem;
            border-bottom: 1px solid var(--border-lt);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h3 {
            font-size: .95rem;
            font-weight: 600;
            color: var(--mandato);
            font-family: "Outfit", sans-serif;
        }

        .card-body {
            padding: 1.4rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .72rem 1.05rem;
            border-radius: 14px;
            font-family: "Inter", sans-serif;
            font-size: .84rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all .15s;
        }

        .btn svg {
            width: 14px;
            height: 14px;
        }

        .btn-dark {
            background: var(--mandato);
            color: var(--clareza);
        }

        .btn-dark:hover {
            background: var(--noite);
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.72);
            color: var(--ink-soft);
            border: 1px solid var(--border);
        }

        .btn-outline:hover {
            background: var(--white);
        }

        .btn-gold {
            background: var(--gold);
            color: var(--noite);
        }

        .btn-gold:hover {
            background: var(--gold-lt);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: .2rem .6rem;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 500;
        }

        .badge-green {
            background: var(--green-bg);
            color: var(--green);
        }

        .badge-orange {
            background: var(--orange-bg);
            color: var(--orange);
        }

        .badge-red {
            background: var(--red-bg);
            color: var(--red);
        }

        .badge-blue {
            background: var(--blue-bg);
            color: var(--blue);
        }

        .badge-gold {
            background: var(--gold-bg);
            color: var(--gold);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .content {
            animation: fadeIn .3s ease;
        }

        @media (max-width: 1180px) {
            .sidebar {
                width: 244px;
            }

            .main {
                margin-left: 276px;
            }
        }

        @media (max-width: 980px) {
            .sidebar {
                position: static;
                width: auto;
                margin: 12px;
                bottom: auto;
            }

            .main {
                margin-left: 0;
                padding: 0 12px 12px;
            }

            .topbar {
                top: 0;
            }
        }
    </style>
    @stack('styles')
</head>

<body>

    {{-- ── Sidebar ──────────────────────────────────────────────── --}}
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">
                <img width="100%" src="/images/logo-borda-white.png" alt="">
            </div>
            <span class="sidebar-logo-text">Qu4tro.ai</span>
            <span class="sidebar-badge">Admin</span>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-label">Visão geral</div>
            <a href="{{ route('admin.dashboard') }}"
                class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" />
                </svg>
                Dashboard
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-label">Clientes</div>
            <a href="{{ route('admin.municipalities.index') }}"
                class="nav-item {{ request()->routeIs('admin.municipalities*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M15 11V5l-3-3-3 3v2H3v14h18V11h-6zm-8 8H5v-2h2v2zm0-4H5v-2h2v2zm0-4H5V9h2v2zm6 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V9h2v2zm0-4h-2V5h2v2zm6 12h-2v-2h2v2zm0-4h-2v-2h2v2z" />
                </svg>
                Municípios
                @if (isset($pendingOnboarding) && $pendingOnboarding > 0)
                    <span class="nav-badge">{{ $pendingOnboarding }}</span>
                @endif
            </a>
            <a href="{{ route('admin.users.index') }}"
                class="nav-item {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" />
                </svg>
                Prefeitos
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-label">Sistema</div>
            <a href="{{ route('admin.integrations.index') }}"
                class="nav-item {{ request()->routeIs('admin.integrations*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z" />
                </svg>
                Integrações
            </a>
            <a href="{{ route('admin.federal-programs.index') }}"
                class="nav-item {{ request()->routeIs('admin.federal-programs*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z" />
                </svg>
                Programas Federais
            </a>
            <a href="{{ route('admin.knowledge-base.index') }}"
                class="nav-item {{ request()->routeIs('admin.knowledge-base*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z" />
                </svg>
                Base de Conhecimento
            </a>
            <a href="{{ route('admin.settings.index') }}"
                class="nav-item {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z" />
                </svg>
                Configurações Sistema
            </a>
            <a href="{{ route('admin.settings.integrations') }}"
                class="nav-item {{ request()->routeIs('admin.settings.integrations*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M17 7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h10c2.76 0 5-2.24 5-5s-2.24-5-5-5zm0 8H7c-1.65 0-3-1.35-3-3s1.35-3 3-3h10c1.65 0 3 1.35 3 3s-1.35 3-3 3zm-3-3c0 1.1.9 2 2 2s2-.9 2-2-.9-2-2-2-2 .9-2 2z" />
                </svg>
                APIs Externas
            </a>
            <a href="{{ route('admin.diagnostic.index') }}"
                class="nav-item {{ request()->routeIs('admin.diagnostic*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
                </svg>
                Diagnóstico
            </a>
            <a href="{{ route('admin.coverage-alerts.index') }}"
                class="nav-item {{ request()->routeIs('admin.coverage-alerts*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2 1 21h22L12 2zm0 5.8 6.53 11.2H5.47L12 7.8zm-1 3.2v4h2v-4h-2zm0 6v2h2v-2h-2z" />
                </svg>
                Alertas de Cobertura
            </a>
        </div>

        <div class="sidebar-spacer"></div>

        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div style="flex:1;min-width:0">
                <div class="sidebar-user-name">{{ auth()->user()->name }}</div>
                <div class="sidebar-user-role">Administrador</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-logout" title="Sair">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z" />
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    {{-- ── Main ─────────────────────────────────────────────────── --}}
    <main class="main">
        <div class="topbar">
            <div class="topbar-breadcrumb">
                @yield('breadcrumb')
            </div>
            <div class="topbar-actions">
                <div class="topbar-search">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                    </svg>
                    <input type="text" placeholder="Buscar município...">
                </div>
                @yield('topbar-actions')
            </div>
        </div>

        <div class="content">
            @yield('content')
        </div>
    </main>

    @stack('scripts')
</body>

</html>
