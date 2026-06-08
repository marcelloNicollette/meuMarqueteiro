<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            --institucional: #1a5fa8;
            --desenvolvimento: #1e7e48;
            --ink: var(--noite);
            --ink-soft: var(--gestao);
            --ink-muted: var(--apoio);
            --gold: var(--ouro);
            --gold-lt: #d1af5b;
            --cream: var(--clareza);
            --white: #ffffff;
            --border: #e1ddd6;
            --red: #c0392b;
            --shadow-soft: 0 30px 60px rgba(17, 19, 24, 0.18);
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
                radial-gradient(circle at top left, rgba(184, 144, 42, 0.08), transparent 28%),
                linear-gradient(180deg, #faf8f4 0%, var(--cream) 100%);
        }

        /* ── Layout ─────────────────────────────────────────── */
        .page {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
        }

        /* ── Painel esquerdo — identidade visual ─────────────── */
        .brand-panel {
            background: linear-gradient(180deg, var(--noite) 0%, var(--mandato) 100%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3.5rem;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            top: -120px;
            right: -120px;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            border: 1px solid rgba(184, 144, 42, .18);
        }

        .brand-panel::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            border: 1px solid rgba(184, 144, 42, .12);
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: .75rem;
            position: relative;
            z-index: 1;
        }

        .brand-logo-icon {
            width: 84px;
            height: 84px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 1);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(14px);
        }

        .brand-logo-icon svg {
            width: 20px;
            height: 20px;
            fill: var(--ink);
        }

        .brand-logo-name {
            font-family: "Outfit", sans-serif;
            font-size: 1.15rem;
            color: var(--white);
            letter-spacing: .01em;
        }

        .brand-hero {
            position: relative;
            z-index: 10;
        }

        .brand-hero-eyebrow {
            font-size: .8rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 1rem;
            letter-spacing: .2em;
        }

        .brand-hero-title {
            font-family: "Outfit", sans-serif;
            font-size: 3.1rem;
            line-height: 1.04;
            color: var(--white);
            margin-bottom: 1.2rem;
            font-weight: 600;
            letter-spacing: -0.03em;
        }

        .brand-hero-title em {
            font-style: normal;
            color: var(--gold-lt);
        }

        .brand-hero-body {
            font-size: .95rem;
            line-height: 1.75;
            color: rgba(255, 255, 255, .68);
            max-width: 420px;
        }

        .brand-footer {
            position: relative;
            z-index: 1;
        }

        .brand-stats {
            display: flex;
            gap: 2.5rem;
        }

        .brand-stat-value {
            font-family: "JetBrains Mono", monospace;
            font-size: 1.8rem;
            color: var(--white);
        }

        .brand-stat-label {
            font-size: .72rem;
            letter-spacing: .08em;
            color: rgba(255, 255, 255, .4);
            text-transform: uppercase;
            margin-top: .2rem;
        }

        /* ── Painel direito — formulário ─────────────────────── */
        .form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 4rem;
            z-index: 11;
        }

        .form-box {
            width: 100%;
            max-width: 440px;
            background: rgba(255, 255, 255, 0.84);
            border: 1px solid rgba(225, 221, 214, 0.92);
            border-radius: 28px;
            padding: 2.25rem;
            box-shadow: var(--shadow-soft);
            backdrop-filter: blur(16px);
        }

        .form-heading {
            margin-bottom: 2.5rem;
        }

        .form-heading h1 {
            font-family: "Outfit", sans-serif;
            font-size: 2.1rem;
            font-weight: 600;
            color: var(--mandato);
            margin-bottom: .45rem;
            letter-spacing: -0.02em;
        }

        .form-heading p {
            font-size: .88rem;
            color: var(--ink-muted);
            line-height: 1.6;
        }

        /* Alerta de erro ─── */
        .alert-error {
            background: #fff0ef;
            border: 1px solid #f5c6c2;
            border-radius: 8px;
            padding: .9rem 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: .6rem;
        }

        .alert-error svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            margin-top: 1px;
            color: var(--red);
        }

        .alert-error p {
            font-size: .83rem;
            color: var(--red);
            line-height: 1.5;
        }

        /* Campo ─── */
        .field {
            margin-bottom: 1.25rem;
        }

        .field label {
            display: block;
            font-size: .74rem;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--ink-soft);
            margin-bottom: .55rem;
        }

        .field input {
            width: 100%;
            padding: .95rem 1rem;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            font-family: "Inter", sans-serif;
            font-size: .93rem;
            color: var(--ink);
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        .field input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(184, 144, 42, .12);
        }

        .field input.is-invalid {
            border-color: var(--red);
        }

        .field-error {
            font-size: .78rem;
            color: var(--red);
            margin-top: .4rem;
        }

        /* Linha lembrar ─── */
        .field-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.75rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .84rem;
            color: var(--ink-soft);
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"] {
            width: 15px;
            height: 15px;
            accent-color: var(--gold);
            cursor: pointer;
        }

        /* Botão ─── */
        .btn-primary {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--mandato) 0%, var(--noite) 100%);
            color: var(--white);
            border: none;
            border-radius: 18px;
            font-family: "Inter", sans-serif;
            font-size: .93rem;
            font-weight: 700;
            letter-spacing: .02em;
            cursor: pointer;
            transition: background .2s, transform .1s;
            position: relative;
            overflow: hidden;
            box-shadow: 0 16px 26px rgba(28, 36, 58, 0.18);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--noite) 0%, var(--mandato) 100%);
        }

        .btn-primary:active {
            transform: scale(.99);
        }

        .btn-primary .btn-shine {
            position: absolute;
            top: 0;
            left: -100%;
            width: 60%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .07), transparent);
            transition: left .5s;
        }

        .btn-primary:hover .btn-shine {
            left: 150%;
        }

        .form-footer {
            text-align: center;
            margin-top: 2rem;
            font-size: .78rem;
            color: var(--ink-muted);
        }

        /* ── Responsivo ───────────────────────────────────────── */
        @media (max-width: 768px) {
            .page {
                grid-template-columns: 1fr;
            }

            .brand-panel {
                display: none;
            }

            .form-panel {
                padding: 2rem 1.5rem;
            }
        }



        /* ── Animação de entrada ──────────────────────────────── */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-box {
            animation: fadeUp .5s ease both;
        }

        .brand-hero {
            animation: fadeUp .6s .1s ease both;
        }

        .robo-bg {

            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            z-index: 1;
            right: 40px;
            background-position: right top;
            opacity: .14;
            filter: grayscale(1) contrast(1.1);
        }

        .logo {
            width: 50%;

        }



        @media (max-width: 880px) {
            .robo-bg {
                background: none;
            }
        }
    </style>
