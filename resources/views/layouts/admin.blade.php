<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Painel') — Meu Marqueteiro Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap"
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
            --ink: #0f1117;
            --ink-soft: #3d4152;
            --ink-muted: #7c8190;
            --gold: #b8902a;
            --gold-lt: #d4a840;
            --gold-bg: #fdf8ee;
            --cream: #f5f2ed;
            --white: #ffffff;
            --border: #e4e0d8;
            --border-lt: #f0ede8;
            --sidebar-w: 240px;
            --green: #1e7e48;
            --green-bg: #edf7f1;
            --orange: #c05c00;
            --orange-bg: #fff3e8;
            --red: #b52b2b;
            --red-bg: #fdf0f0;
            --blue: #1a5fa8;
            --blue-bg: #eff5ff;
        }

        html,
        body {
            height: 100%;
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            color: var(--ink);
        }

        /* ── Sidebar ──────────────────────────────────────────── */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-w);
            background: var(--ink);
            display: flex;
            flex-direction: column;
            z-index: 100;
            border-right: 1px solid rgba(255, 255, 255, .04);
        }

        .sidebar-logo {
            padding: 1.6rem 1.4rem 1.4rem;
            display: flex;
            align-items: center;
            gap: .65rem;
            border-bottom: 1px solid rgba(255, 255, 255, .06);
        }

        .sidebar-logo-icon {
            width: 34px;
            height: 34px;
            background: var(--gold);
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .sidebar-logo-icon svg {
            width: 18px;
            height: 18px;
            fill: var(--ink);
        }

        .sidebar-logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 1rem;
            color: #fff;
        }

        .sidebar-badge {
            font-size: .6rem;
            font-weight: 500;
            letter-spacing: .1em;
            text-transform: uppercase;
            background: rgba(184, 144, 42, .2);
            color: var(--gold-lt);
            padding: .15rem .45rem;
            border-radius: 3px;
            margin-left: .3rem;
        }

        .sidebar-section {
            padding: 1.2rem 1rem .4rem;
        }

        .sidebar-section-label {
            font-size: .62rem;
            font-weight: 500;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .25);
            padding: 0 .4rem;
            margin-bottom: .3rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .62rem .75rem;
            border-radius: 7px;
            font-size: .86rem;
            color: rgba(255, 255, 255, .55);
            text-decoration: none;
            transition: background .15s, color .15s;
            margin-bottom: .1rem;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, .06);
            color: rgba(255, 255, 255, .85);
        }

        .nav-item.active {
            background: rgba(184, 144, 42, .15);
            color: var(--gold-lt);
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
            color: var(--ink);
            font-size: .65rem;
            font-weight: 600;
            padding: .15rem .45rem;
            border-radius: 10px;
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
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--gold);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Playfair Display', serif;
            font-size: .85rem;
            color: var(--ink);
            font-weight: 600;
            flex-shrink: 0;
        }

        .sidebar-user-name {
            font-size: .82rem;
            font-weight: 500;
            color: rgba(255, 255, 255, .7);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }

        .sidebar-user-role {
            font-size: .7rem;
            color: rgba(255, 255, 255, .3);
            margin-top: .05rem;
        }

        .sidebar-logout {
            color: rgba(0, 0, 0, 1);
            text-decoration: none;
            transition: color .15s;
        }

        .sidebar-logout:hover {
            color: rgba(255, 255, 255, .7);
        }

        .sidebar-logout svg {
            width: 15px;
            height: 15px;
        }

        /* ── Main ─────────────────────────────────────────────── */
        .main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-breadcrumb {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .84rem;
            color: var(--ink-muted);
        }

        .topbar-breadcrumb strong {
            color: var(--ink);
            font-weight: 500;
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
            background: var(--cream);
            border: 1px solid var(--border);
            border-radius: 7px;
            padding: .45rem .75rem;
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
            font-family: 'DM Sans', sans-serif;
            font-size: .83rem;
            color: var(--ink);
            width: 180px;
        }

        .topbar-search input::placeholder {
            color: var(--ink-muted);
        }

        .content {
            padding: 2rem;
            flex: 1;
        }

        /* ── Componentes reutilizáveis ────────────────────────── */
        .page-header {
            margin-bottom: 1.75rem;
        }

        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: .3rem;
        }

        .page-header p {
            font-size: .87rem;
            color: var(--ink-muted);
        }

        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .card-header {
            padding: 1.1rem 1.4rem;
            border-bottom: 1px solid var(--border-lt);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h3 {
            font-size: .9rem;
            font-weight: 500;
            color: var(--ink);
        }

        .card-body {
            padding: 1.4rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .55rem 1rem;
            border-radius: 7px;
            font-family: 'DM Sans', sans-serif;
            font-size: .83rem;
            font-weight: 500;
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
            background: var(--ink);
            color: var(--white);
        }

        .btn-dark:hover {
            background: #1e2230;
        }

        .btn-outline {
            background: transparent;
            color: var(--ink-soft);
            border: 1px solid var(--border);
        }

        .btn-outline:hover {
            background: var(--cream);
        }

        .btn-gold {
            background: var(--gold);
            color: var(--white);
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
            <span class="sidebar-logo-text">Marqueteiro</span>
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
                Configurações IA
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
