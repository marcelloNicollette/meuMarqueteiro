@extends('layouts.admin')
@section('title', 'Configurações do Sistema')
@section('content')
    <div style="padding:2rem;max-width:1040px">
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

        <div style="background:#fff;padding:1.5rem;border-radius:16px;border:1px solid #e5e7eb;margin-bottom:1.25rem">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1rem">
                <div>
                    <h2 style="font-size:1rem;font-weight:700;margin-bottom:.25rem">Visão executiva de prontidão</h2>
                    <p style="font-size:.84rem;color:#6b7280">Leitura consolidada da segunda camada do módulo `Configurações`
                        por município ativo.</p>
                </div>
                <a href="{{ route('admin.diagnostic.index') }}"
                    style="padding:.55rem .9rem;border:1px solid #d1d5db;border-radius:8px;text-decoration:none;font-size:.82rem;color:#374151">Abrir
                    diagnóstico</a>
            </div>
            <div style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.75rem;margin-bottom:1rem">
                <div style="padding:1rem;border-radius:12px;background:#f8fafc;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Média</div>
                    <div style="font-size:1.5rem;font-weight:700;margin-top:.2rem">
                        {{ $configExecutiveSummary['average_score'] ?? 0 }}%</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Prontidão média</div>
                </div>
                <div style="padding:1rem;border-radius:12px;background:#ecfdf5;border:1px solid #bbf7d0">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Prontos</div>
                    <div style="font-size:1.5rem;font-weight:700;margin-top:.2rem;color:#166534">
                        {{ $configExecutiveSummary['ready_total'] ?? 0 }}</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Operação saudável</div>
                </div>
                <div style="padding:1rem;border-radius:12px;background:#fff7ed;border:1px solid #fed7aa">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Atenção</div>
                    <div style="font-size:1.5rem;font-weight:700;margin-top:.2rem;color:#b45309">
                        {{ $configExecutiveSummary['warning_total'] ?? 0 }}</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Ajustes pendentes</div>
                </div>
                <div style="padding:1rem;border-radius:12px;background:#fef2f2;border:1px solid #fecaca">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Críticos</div>
                    <div style="font-size:1.5rem;font-weight:700;margin-top:.2rem;color:#b91c1c">
                        {{ $configExecutiveSummary['critical_total'] ?? 0 }}</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Fluxo incompleto</div>
                </div>
                <div style="padding:1rem;border-radius:12px;background:#eff6ff;border:1px solid #bfdbfe">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Menções + Pra hoje
                    </div>
                    <div style="font-size:1.2rem;font-weight:700;margin-top:.2rem;color:#1d4ed8">
                        {{ $configExecutiveSummary['mentions_ready_total'] ?? 0 }}/{{ $configExecutiveSummary['pra_hoje_ready_total'] ?? 0 }}
                    </div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Monitoramento / briefing</div>
                </div>
            </div>
            <div style="display:grid;gap:.75rem">
                @foreach ($configAttentionMunicipalities as $entry)
                    <div style="padding:1rem;border:1px solid #e5e7eb;border-radius:12px;background:#fafafa">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem">
                            <div>
                                <div style="font-size:.9rem;font-weight:700;color:#111827">{{ $entry['municipality_name'] }}
                                </div>
                                <div style="font-size:.78rem;color:#6b7280;margin-top:.15rem">{{ $entry['summary_label'] }}
                                </div>
                            </div>
                            <div
                                style="font-size:1rem;font-weight:700;color:{{ $entry['status'] === 'critical' ? '#b91c1c' : ($entry['status'] === 'warning' ? '#b45309' : '#166534') }}">
                                {{ $entry['score'] }}%
                            </div>
                        </div>
                        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.65rem">
                            @foreach (array_slice($entry['active_channels'] ?? [], 0, 5) as $channel)
                                <span
                                    style="padding:.25rem .55rem;border-radius:999px;background:#fff;border:1px solid #e5e7eb;font-size:.74rem;color:#374151">{{ ucfirst($channel) }}</span>
                            @endforeach
                            <span
                                style="padding:.25rem .55rem;border-radius:999px;background:#fff;border:1px solid #e5e7eb;font-size:.74rem;color:#374151">
                                termos: {{ count($entry['monitoring_terms'] ?? []) }}
                            </span>
                            <span
                                style="padding:.25rem .55rem;border-radius:999px;background:#fff;border:1px solid #e5e7eb;font-size:.74rem;color:#374151">
                                Pra hoje: {{ $entry['pra_hoje_time'] ?? 'pendente' }}
                            </span>
                        </div>
                        <div style="font-size:.78rem;color:#6b7280;margin-top:.6rem;line-height:1.55">
                            {{ implode(', ', array_slice($entry['issues'] ?? [], 0, 3)) ?: 'Sem pendências críticas.' }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

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
                    <div
                        style="margin-top:.35rem;padding:1rem;border:1px solid #dcfce7;border-radius:10px;background:#f0fdf4">
                        <div style="font-size:.84rem;font-weight:700;color:#166534;margin-bottom:.25rem">Audio server-side
                            do chat</div>
                        <div style="font-size:.75rem;color:#4b5563;margin-bottom:.9rem">
                            Define explicitamente o fallback de voz do Meu Assistente quando o navegador não suportar
                            STT/TTS nativo.
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem">
                            <div>
                                <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Modelo de
                                    transcricao</label>
                                <select name="openai_audio_transcription_model"
                                    style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                                    @foreach (['gpt-4o-mini-transcribe' => 'GPT-4o Mini Transcribe (recomendado)', 'whisper-1' => 'Whisper 1 (legado)'] as $val => $label)
                                        <option value="{{ $val }}"
                                            {{ $ai['openai_audio_transcription_model'] === $val ? 'selected' : '' }}>
                                            {{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Modelo de
                                    fala</label>
                                <select name="openai_audio_speech_model"
                                    style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                                    @foreach (['gpt-4o-mini-tts' => 'GPT-4o Mini TTS (recomendado)', 'tts-1' => 'TTS-1', 'tts-1-hd' => 'TTS-1 HD'] as $val => $label)
                                        <option value="{{ $val }}"
                                            {{ $ai['openai_audio_speech_model'] === $val ? 'selected' : '' }}>
                                            {{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Voz do
                                    chat</label>
                                <select name="openai_audio_voice"
                                    style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                                    @foreach (['alloy' => 'Alloy', 'echo' => 'Echo', 'fable' => 'Fable', 'onyx' => 'Onyx', 'nova' => 'Nova', 'shimmer' => 'Shimmer'] as $val => $label)
                                        <option value="{{ $val }}"
                                            {{ $ai['openai_audio_voice'] === $val ? 'selected' : '' }}>
                                            {{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">TTL do
                                    cache de audio (minutos)</label>
                                <input type="number" min="5" max="1440"
                                    name="openai_audio_cache_ttl_minutes"
                                    value="{{ $ai['openai_audio_cache_ttl_minutes'] }}"
                                    style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                                <div style="font-size:.75rem;color:#6b7280;margin-top:.3rem">
                                    Mantem audio temporario para replay e reutilizacao do fallback server-side.
                                </div>
                            </div>
                        </div>
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

        <form method="POST" action="{{ route('admin.settings.operational') }}" style="margin-top:2rem">
            @csrf

            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
                <div
                    style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid #f3f4f6">
                    <div>
                        <h3 style="font-size:.95rem;font-weight:600">SMTP operacional do sistema</h3>
                        <p style="font-size:.8rem;color:#6b7280;margin-top:.2rem">
                            Configura o envio real de e-mails do Radar sem depender exclusivamente de `MAIL_*` no `.env`.
                        </p>
                    </div>
                    <div style="text-align:right">
                        <div style="font-size:.76rem;color:#6b7280">Mailer ativo</div>
                        <div style="font-size:.88rem;font-weight:700;color:#111827">
                            {{ $mailRuntimeStatus['active_mailer'] }}</div>
                        <div
                            style="font-size:.74rem;color:{{ $mailRuntimeStatus['runtime_enabled'] ? '#047857' : '#9ca3af' }};margin-top:.2rem">
                            {{ $mailRuntimeStatus['runtime_enabled'] ? 'SMTP runtime ativo' : 'Fallback atual do Laravel' }}
                        </div>
                    </div>
                </div>

                <label style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;font-size:.85rem;color:#111827">
                    <input type="checkbox" name="mail_runtime_enabled" value="1"
                        {{ $mail['mail_runtime_enabled'] ? 'checked' : '' }}>
                    Ativar SMTP salvo no painel
                </label>

                <div
                    style="margin-bottom:1rem;padding:1rem;border:1px solid #fde68a;border-radius:10px;background:#fffbeb">
                    <div style="font-size:.84rem;font-weight:700;color:#92400e;margin-bottom:.35rem">Exemplo Gmail SMTP
                    </div>
                    <div style="font-size:.78rem;color:#b45309;line-height:1.6">
                        Host: <strong>`smtp.gmail.com`</strong> • Porta: <strong>`587`</strong> • Criptografia:
                        <strong>`tls`</strong><br>
                        Usuário: seu e-mail Google completo • Senha: use <strong>senha de app do Google</strong>, não a
                        senha normal da conta.
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Host SMTP</label>
                        <input type="text" name="mail_runtime_host" value="{{ $mail['mail_runtime_host'] }}"
                            placeholder="smtp.gmail.com"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Porta</label>
                        <input type="number" name="mail_runtime_port" value="{{ $mail['mail_runtime_port'] }}"
                            min="1" max="65535"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Usuário
                            SMTP</label>
                        <input type="text" name="mail_runtime_username" value="{{ $mail['mail_runtime_username'] }}"
                            placeholder="seuemail@gmail.com"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Senha
                            SMTP</label>
                        <input type="password" name="mail_runtime_password" placeholder="deixe em branco para manter"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                        @if (!empty($mail['mail_runtime_password']))
                            <div style="font-size:.75rem;color:#16a34a;margin-top:.3rem">✓ Senha SMTP já configurada</div>
                        @endif
                    </div>
                    <div>
                        <label
                            style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Criptografia</label>
                        <select name="mail_runtime_encryption"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                            @foreach (['tls' => 'TLS (recomendado)', 'ssl' => 'SSL', 'none' => 'Sem criptografia'] as $value => $label)
                                <option value="{{ $value }}"
                                    {{ $mail['mail_runtime_encryption'] === $value ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Timeout
                            (segundos)</label>
                        <input type="number" name="mail_runtime_timeout" value="{{ $mail['mail_runtime_timeout'] }}"
                            min="5" max="120"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Remetente</label>
                        <input type="email" name="mail_runtime_from_address"
                            value="{{ $mail['mail_runtime_from_address'] }}" placeholder="alertas@seudominio.com"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Nome do
                            remetente</label>
                        <input type="text" name="mail_runtime_from_name"
                            value="{{ $mail['mail_runtime_from_name'] }}" placeholder="Meu Assistente"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Domínio
                            EHLO</label>
                        <input type="text" name="mail_runtime_ehlo_domain"
                            value="{{ $mail['mail_runtime_ehlo_domain'] }}" placeholder="seudominio.com.br"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">E-mail para
                            teste</label>
                        <input type="email" id="mail-runtime-test-recipient" name="mail_runtime_test_recipient"
                            value="{{ $mail['mail_runtime_test_recipient'] }}" placeholder="voce@empresa.com"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                </div>

                <div style="display:flex;gap:.65rem;flex-wrap:wrap;margin-top:1rem">
                    <button type="button" onclick="testSmtpRuntime()"
                        style="padding:.5rem .95rem;background:#fff;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem;cursor:pointer">
                        Testar SMTP
                    </button>
                    <button type="button" onclick="applyGoogleMailPreset()"
                        style="padding:.5rem .95rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:8px;font-size:.82rem;cursor:pointer">
                        Preencher Gmail
                    </button>
                </div>
                <div id="test-mail-runtime"
                    style="display:none;margin-top:.75rem;padding:.75rem;border-radius:8px;font-size:.82rem"></div>
            </div>

            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1.5rem">
                <div
                    style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid #f3f4f6">
                    <div>
                        <h3 style="font-size:.95rem;font-weight:600">Operação do Radar de Recursos</h3>
                        <p style="font-size:.8rem;color:#6b7280;margin-top:.2rem">
                            Define para quem vão os snapshots diários e semanais, além dos horários operacionais.
                        </p>
                    </div>
                </div>

                <label
                    style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;font-size:.85rem;color:#111827">
                    <input type="checkbox" name="radar_sync_snapshot_enabled" value="1"
                        {{ $radarOps['radar_sync_snapshot_enabled'] ? 'checked' : '' }}>
                    Ativar snapshots do Radar por e-mail
                </label>

                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem">
                    <label style="display:flex;align-items:center;gap:.5rem;font-size:.82rem;color:#111827">
                        <input type="checkbox" name="radar_sync_snapshot_daily_enabled" value="1"
                            {{ $radarOps['radar_sync_snapshot_daily_enabled'] ? 'checked' : '' }}>
                        Snapshot diário
                    </label>
                    <label style="display:flex;align-items:center;gap:.5rem;font-size:.82rem;color:#111827">
                        <input type="checkbox" name="radar_sync_snapshot_weekly_enabled" value="1"
                            {{ $radarOps['radar_sync_snapshot_weekly_enabled'] ? 'checked' : '' }}>
                        Snapshot semanal
                    </label>
                    <div style="grid-column:1/-1">
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Destinatários
                            internos</label>
                        <textarea name="radar_sync_snapshot_recipients" rows="3" placeholder="ops@empresa.com, gestao@empresa.com"
                            style="width:100%;padding:.7rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">{{ $radarOps['radar_sync_snapshot_recipients'] }}</textarea>
                        <div style="font-size:.74rem;color:#6b7280;margin-top:.3rem">
                            Separe múltiplos e-mails com vírgula.
                        </div>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Horário
                            diário</label>
                        <input type="time" name="radar_sync_snapshot_daily_time"
                            value="{{ $radarOps['radar_sync_snapshot_daily_time'] }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Horário
                            semanal</label>
                        <input type="time" name="radar_sync_snapshot_weekly_time"
                            value="{{ $radarOps['radar_sync_snapshot_weekly_time'] }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Dia
                            semanal</label>
                        <select name="radar_sync_snapshot_weekly_day"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                            @foreach ([0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado'] as $value => $label)
                                <option value="{{ $value }}"
                                    {{ (int) $radarOps['radar_sync_snapshot_weekly_day'] === (int) $value ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display:flex;gap:.65rem;flex-wrap:wrap;margin-top:1rem">
                    <button type="button" onclick="testRadarSnapshot('daily')"
                        style="padding:.5rem .95rem;background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;border-radius:8px;font-size:.82rem;cursor:pointer">
                        Testar snapshot diário
                    </button>
                    <button type="button" onclick="testRadarSnapshot('weekly')"
                        style="padding:.5rem .95rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:8px;font-size:.82rem;cursor:pointer">
                        Testar snapshot semanal
                    </button>
                </div>
                <div id="test-radar-snapshot"
                    style="display:none;margin-top:.75rem;padding:.75rem;border-radius:8px;font-size:.82rem"></div>
            </div>

            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1.5rem">
                <div
                    style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid #f3f4f6">
                    <div>
                        <h3 style="font-size:.95rem;font-weight:600">Mailing executivo da cobertura</h3>
                        <p style="font-size:.8rem;color:#6b7280;margin-top:.2rem">
                            Define o envio periódico do ranking executivo com PDF gerencial em anexo.
                        </p>
                    </div>
                </div>

                <label
                    style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;font-size:.85rem;color:#111827">
                    <input type="checkbox" name="coverage_executive_mail_enabled" value="1"
                        {{ $coverageOps['coverage_executive_mail_enabled'] ? 'checked' : '' }}>
                    Ativar mailing executivo da cobertura
                </label>
                <label style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;font-size:.82rem;color:#374151">
                    <input type="checkbox" name="coverage_executive_mail_requires_approval" value="1"
                        {{ $coverageOps['coverage_executive_mail_requires_approval'] ? 'checked' : '' }}>
                    Exigir aprovação manual antes do disparo agendado
                </label>
                <label
                    style="display:flex;align-items:center;gap:.5rem;margin-bottom:.45rem;font-size:.82rem;color:#374151">
                    <input type="checkbox" name="coverage_executive_mail_two_level_approval" value="1"
                        {{ $coverageOps['coverage_executive_mail_two_level_approval'] ? 'checked' : '' }}>
                    Exigir aprovação em dois níveis no mailing executivo
                </label>
                <label style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;font-size:.82rem;color:#374151">
                    <input type="checkbox" name="coverage_executive_mail_distinct_approvers" value="1"
                        {{ $coverageOps['coverage_executive_mail_distinct_approvers'] ? 'checked' : '' }}>
                    Exigir aprovadores distintos entre os dois níveis
                </label>

                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem">
                    <label style="display:flex;align-items:center;gap:.5rem;font-size:.82rem;color:#111827">
                        <input type="checkbox" name="coverage_executive_mail_daily_enabled" value="1"
                            {{ $coverageOps['coverage_executive_mail_daily_enabled'] ? 'checked' : '' }}>
                        Envio diário
                    </label>
                    <label style="display:flex;align-items:center;gap:.5rem;font-size:.82rem;color:#111827">
                        <input type="checkbox" name="coverage_executive_mail_weekly_enabled" value="1"
                            {{ $coverageOps['coverage_executive_mail_weekly_enabled'] ? 'checked' : '' }}>
                        Envio semanal
                    </label>
                    <div style="grid-column:1/-1">
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Destinatários
                            executivos</label>
                        <textarea name="coverage_executive_mail_recipients" rows="3"
                            placeholder="gestao@empresa.com, diretoria@empresa.com"
                            style="width:100%;padding:.7rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">{{ $coverageOps['coverage_executive_mail_recipients'] }}</textarea>
                        <div style="font-size:.74rem;color:#6b7280;margin-top:.3rem">
                            Separe múltiplos e-mails com vírgula.
                        </div>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Horário
                            diário</label>
                        <input type="time" name="coverage_executive_mail_daily_time"
                            value="{{ $coverageOps['coverage_executive_mail_daily_time'] }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Horário
                            semanal</label>
                        <input type="time" name="coverage_executive_mail_weekly_time"
                            value="{{ $coverageOps['coverage_executive_mail_weekly_time'] }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Dia
                            semanal</label>
                        <select name="coverage_executive_mail_weekly_day"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                            @foreach ([0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado'] as $value => $label)
                                <option value="{{ $value }}"
                                    {{ (int) $coverageOps['coverage_executive_mail_weekly_day'] === (int) $value ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Limite do
                            ranking</label>
                        <input type="number" name="coverage_executive_mail_ranking_limit" min="5" max="50"
                            value="{{ $coverageOps['coverage_executive_mail_ranking_limit'] }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Rótulo do
                            nível 1</label>
                        <input type="text" name="coverage_executive_mail_level_one_label"
                            value="{{ $coverageOps['coverage_executive_mail_level_one_label'] }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Rótulo do
                            nível 2</label>
                        <input type="text" name="coverage_executive_mail_level_two_label"
                            value="{{ $coverageOps['coverage_executive_mail_level_two_label'] }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Nome
                            institucional</label>
                        <input type="text" name="coverage_executive_mail_identity_name"
                            value="{{ $coverageOps['coverage_executive_mail_identity_name'] }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label
                            style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Departamento</label>
                        <input type="text" name="coverage_executive_mail_identity_department"
                            value="{{ $coverageOps['coverage_executive_mail_identity_department'] }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div style="grid-column:1/-1">
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Tagline
                            institucional</label>
                        <input type="text" name="coverage_executive_mail_identity_tagline"
                            value="{{ $coverageOps['coverage_executive_mail_identity_tagline'] }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div style="grid-column:1/-1">
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Logo
                            institucional</label>
                        <input type="text" name="coverage_executive_mail_identity_logo"
                            value="{{ $coverageOps['coverage_executive_mail_identity_logo'] }}"
                            placeholder="/images/logo-borda-black.png"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                        <div style="font-size:.74rem;color:#6b7280;margin-top:.3rem">Use um caminho público da aplicação
                            para o cabeçalho do PDF.</div>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Cor
                            principal</label>
                        <input type="text" name="coverage_executive_mail_identity_accent_color"
                            value="{{ $coverageOps['coverage_executive_mail_identity_accent_color'] }}"
                            placeholder="#111827"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Cor
                            secundária</label>
                        <input type="text" name="coverage_executive_mail_identity_secondary_color"
                            value="{{ $coverageOps['coverage_executive_mail_identity_secondary_color'] }}"
                            placeholder="#1D4ED8"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Assinatura
                            principal</label>
                        <input type="text" name="coverage_executive_mail_signature_primary_name"
                            value="{{ $coverageOps['coverage_executive_mail_signature_primary_name'] }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Cargo da
                            assinatura principal</label>
                        <input type="text" name="coverage_executive_mail_signature_primary_role"
                            value="{{ $coverageOps['coverage_executive_mail_signature_primary_role'] }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Assinatura
                            secundária</label>
                        <input type="text" name="coverage_executive_mail_signature_secondary_name"
                            value="{{ $coverageOps['coverage_executive_mail_signature_secondary_name'] }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Cargo da
                            assinatura secundária</label>
                        <input type="text" name="coverage_executive_mail_signature_secondary_role"
                            value="{{ $coverageOps['coverage_executive_mail_signature_secondary_role'] }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                </div>

                <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid #f3f4f6">
                    <div style="font-size:.9rem;font-weight:700;color:#111827;margin-bottom:.3rem">SLA do owner por perfil
                    </div>
                    <div style="font-size:.78rem;color:#6b7280;margin-bottom:.85rem">
                        Define a meta padrão por severidade e o aviso prévio das notificações de vencimento iminente.
                    </div>
                    <label
                        style="display:flex;align-items:center;gap:.5rem;margin-bottom:.85rem;font-size:.82rem;color:#374151">
                        <input type="checkbox" name="coverage_alert_owner_notifications_enabled" value="1"
                            {{ $coverageOps['coverage_alert_owner_notifications_enabled'] ? 'checked' : '' }}>
                        Ativar notificações automáticas de vencimento iminente do owner
                    </label>
                    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem">
                        <div>
                            <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.3rem">Aviso
                                prévio</label>
                            <input type="number" name="coverage_alert_owner_warning_minutes" min="15"
                                max="720" value="{{ $coverageOps['coverage_alert_owner_warning_minutes'] }}"
                                style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                        </div>
                        @foreach ([
            ['key' => 'coverage_alert_owner_sla_high_hours', 'label' => 'Base alta'],
            ['key' => 'coverage_alert_owner_sla_medium_hours', 'label' => 'Base média'],
            ['key' => 'coverage_alert_owner_sla_default_hours', 'label' => 'Base baixa'],
            ['key' => 'coverage_alert_owner_sla_admin_high_hours', 'label' => 'Admin alta'],
            ['key' => 'coverage_alert_owner_sla_admin_medium_hours', 'label' => 'Admin média'],
            ['key' => 'coverage_alert_owner_sla_admin_default_hours', 'label' => 'Admin baixa'],
            ['key' => 'coverage_alert_owner_sla_mayor_high_hours', 'label' => 'Prefeito alta'],
            ['key' => 'coverage_alert_owner_sla_mayor_medium_hours', 'label' => 'Prefeito média'],
            ['key' => 'coverage_alert_owner_sla_mayor_default_hours', 'label' => 'Prefeito baixa'],
            ['key' => 'coverage_alert_owner_sla_secretary_high_hours', 'label' => 'Secretário alta'],
            ['key' => 'coverage_alert_owner_sla_secretary_medium_hours', 'label' => 'Secretário média'],
            ['key' => 'coverage_alert_owner_sla_secretary_default_hours', 'label' => 'Secretário baixa'],
            ['key' => 'coverage_alert_owner_sla_advisor_high_hours', 'label' => 'Assessor alta'],
            ['key' => 'coverage_alert_owner_sla_advisor_medium_hours', 'label' => 'Assessor média'],
            ['key' => 'coverage_alert_owner_sla_advisor_default_hours', 'label' => 'Assessor baixa'],
        ] as $field)
                            <div>
                                <label
                                    style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.3rem">{{ $field['label'] }}</label>
                                <input type="number" name="{{ $field['key'] }}" min="1" max="240"
                                    value="{{ $coverageOps[$field['key']] }}"
                                    style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                            </div>
                        @endforeach
                    </div>
                </div>

                <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid #f3f4f6">
                    <div style="font-size:.9rem;font-weight:700;color:#111827;margin-bottom:.3rem">Override por owner</div>
                    <div style="font-size:.78rem;color:#6b7280;margin-bottom:.85rem">
                        Valores em horas. Deixe `0` para usar o SLA do perfil correspondente.
                    </div>
                    <div style="display:grid;gap:.7rem">
                        @foreach ($coverageOwnerSlaUsers as $ownerUser)
                            <div
                                style="display:grid;grid-template-columns:1.2fr repeat(3,minmax(0,140px));gap:.7rem;align-items:end;padding:.85rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                                <div>
                                    <div style="font-size:.83rem;font-weight:700;color:#111827">{{ $ownerUser['name'] }}
                                    </div>
                                    <div style="font-size:.75rem;color:#6b7280">{{ $ownerUser['role'] }}</div>
                                </div>
                                <div>
                                    <label
                                        style="display:block;font-size:.75rem;font-weight:600;margin-bottom:.25rem">Alta</label>
                                    <input type="number"
                                        name="coverage_owner_sla_overrides[{{ $ownerUser['id'] }}][high]" min="0"
                                        max="240" value="{{ $ownerUser['sla']['high'] }}"
                                        style="width:100%;padding:.55rem .7rem;border:1px solid #d1d5db;border-radius:8px;font-size:.84rem">
                                </div>
                                <div>
                                    <label
                                        style="display:block;font-size:.75rem;font-weight:600;margin-bottom:.25rem">Média</label>
                                    <input type="number"
                                        name="coverage_owner_sla_overrides[{{ $ownerUser['id'] }}][medium]"
                                        min="0" max="240" value="{{ $ownerUser['sla']['medium'] }}"
                                        style="width:100%;padding:.55rem .7rem;border:1px solid #d1d5db;border-radius:8px;font-size:.84rem">
                                </div>
                                <div>
                                    <label
                                        style="display:block;font-size:.75rem;font-weight:600;margin-bottom:.25rem">Baixa</label>
                                    <input type="number"
                                        name="coverage_owner_sla_overrides[{{ $ownerUser['id'] }}][default]"
                                        min="0" max="240" value="{{ $ownerUser['sla']['default'] }}"
                                        style="width:100%;padding:.55rem .7rem;border:1px solid #d1d5db;border-radius:8px;font-size:.84rem">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:.5rem">
                <button type="submit"
                    style="padding:.7rem 2rem;background:#0f1117;color:#fff;border:none;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer">
                    Salvar SMTP, Radar e Cobertura
                </button>
            </div>
        </form>

        <div style="margin-top:2rem;background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb">
            <div
                style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid #f3f4f6">
                <div>
                    <h3 style="font-size:.95rem;font-weight:600">Auditoria operacional do Radar</h3>
                    <p style="font-size:.8rem;color:#6b7280;margin-top:.2rem">
                        Histórico de alterações em SMTP, destinatários e horários do Radar, com rollback seguro por
                        snapshot.
                    </p>
                </div>
            </div>

            <div style="display:grid;gap:.9rem">
                @forelse ($radarOperationalHistory as $entry)
                    <div style="border:1px solid #e5e7eb;border-radius:12px;padding:1rem 1.05rem;background:#fcfcfd">
                        <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start">
                            <div>
                                <div style="font-size:.88rem;font-weight:700;color:#111827">{{ $entry['description'] }}
                                </div>
                                <div style="font-size:.74rem;color:#6b7280;margin-top:.2rem">
                                    {{ $entry['causer_name'] }}
                                    @if ($entry['causer_email'])
                                        • {{ $entry['causer_email'] }}
                                    @endif
                                    • {{ $entry['created_at_human'] ?? 'agora' }}
                                    • ID #{{ $entry['id'] }}
                                </div>
                            </div>
                            <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;justify-content:flex-end">
                                <span
                                    style="padding:.2rem .55rem;border-radius:999px;font-size:.72rem;font-weight:700;background:{{ $entry['event'] === 'rollback' ? '#eff6ff' : '#ecfdf5' }};color:{{ $entry['event'] === 'rollback' ? '#1d4ed8' : '#047857' }}">
                                    {{ $entry['event'] === 'rollback' ? 'Rollback' : 'Atualização' }}
                                </span>
                                @if ($entry['can_rollback'])
                                    <button type="button" onclick="rollbackRadarOperational({{ $entry['id'] }})"
                                        style="padding:.4rem .8rem;background:#fff7ed;color:#c2410c;border:1px solid #fdba74;border-radius:8px;font-size:.76rem;font-weight:700;cursor:pointer">
                                        Restaurar este snapshot
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if (!empty($entry['changed_keys']))
                            <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.75rem">
                                @foreach ($entry['changed_keys'] as $changedKey)
                                    <span
                                        style="padding:.22rem .55rem;border-radius:999px;background:#f3f4f6;color:#4b5563;font-size:.72rem;font-weight:600">
                                        {{ $changedKey }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <details style="margin-top:.8rem">
                            <summary style="cursor:pointer;color:#3730a3;font-size:.78rem;font-weight:700">
                                Ver antes e depois
                            </summary>
                            <div
                                style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.85rem;margin-top:.75rem">
                                <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:.85rem">
                                    <div style="font-size:.76rem;font-weight:700;color:#6b7280;margin-bottom:.5rem">Antes
                                    </div>
                                    <div style="display:grid;gap:.35rem">
                                        @foreach ($entry['before_masked'] as $key => $value)
                                            <div style="font-size:.74rem;color:#374151">
                                                <strong>{{ $key }}</strong>:
                                                {{ is_array($value) ? implode(', ', $value) : ($value === '' ? '—' : $value) }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:.85rem">
                                    <div style="font-size:.76rem;font-weight:700;color:#6b7280;margin-bottom:.5rem">Depois
                                    </div>
                                    <div style="display:grid;gap:.35rem">
                                        @foreach ($entry['after_masked'] as $key => $value)
                                            <div style="font-size:.74rem;color:#374151">
                                                <strong>{{ $key }}</strong>:
                                                {{ is_array($value) ? implode(', ', $value) : ($value === '' ? '—' : $value) }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </details>
                    </div>
                @empty
                    <div
                        style="padding:1rem;border:1px dashed #d1d5db;border-radius:10px;text-align:center;font-size:.82rem;color:#9ca3af">
                        Ainda não há alterações auditadas para SMTP e operação do Radar.
                    </div>
                @endforelse
            </div>
        </div>
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

        function applyGoogleMailPreset() {
            document.querySelector('input[name="mail_runtime_host"]').value = 'smtp.gmail.com';
            document.querySelector('input[name="mail_runtime_port"]').value = '587';
            document.querySelector('select[name="mail_runtime_encryption"]').value = 'tls';
        }

        async function testSmtpRuntime() {
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const el = document.getElementById('test-mail-runtime');
            const recipient = document.getElementById('mail-runtime-test-recipient').value;

            el.style.display = 'block';
            el.style.background = '#f3f4f6';
            el.style.color = '#374151';
            el.textContent = 'Testando SMTP...';

            try {
                const res = await fetch('{{ route('admin.settings.mail.test') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    },
                    body: JSON.stringify({
                        recipient
                    })
                });
                const data = await res.json();
                if (data.success) {
                    el.style.background = '#d1fae5';
                    el.style.color = '#065f46';
                    el.textContent = `✓ SMTP OK — enviado para ${data.recipient} via ${data.mailer}`;
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

        async function testRadarSnapshot(period) {
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const el = document.getElementById('test-radar-snapshot');
            const recipient = document.getElementById('mail-runtime-test-recipient').value;

            el.style.display = 'block';
            el.style.background = '#f3f4f6';
            el.style.color = '#374151';
            el.textContent = `Testando snapshot ${period === 'weekly' ? 'semanal' : 'diário'}...`;

            try {
                const res = await fetch('{{ route('admin.settings.radar-snapshot.test') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    },
                    body: JSON.stringify({
                        recipient,
                        period
                    })
                });
                const data = await res.json();
                if (data.success) {
                    el.style.background = '#d1fae5';
                    el.style.color = '#065f46';
                    el.textContent =
                        `✓ Snapshot ${period} enviado para ${data.recipient}. Falhas: ${data.summary.failed}, stale: ${data.summary.stale}, retries: ${data.summary.retried}`;
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

        async function rollbackRadarOperational(activityId) {
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;

            if (!confirm('Restaurar este snapshot operacional do Radar e do SMTP?')) return;

            try {
                const res = await fetch(`{{ url('/admin/settings/operational') }}/${activityId}/rollback`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    },
                    body: JSON.stringify({})
                });
                const data = await res.json();

                if (data.ok) {
                    window.location.reload();
                    return;
                }

                alert(data.message || 'Não foi possível restaurar o snapshot.');
            } catch (e) {
                alert('Erro ao restaurar snapshot: ' + e.message);
            }
        }
    </script>
@endsection
