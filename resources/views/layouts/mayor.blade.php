<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Meu Assistente</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" type="image/x-icon" href="/images/icon.svg">
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
            --accent: var(--institucional);
            --surface: var(--clareza);
            --surface-soft: #f1eee8;
            --white: #ffffff;
            --border: #e1ddd6;
            --border-lt: #ece7df;
            --nav-h: 76px;
            --sidebar-w: 96px;
            --green: var(--desenvolvimento);
            --green-bg: #edf7f1;
            --red: #b52b2b;
            --red-bg: #fdf0f0;
            --shadow-soft: 0 18px 45px rgba(17, 19, 24, 0.08);
        }

        html,
        body {
            height: 100%;
            font-family: "Inter", sans-serif;
            background: var(--surface);
            color: var(--ink);
        }

        body {
            background:
                radial-gradient(circle at top left, rgba(184, 144, 42, 0.08), transparent 26%),
                linear-gradient(180deg, #faf8f4 0%, var(--surface) 100%);
        }

        /* ── Nav lateral (ícones) ─────────────────────────────── */
        .sidenav {
            position: fixed;
            top: 16px;
            left: 16px;
            bottom: 16px;
            width: var(--sidebar-w);
            background: linear-gradient(180deg, var(--noite) 0%, var(--mandato) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.15rem 0;
            z-index: 200;
            border-radius: 28px;
            box-shadow: 0 28px 50px rgba(17, 19, 24, 0.22);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .sidenav-logo {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, rgba(184, 144, 42, 0.2) 0%, rgba(184, 144, 42, 0.35) 100%);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.85rem;
            flex-shrink: 0;
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(12px);
        }

        .sidenav-logo img {
            width: 34px;
            height: 34px;
            object-fit: contain;
        }

        .sidenav-nav {
            flex: 1;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .5rem;
        }

        .sidenav-item {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(246, 244, 240, 0.68);
            text-decoration: none;
            transition: background .18s, color .18s, transform .18s, box-shadow .18s;
            position: relative;
            border: 1px solid transparent;
        }

        .sidenav-item:hover {
            background: rgba(255, 255, 255, 0.08);
            color: var(--clareza);
            transform: translateY(-1px);
        }

        .sidenav-item.active {
            background: rgba(184, 144, 42, 0.18);
            color: var(--gold-lt);
            border-color: rgba(184, 144, 42, 0.18);
            box-shadow: inset 0 0 0 1px rgba(184, 144, 42, 0.2);
        }

        .sidenav-item.active::before {
            content: "";
            position: absolute;
            left: -8px;
            width: 4px;
            height: 22px;
            border-radius: 999px;
            background: var(--gold);
        }

        .sidenav-item svg {
            width: 23px;
            height: 23px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.4;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* Tooltip */
        .sidenav-item::after {
            content: attr(data-label);
            position: absolute;
            left: calc(100% + 14px);
            background: rgba(28, 36, 58, 0.96);
            color: var(--clareza);
            font-size: .74rem;
            white-space: nowrap;
            padding: .45rem .72rem;
            border-radius: 999px;
            opacity: 0;
            pointer-events: none;
            transform: translateX(-4px);
            transition: opacity .15s, transform .15s;
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 10px 24px rgba(17, 19, 24, 0.18);
        }

        .sidenav-item:hover::after {
            opacity: 1;
            transform: translateX(0);
        }

        .sidenav-item .dot {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--gold);
            border: 1.5px solid var(--mandato);
        }

        .sidenav-bottom {
            padding-bottom: .35rem;
        }

        .sidenav-avatar {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            color: var(--clareza);
            font-weight: 600;
            cursor: pointer;
            flex-shrink: 0;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        /* ── Main ─────────────────────────────────────────────── */
        .main {
            margin-left: calc(var(--sidebar-w) + 32px);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 16px 18px 16px 0;
        }

        /* ── Topbar ───────────────────────────────────────────── */
        .topbar {
            min-height: var(--nav-h);
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(225, 221, 214, 0.9);
            display: flex;
            align-items: center;
            padding: 1rem 1.4rem;
            gap: .85rem;
            position: sticky;
            top: 16px;
            z-index: 100;
            border-radius: 24px;
            backdrop-filter: blur(16px);
            box-shadow: var(--shadow-soft);
            flex-wrap: wrap;
        }

        .topbar-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--mandato);
            flex: 1;
            letter-spacing: -0.02em;
        }

        .topbar-date {
            font-size: .8rem;
            color: var(--ink-muted);
            padding: .55rem .85rem;
            border-radius: 999px;
            background: var(--surface-soft);
            border: 1px solid var(--border-lt);
        }

        .topbar-briefing {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .72rem 1rem;
            border-radius: 16px;
            background: var(--mandato);
            color: var(--clareza);
            font-size: .82rem;
            font-weight: 600;
            text-decoration: none;
            transition: transform .15s, box-shadow .15s, background .15s;
            box-shadow: 0 12px 22px rgba(28, 36, 58, 0.16);
        }

        .topbar-briefing:hover {
            background: var(--noite);
            transform: translateY(-1px);
        }

        .topbar-briefing svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.4;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* ── Page ─────────────────────────────────────────────── */
        .page-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            margin-top: 1rem;
        }

        /* ── Utilitários ──────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .72rem 1.05rem;
            border-radius: 14px;
            font-family: "Inter", sans-serif;
            font-size: .84rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all .15s;
        }

        .btn svg {
            width: 15px;
            height: 15px;
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

        .topbar-logout {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .72rem .95rem;
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid var(--border);
            border-radius: 14px;
            font-family: 'Inter', sans-serif;
            font-size: .82rem;
            font-weight: 600;
            color: var(--ink-muted);
            cursor: pointer;
            transition: background .15s, color .15s, transform .15s;
        }

        .topbar-logout:hover {
            background: var(--white);
            color: var(--mandato);
            transform: translateY(-1px);
        }

        .topbar-logout svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-body {
            animation: fadeIn .25s ease;
        }

        @media (max-width: 1023px) {
            .sidenav {
                left: 12px;
                top: 12px;
                bottom: 12px;
                width: 84px;
            }

            .main {
                margin-left: 108px;
                padding-right: 12px;
            }
        }

        @media (max-width: 767px) {
            .sidenav {
                position: static;
                width: auto;
                height: auto;
                flex-direction: row;
                justify-content: space-between;
                border-radius: 24px;
                margin: 12px;
                padding: .85rem;
            }

            .sidenav-nav {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }

            .sidenav-item::after,
            .sidenav-item.active::before {
                display: none;
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

    {{-- PWA + Web Push --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#111318">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
</head>

<body>

    @php
        $currentUser = auth()->user();
        $isMayorUser = $currentUser && $currentUser->hasRole('mayor');
        $resolveAiRoute = $isMayorUser ? route('mayor.mandato.demands.index') : route('resolve-ai.demands.index');
        $praHojeRoute = route('pra-hoje.index');
    @endphp

    {{-- ── Sidenav ──────────────────────────────────────────────── --}}
    <nav class="sidenav">
        <div class="sidenav-logo">
            <img src="/images/logo-borda-white.png" alt="Qu4tro.ai">
        </div>

        <div class="sidenav-nav">
            <!--<a href="{{ route('mayor.dashboard') }}"
                class="sidenav-item {{ request()->routeIs('mayor.dashboard') ? 'active' : '' }}" data-label="Início">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" />
                </svg>
            </a>-->
            <a href="{{ route('mayor.situacao') }}"
                class="sidenav-item {{ request()->routeIs('mayor.situacao*') ? 'active' : '' }}" data-label="Painel">
                <svg viewBox="0 0 26 26" aria-hidden="true">
                    <rect x="4" y="5" width="7" height="7" rx="2" />
                    <rect x="15" y="5" width="7" height="11" rx="2" />
                    <rect x="4" y="15" width="7" height="7" rx="2" />
                    <rect x="15" y="19" width="7" height="3" rx="1.5" />
                </svg>
            </a>
            <a href="{{ $praHojeRoute }}"
                class="sidenav-item {{ request()->routeIs('pra-hoje.*') || request()->routeIs('mayor.mandato.briefings*') ? 'active' : '' }}"
                data-label="Pra hoje!">
                <svg viewBox="0 0 26 26" aria-hidden="true">
                    <rect x="4" y="5" width="18" height="17" rx="3" />
                    <path d="M4 10h18" />
                    <path d="M9 3v4" />
                    <path d="M17 3v4" />
                    <path d="M8 15h3" />
                    <path d="M8 18.5h5" />
                    <circle cx="17.5" cy="16.5" r="3.5" />
                    <path d="M16 16.5l1 1 2-2" />
                </svg>
            </a>
            <a href="{{ $resolveAiRoute }}"
                class="sidenav-item {{ request()->routeIs('mayor.mandato.demands*') || request()->routeIs('resolve-ai.demands*') ? 'active' : '' }}"
                data-label="Resolve ai">
                <svg viewBox="0 0 26 26" aria-hidden="true">
                    <path
                        d="M21 13c0 4.42-3.58 8-8 8a8.1 8.1 0 0 1-3.5-.79L4 22l1.79-5.5A7.93 7.93 0 0 1 5 13c0-4.42 3.58-8 8-8s8 3.58 8 8z" />
                    <path d="M9.5 13l2 2 4-4" />
                </svg>
            </a>
            @if ($isMayorUser)

                <a href="{{ route('mayor.chat.index') }}"
                    class="sidenav-item {{ request()->routeIs('mayor.chat*') ? 'active' : '' }}"
                    data-label="Meu Assistente">
                    <svg viewBox="0 0 26 26" aria-hidden="true">
                        <rect x="6" y="9" width="14" height="11" rx="3" />
                        <rect x="9" y="13" width="2.5" height="2.5" rx="0.8" fill="currentColor"
                            stroke="none" />
                        <rect x="14.5" y="13" width="2.5" height="2.5" rx="0.8" fill="currentColor"
                            stroke="none" />
                        <path d="M10 20v2.5" />
                        <path d="M16 20v2.5" />
                        <path d="M13 9V6.5" />
                        <circle cx="13" cy="5.5" r="1.2" fill="currentColor" stroke="none" />
                        <path d="M6 14.5H4a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h2" />
                        <path d="M20 14.5h2a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1h-2" />
                    </svg>
                    @if (false)
                        {{-- lógica de mensagens não  lidas aqui --}}
                        <span class="dot"></span>
                    @endif
                </a>

                <a href="{{ route('mayor.content.index') }}"
                    class="sidenav-item {{ request()->routeIs('mayor.content*') || request()->routeIs('mayor.mentions*') ? 'active' : '' }}"
                    data-label="Comunicação">
                    <svg viewBox="0 0 26 26" aria-hidden="true">
                        <circle cx="18" cy="6" r="2.5" />
                        <circle cx="18" cy="20" r="2.5" />
                        <circle cx="7" cy="13" r="2.5" />
                        <path d="M9.4 11.8l6.2-4" />
                        <path d="M9.4 14.2l6.2 4" />
                    </svg>
                </a>
                <a href="{{ route('mayor.mandato.federal-programs') }}"
                    class="sidenav-item {{ request()->routeIs('mayor.mandato.federal-programs*') ? 'active' : '' }}"
                    data-label="Radar de Recursos">
                    <svg viewBox="0 0 26 26" aria-hidden="true">
                        <circle cx="12" cy="13" r="5.5" />
                        <path d="M12 10v6" />
                        <path d="M10 11.5h3a1 1 0 0 1 0 2h-2a1 1 0 0 0 0 2h3" />
                        <path d="M19.5 6.5a9.5 9.5 0 0 1 0 13" />
                        <path d="M4.5 6.5a9.5 9.5 0 0 0 0 13" />
                    </svg>
                </a>
                <a href="{{ route('mayor.projects.index') }}"
                    class="sidenav-item {{ request()->routeIs('mayor.projects*') ? 'active' : '' }}"
                    data-label="Projetos">
                    <svg viewBox="0 0 26 26" aria-hidden="true">
                        <path
                            d="M13 3C9.13 3 6 6.13 6 10c0 2.6 1.4 4.9 3.5 6.2V18h7v-1.8C18.6 14.9 20 12.6 20 10c0-3.87-3.13-7-7-7z" />
                        <path d="M9.5 18h7" />
                        <path d="M10.5 21h5" />
                        <path d="M11 10l1.5-3 1.5 3 1.5-3" />
                    </svg>
                </a>
                <a href="{{ route('mayor.mandato.painel') }}"
                    class="sidenav-item {{ request()->routeIs('mayor.mandato*') && !request()->routeIs('mayor.mandato.demands*') ? 'active' : '' }}"
                    data-label="Ações">
                    <svg viewBox="0 -960 960 960" aria-hidden="true" style="fill: currentColor; stroke: none;">
                        <path
                            d="M480-80 120-436l200-244h320l200 244L480-80ZM183-680l-85-85 57-56 85 85-57 56Zm257-80v-120h80v120h-80Zm335 80-57-57 85-85 57 57-85 85ZM480-192l210-208H270l210 208ZM358-600l-99 120h442l-99-120H358Z" />
                    </svg>
                </a>
            @endif

        </div>

        <div class="sidenav-bottom">
            <div class="sidenav-avatar" title="{{ auth()->user()->name }}">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
        </div>
    </nav>

    {{-- ── Main ─────────────────────────────────────────────────── --}}
    <main class="main">
        <div class="topbar">
            <div class="topbar-title">@yield('topbar-title', 'Meu Assistente')</div>
            <div class="topbar-date">{{ now()->locale('pt_BR')->isoFormat('ddd, D MMM') }}</div>
            <form method="POST" action="{{ route('logout') }}" style="margin:0">
                @csrf
                <button type="submit" class="topbar-logout">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <path d="M16 17l5-5-5-5" />
                        <path d="M21 12H9" />
                    </svg>
                    Sair
                </button>
            </form>

            <a href="{{ $praHojeRoute }}" class="topbar-briefing">
                <svg viewBox="0 0 26 26" aria-hidden="true">
                    <rect x="4" y="5" width="18" height="17" rx="3" />
                    <path d="M4 10h18" />
                    <path d="M9 3v4" />
                    <path d="M17 3v4" />
                    <circle cx="17.5" cy="16.5" r="3.5" />
                    <path d="M16 16.5l1 1 2-2" />
                </svg>
                Pra hoje!
            </a>
        </div>

        <div class="page-body">
            @yield('content')
        </div>
    </main>

    @stack('scripts')

    {{-- Registro do Service Worker + Web Push --}}
    <script>
        (function() {
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

            const VAPID_PUBLIC_KEY = '{{ config('webpush.vapid_public_key') }}';
            const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

            // Converter VAPID key de base64url para Uint8Array
            function urlBase64ToUint8Array(base64String) {
                const padding = '='.repeat((4 - base64String.length % 4) % 4);
                const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                const rawData = window.atob(base64);
                return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
            }

            async function registerPush() {
                try {
                    // Registrar o Service Worker
                    const reg = await navigator.serviceWorker.register('/sw.js', {
                        scope: '/'
                    });
                    await navigator.serviceWorker.ready;

                    // Verificar se já tem subscription ativa
                    let subscription = await reg.pushManager.getSubscription();

                    if (!subscription) {
                        // Pedir permissão apenas se ainda não  foi concedida
                        const permission = await Notification.requestPermission();
                        if (permission !== 'granted') return;

                        // Criar nova subscription
                        subscription = await reg.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
                        });
                    }

                    // Enviar subscription para o servidor
                    const key = subscription.getKey ? subscription.getKey('p256dh') : null;
                    const auth = subscription.getKey ? subscription.getKey('auth') : null;

                    await fetch('/mayor/push/subscribe', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            endpoint: subscription.endpoint,
                            public_key: key ? btoa(String.fromCharCode(...new Uint8Array(key))) :
                                null,
                            auth_token: auth ? btoa(String.fromCharCode(...new Uint8Array(auth))) :
                                null,
                        }),
                    });

                } catch (e) {
                    // Falha silenciosa — não  interrompe o uso do app
                    console.warn('[Push] Registro falhou:', e.message);
                }
            }

            // Registrar após o carregamento, com delay para não  bloquear
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => setTimeout(registerPush, 2000));
            } else {
                setTimeout(registerPush, 2000);
            }
        })();
    </script>
</body>

</html>
