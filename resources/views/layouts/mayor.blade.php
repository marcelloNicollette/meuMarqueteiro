<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Meu Assistente</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="icon" type="image/x-icon" href="/images/logo-borda-black.png">
    <link
        href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;1,400&family=DM+Sans:wght@300;400;500&display=swap"
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
            --ink: #111318;
            --ink-soft: #3e424f;
            --ink-muted: #80869a;
            --gold: #b8902a;
            --gold-lt: #cfaa50;
            --accent: #1a5fa8;
            --surface: #f6f4f0;
            --white: #ffffff;
            --border: #e5e1da;
            --border-lt: #edeae4;
            --nav-h: 60px;
            --sidebar-w: 72px;
            --green: #1e7e48;
            --green-bg: #edf7f1;
            --red: #b52b2b;
            --red-bg: #fdf0f0;
        }

        html,
        body {
            height: 100%;
            font-family: "Open Sans", sans-serif;
            background: var(--surface);
            color: var(--ink);
        }

        /* ── Nav lateral (ícones) ─────────────────────────────── */
        .sidenav {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-w);
            background: var(--ink);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.2rem 0;
            z-index: 200;
        }

        .sidenav-logo {
            width: 36px;
            height: 36px;
            background: var(--gold);
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.75rem;
            flex-shrink: 0;
        }

        .sidenav-logo svg {
            width: 18px;
            height: 18px;
            fill: var(--ink);
        }

        .sidenav-nav {
            flex: 1;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .25rem;
        }

        .sidenav-item {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, .4);
            text-decoration: none;
            transition: background .15s, color .15s;
            position: relative;
        }

        .sidenav-item:hover {
            background: rgba(255, 255, 255, .08);
            color: rgba(255, 255, 255, .8);
        }

        .sidenav-item.active {
            background: rgba(184, 144, 42, .2);
            color: var(--gold-lt);
        }

        .sidenav-item svg {
            width: 20px;
            height: 20px;
        }

        /* Tooltip */
        .sidenav-item::after {
            content: attr(data-label);
            position: absolute;
            left: calc(100% + 10px);
            background: var(--ink);
            color: #fff;
            font-size: .72rem;
            white-space: nowrap;
            padding: .3rem .65rem;
            border-radius: 5px;
            opacity: 0;
            pointer-events: none;
            transform: translateX(-4px);
            transition: opacity .15s, transform .15s;
        }

        .sidenav-item:hover::after {
            opacity: 1;
            transform: translateX(0);
        }

        .sidenav-item .dot {
            position: absolute;
            top: 7px;
            right: 7px;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--gold);
            border: 1.5px solid var(--ink);
        }

        .sidenav-bottom {
            padding-bottom: .5rem;
        }

        .sidenav-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--gold);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Lora', serif;
            font-size: .9rem;
            color: var(--ink);
            font-weight: 600;
            cursor: pointer;
            flex-shrink: 0;
        }

        /* ── Main ─────────────────────────────────────────────── */
        .main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Topbar ───────────────────────────────────────────── */
        .topbar {
            height: var(--nav-h);
            background: var(--white);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 1.75rem;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-title {
            font-family: 'Lora', serif;
            font-size: 1rem;
            color: var(--ink);
            flex: 1;
        }

        .topbar-date {
            font-size: .78rem;
            color: var(--ink-muted);
        }

        .topbar-briefing {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .4rem .8rem;
            border-radius: 20px;
            background: var(--gold-lt);
            color: var(--ink);
            font-size: .78rem;
            font-weight: 500;
            text-decoration: none;
            transition: opacity .15s;
        }

        .topbar-briefing:hover {
            opacity: .85;
        }

        .topbar-briefing svg {
            width: 13px;
            height: 13px;
        }

        /* ── Page ─────────────────────────────────────────────── */
        .page-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* ── Utilitários ──────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .55rem 1rem;
            border-radius: 8px;
            font-family: "Open Sans", sans-serif;
            font-size: .83rem;
            font-weight: 500;
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
            background: var(--ink);
            color: var(--white);
        }

        .btn-dark:hover {
            background: #1e2230;
        }

        .btn-outline {
            background: transparent;
            color: var(--ink-soft);
            border: 1.5px solid var(--border);
        }

        .btn-outline:hover {
            background: var(--surface);
        }

        .btn-gold {
            background: var(--gold);
            color: var(--white);
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
            <svg viewBox="0 0 24 24">
                <path
                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z" />
            </svg>
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
                <img src="/images/icone-painel.svg" alt="">
            </a>
            <a href="{{ $praHojeRoute }}"
                class="sidenav-item {{ request()->routeIs('pra-hoje.*') || request()->routeIs('mayor.mandato.briefings*') ? 'active' : '' }}"
                data-label="Pra hoje!">
                <svg viewBox="0 0 24 24" fill="#FFFFFF">
                    <path
                        d="M12 4a1 1 0 0 1 1 1v1.05a6 6 0 1 1-2 0V5a1 1 0 0 1 1-1Zm0 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm0-6a1 1 0 0 1 1 1v.5a1 1 0 1 1-2 0V3a1 1 0 0 1 1-1Zm0 17a1 1 0 0 1 1 1v.5a1 1 0 1 1-2 0V20a1 1 0 0 1 1-1Zm9-8a1 1 0 0 1 0 2h-.5a1 1 0 1 1 0-2H21ZM4.5 11a1 1 0 1 1 0 2H4a1 1 0 1 1 0-2h.5Zm13.16-5.66a1 1 0 0 1 1.41 1.41l-.35.36a1 1 0 0 1-1.42-1.42l.36-.35Zm-11.32 11.32a1 1 0 0 1 1.41 1.41l-.35.36a1 1 0 0 1-1.42-1.42l.36-.35Zm12.03 1.77a1 1 0 0 1-1.41 0l-.36-.35a1 1 0 1 1 1.42-1.42l.35.36a1 1 0 0 1 0 1.41ZM7.05 7.4a1 1 0 0 1-1.41 0l-.36-.35A1 1 0 0 1 6.7 5.64l.35.35a1 1 0 0 1 0 1.42Z" />
                </svg>
            </a>
            <a href="{{ $resolveAiRoute }}"
                class="sidenav-item {{ request()->routeIs('mayor.mandato.demands*') || request()->routeIs('resolve-ai.demands*') ? 'active' : '' }}"
                data-label="Resolve ai">
                <img src="/images/icone-anotaai.svg" alt="">
            </a>
            @if ($isMayorUser)

                <a href="{{ route('mayor.chat.index') }}"
                    class="sidenav-item {{ request()->routeIs('mayor.chat*') ? 'active' : '' }}"
                    data-label="Meu Assistente">
                    <img src="/images/icone-meu-marqueteiro.svg" alt="">
                    @if (false)
                        {{-- lógica de mensagens não  lidas aqui --}}
                        <span class="dot"></span>
                    @endif
                </a>

                <a href="{{ route('mayor.content.index') }}"
                    class="sidenav-item {{ request()->routeIs('mayor.content*') || request()->routeIs('mayor.mentions*') ? 'active' : '' }}"
                    data-label="Comunicação">
                    <img src="/images/icone-comunicacao.svg" alt="">
                </a>
                <a href="{{ route('mayor.mandato.federal-programs') }}"
                    class="sidenav-item {{ request()->routeIs('mayor.mandato.federal-programs*') ? 'active' : '' }}"
                    data-label="Radar de Recursos">
                    <img src="/images/icone-recursos.svg" alt="">
                </a>
                <a href="{{ route('mayor.projects.index') }}"
                    class="sidenav-item {{ request()->routeIs('mayor.projects*') ? 'active' : '' }}"
                    data-label="Projetos">
                    <img src="/images/icone-projetos.svg" alt="">
                </a>
                <a href="{{ route('mayor.mandato.painel') }}"
                    class="sidenav-item {{ request()->routeIs('mayor.mandato*') && !request()->routeIs('mayor.mandato.demands*') ? 'active' : '' }}"
                    data-label="Ações">
                    <img src="/images/icone-mandato.svg" alt="">
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
                <button type="submit"
                    style="display:flex;align-items:center;gap:.4rem;padding:.45rem .9rem;background:none;border:1.5px solid var(--border);border-radius:8px;font-family:'DM Sans',sans-serif;font-size:.8rem;color:var(--ink-muted);cursor:pointer">
                    <svg width='13' height='13' viewBox='0 0 24 24' fill='currentColor'>
                        <path
                            d='M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z' />
                    </svg>
                    Sair
                </button>
            </form>

            <a href="{{ $praHojeRoute }}" class="topbar-briefing">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M12 4a1 1 0 0 1 1 1v1.05a6 6 0 1 1-2 0V5a1 1 0 0 1 1-1Zm0 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" />
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
