<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aguardando configuração — Meu Marqueteiro</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            height: 100%; font-family: 'DM Sans', sans-serif;
            background: #0f1117; display: flex;
            align-items: center; justify-content: center;
        }
        .box {
            text-align: center; max-width: 420px; padding: 2rem;
            animation: fadeUp .5s ease;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .icon {
            width: 64px; height: 64px; border-radius: 14px;
            background: rgba(184,144,42,.15);
            border: 1px solid rgba(184,144,42,.25);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.75rem;
        }
        .icon svg { width: 28px; height: 28px; fill: #d4a840; }
        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem; color: #fff;
            margin-bottom: .75rem;
        }
        p { font-size: .9rem; line-height: 1.7; color: rgba(255,255,255,.45); margin-bottom: 1.5rem; }

        .status-pill {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .45rem 1rem; border-radius: 20px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.08);
            font-size: .78rem; color: rgba(255,255,255,.4);
            margin-bottom: 2rem;
        }
        .dot { width: 7px; height: 7px; border-radius: 50%; background: #f59e0b; animation: pulse 1.5s ease infinite; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

        form button {
            background: none; border: 1px solid rgba(255,255,255,.12);
            color: rgba(255,255,255,.4); font-family: 'DM Sans', sans-serif;
            font-size: .82rem; padding: .55rem 1.2rem; border-radius: 8px;
            cursor: pointer; transition: all .15s;
        }
        form button:hover { border-color: rgba(255,255,255,.3); color: rgba(255,255,255,.7); }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">
            <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 4l5 2.18V11c0 3.5-2.33 6.79-5 7.93-2.67-1.14-5-4.43-5-7.93V7.18L12 5z"/></svg>
        </div>
        <h1>Quase lá.</h1>
        <p>
            Seu assistente está sendo configurado pelo consultor com os dados reais
            de <strong style="color:rgba(255,255,255,.7)">{{ auth()->user()->municipality->name ?? 'seu município' }}</strong>.
            Você receberá um aviso assim que estiver pronto.
        </p>
        <div class="status-pill">
            <span class="dot"></span>
            {{ $status === 'pending' ? 'Aguardando início do onboarding' : 'Configuração em andamento' }}
        </div>
        <br>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Sair da conta</button>
        </form>
    </div>
</body>
</html>