</head>

<body>

    <div class="page">
        <div class="robo-bg"></div>
        {{-- ── Painel esquerdo ──────────────────────────────────────── --}}
        <div class="brand-panel">

            <div class="brand-logo">
                <div class="brand-logo-icon">
                    <img width="100%" src="/images/icon.svg" alt="">
                </div>
                <!--<span class="brand-logo-name">Meu Assistente</span>-->
            </div>

            <div class="brand-hero">
                <p class="brand-hero-eyebrow">Qu4tro.ai</p>
                <h2 class="brand-hero-title">Gestão pública mais clara, estratégica e orientada por dados.</h2>
                <p class="brand-hero-body">
                    Uma experiência visual mais limpa, institucional e confiável, alinhada ao novo sistema de
                    tipografia,
                    iconografia e cores da Qu4tro.ai.
                </p>
            </div>

            <div class="brand-footer">
                <div class="brand-stats">
                    <div>
                        <div class="brand-stat-value">24h</div>
                        <div class="brand-stat-label">Disponível</div>
                    </div>
                    <div>
                        <div class="brand-stat-value">IA</div>
                        <div class="brand-stat-label">Assistida</div>
                    </div>
                    <div>
                        <div class="brand-stat-value">100%</div>
                        <div class="brand-stat-label">Alinhado à marca</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Painel direito — formulário ──────────────────────────── --}}
        <div class="form-panel">
            <div class="form-box">
                <div class="logo">
                    <img src="/images/logo-qu4tro.ai.svg" width="100%" alt="">
                </div>
                <div class="form-heading">
                    <h1>Entrar na plataforma</h1>
                    <p>Acesse com seu e-mail e senha cadastrados para continuar no ambiente da Qu4tro.ai.</p>
                </div>

                {{-- Erros globais --}}
                @if ($errors->any())
                    <div class="alert-error">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                        </svg>
                        <p>{{ $errors->first() }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf

                    <div class="field">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                            placeholder="seu@email.com.br" autocomplete="email" autofocus
                            class="{{ $errors->has('email') ? 'is-invalid' : '' }}">
                        @error('email')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password">Senha</label>
                        <input type="password" id="password" name="password" placeholder="••••••••"
                            autocomplete="current-password" class="{{ $errors->has('password') ? 'is-invalid' : '' }}">
                        @error('password')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field-row">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember" id="remember"
                                {{ old('remember') ? 'checked' : '' }}>
                            Manter conectado
                        </label>
                    </div>

                    <button type="submit" class="btn-primary">
                        <span class="btn-shine"></span>
                        Acessar plataforma
                    </button>
                </form>

                <p class="form-footer">
                    Problemas de acesso? Fale com o seu consultor.
                </p>

            </div>
        </div>

    </div>

</body>

</html>
