@extends('layouts.admin')
@section('title', 'Configurações do Sistema')
@section('content')
    <div style="padding:2rem;max-width:750px">
        <h1 style="font-size:1.4rem;font-weight:700;margin-bottom:1.5rem">Configurações do Sistema</h1>

        @if (session('success'))
            <div
                style="background:#d1fae5;border:1px solid #6ee7b7;padding:1rem;border-radius:8px;margin-bottom:1.5rem;color:#065f46">
                {{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div
                style="background:#fee2e2;border:1px solid #fca5a5;padding:1rem;border-radius:8px;margin-bottom:1.5rem;color:#991b1b;font-size:.88rem">
                @foreach ($errors->all() as $e)
                    <div>{{ $e }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.settings.ai') }}">
            @csrf

            {{-- PROVIDER PADRÃO --}}
            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
                <h3
                    style="font-size:.95rem;font-weight:600;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid #f3f4f6">
                    🤖 Provider Padrão de IA</h3>
                <p style="font-size:.82rem;color:#6b7280;margin-bottom:1rem">Define qual IA será usada em todas as
                    funcionalidades do sistema.</p>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem">
                    @foreach (['anthropic' => ['nome' => 'Anthropic (Claude)', 'cor' => '#d97706'], 'openai' => ['nome' => 'OpenAI (GPT)', 'cor' => '#16a34a'], 'gemini' => ['nome' => 'Google Gemini', 'cor' => '#2563eb']] as $val => $info)
                        <label style="cursor:pointer">
                            <input type="radio" name="ai_default_provider" value="{{ $val }}"
                                {{ $ai['ai_default_provider'] === $val ? 'checked' : '' }} style="display:none"
                                onchange="document.querySelectorAll('.provider-card').forEach(c=>c.style.borderColor='#e5e7eb'); this.closest('label').querySelector('.provider-card').style.borderColor='{{ $info['cor'] }}'">
                            <div class="provider-card"
                                style="padding:1rem;border:2px solid {{ $ai['ai_default_provider'] === $val ? $info['cor'] : '#e5e7eb' }};border-radius:10px;text-align:center;transition:.2s">
                                <div style="font-weight:600;font-size:.88rem;color:#0f1117">{{ $info['nome'] }}</div>
                                @if ($ai['ai_default_provider'] === $val)
                                    <div
                                        style="font-size:.72rem;color:{{ $info['cor'] }};font-weight:600;margin-top:.3rem">
                                        ✓ ATIVO</div>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- ANTHROPIC --}}
            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
                <div
                    style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid #f3f4f6">
                    <h3 style="font-size:.95rem;font-weight:600">Anthropic (Claude)</h3>
                    <button type="button" onclick="testConnection('anthropic')"
                        style="padding:.35rem .9rem;border:1px solid #d1d5db;border-radius:6px;font-size:.78rem;background:#fff;cursor:pointer">Testar
                        conexão</button>
                </div>
                <div style="display:grid;gap:.75rem">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Modelo</label>
                        <select name="anthropic_model"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                            @foreach (['claude-sonnet-4-6' => 'Claude Sonnet 4.6 (recomendado)', 'claude-opus-4-6' => 'Claude Opus 4.6 (mais poderoso)', 'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 (mais rápido)'] as $val => $label)
                                <option value="{{ $val }}"
                                    {{ $ai['anthropic_model'] === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Chave de
                            API</label>
                        <input type="password" name="anthropic_api_key"
                            placeholder="sk-ant-... (deixe em branco para manter)"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                        @if (!empty($ai['anthropic_api_key']))
                            <div style="font-size:.75rem;color:#16a34a;margin-top:.3rem">✓ Chave configurada
                                ({{ substr($ai['anthropic_api_key'], 0, 12) }}...)</div>
                        @else
                            <div style="font-size:.75rem;color:#9ca3af;margin-top:.3rem">Nenhuma chave configurada</div>
                        @endif
                    </div>
                </div>
                <div id="test-anthropic"
                    style="display:none;margin-top:.75rem;padding:.75rem;border-radius:8px;font-size:.82rem"></div>
            </div>

            {{-- OPENAI --}}
            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
                <div
                    style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid #f3f4f6">
                    <h3 style="font-size:.95rem;font-weight:600">OpenAI (GPT)</h3>
                    <button type="button" onclick="testConnection('openai')"
                        style="padding:.35rem .9rem;border:1px solid #d1d5db;border-radius:6px;font-size:.78rem;background:#fff;cursor:pointer">Testar
                        conexão</button>
                </div>
                <div style="display:grid;gap:.75rem">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Modelo</label>
                        <select name="openai_model"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                            @foreach (['gpt-4o-mini' => 'GPT-4o Mini (recomendado)', 'gpt-4o' => 'GPT-4o (mais poderoso)', 'gpt-3.5-turbo' => 'GPT-3.5 Turbo (mais rápido)'] as $val => $label)
                                <option value="{{ $val }}" {{ $ai['openai_model'] === $val ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Chave de
                            API</label>
                        <input type="password" name="openai_api_key" placeholder="sk-... (deixe em branco para manter)"
                            style="width:100%;padding:.6rem .8rim;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                        @if (!empty($ai['openai_api_key']))
                            <div style="font-size:.75rem;color:#16a34a;margin-top:.3rem">✓ Chave configurada
                                ({{ substr($ai['openai_api_key'], 0, 7) }}...)</div>
                        @else
                            <div style="font-size:.75rem;color:#9ca3af;margin-top:.3rem">Nenhuma chave configurada</div>
                        @endif
                    </div>
                </div>
                <div id="test-openai"
                    style="display:none;margin-top:.75rem;padding:.75rem;border-radius:8px;font-size:.82rem"></div>
            </div>

            {{-- GEMINI --}}
            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1.5rem">
                <div
                    style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid #f3f4f6">
                    <h3 style="font-size:.95rem;font-weight:600">Google Gemini</h3>
                    <button type="button" onclick="testConnection('gemini')"
                        style="padding:.35rem .9rem;border:1px solid #d1d5db;border-radius:6px;font-size:.78rem;background:#fff;cursor:pointer">Testar
                        conexão</button>
                </div>
                <div style="display:grid;gap:.75rem">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Modelo</label>
                        <select name="gemini_model"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                            @foreach (['gemini-1.5-pro' => 'Gemini 1.5 Pro (recomendado)', 'gemini-1.5-flash' => 'Gemini 1.5 Flash (mais rápido)', 'gemini-2.0-flash' => 'Gemini 2.0 Flash'] as $val => $label)
                                <option value="{{ $val }}" {{ $ai['gemini_model'] === $val ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Chave de
                            API</label>
                        <input type="password" name="gemini_api_key" placeholder="AIza... (deixe em branco para manter)"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                        @if (!empty($ai['gemini_api_key']))
                            <div style="font-size:.75rem;color:#16a34a;margin-top:.3rem">✓ Chave configurada</div>
                        @else
                            <div style="font-size:.75rem;color:#9ca3af;margin-top:.3rem">Nenhuma chave configurada</div>
                        @endif
                    </div>
                </div>
                <div id="test-gemini"
                    style="display:none;margin-top:.75rem;padding:.75rem;border-radius:8px;font-size:.82rem"></div>
            </div>

            {{-- VOYAGE AI — Embeddings --}}
            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:2px solid #e0e7ff;margin-bottom:1.5rem">
                <div
                    style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid #f3f4f6">
                    <div>
                        <h3 style="font-size:.95rem;font-weight:600">🧭 Voyage AI — Embeddings (RAG)</h3>
                        <p style="font-size:.75rem;color:#6b7280;margin-top:.2rem">Parceiro oficial Anthropic para
                            embeddings. Necessário para o RAG funcionar com Claude. <a href="https://dash.voyageai.com"
                                target="_blank" style="color:var(--gold)">Criar conta gratuita →</a></p>
                    </div>
                </div>
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.35rem">Chave de API Voyage
                        AI</label>
                    <input type="password" name="voyage_api_key" placeholder="pa-... (deixe em branco para manter)"
                        style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    @if (!empty($ai['voyage_api_key']))
                        <div style="font-size:.75rem;color:#16a34a;margin-top:.3rem">✓ Chave configurada
                            ({{ substr($ai['voyage_api_key'], 0, 8) }}...) — RAG via Voyage AI ativo</div>
                    @else
                        <div style="font-size:.75rem;color:#f87171;margin-top:.3rem">⚠ Sem chave — RAG desativado quando
                            usar Anthropic sem OpenAI</div>
                    @endif
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:.5rem">
                <button type="submit"
                    style="padding:.7rem 2rem;background:var(--gold);color:#fff;border:none;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer">Salvar
                    Configurações</button>
            </div>
        </form>
    </div>

    <script>
        async function testConnection(provider) {
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const el = document.getElementById('test-' + provider);
            el.style.display = 'block';
            el.style.background = '#f3f4f6';
            el.style.color = '#374151';
            el.textContent = 'Testando conexão...';

            try {
                const res = await fetch('{{ route('admin.settings.test') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    },
                    body: JSON.stringify({
                        provider
                    })
                });
                const data = await res.json();
                if (data.success) {
                    el.style.background = '#d1fae5';
                    el.style.color = '#065f46';
                    el.textContent = `✓ Conexão OK — modelo: ${data.model} — resposta: "${data.response}"`;
                } else {
                    el.style.background = '#fee2e2';
                    el.style.color = '#991b1b';
                    el.textContent = `✗ Erro: ${data.error}`;
                }
            } catch (e) {
                el.style.background = '#fee2e2';
                el.style.color = '#991b1b';
                el.textContent = `✗ Erro: ${e.message}`;
            }
        }
    </script>
@endsection
