@extends('layouts.admin')
@section('title', 'Onboarding — ' . $municipality->name)
@section('content')
    <div style="padding:2rem;">
        <div style="margin-bottom:1.5rem">
            <a href="{{ route('admin.municipalities.index') }}" style="font-size:.85rem;color:#6b7280;text-decoration:none">←
                Municípios</a>
            <h1 style="font-size:1.4rem;font-weight:700;margin-top:.5rem">Onboarding — {{ $municipality->name }}</h1>
            <p style="color:#6b7280;font-size:.88rem;margin-top:.3rem">Configure o perfil do município para ativar o
                assistente do prefeito.</p>
        </div>

        @if (session('success'))
            <div
                style="background:#d1fae5;border:1px solid #6ee7b7;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#065f46">
                {{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div
                style="background:#fee2e2;border:1px solid #fca5a5;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#991b1b">
                {{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div
                style="background:#fff7ed;border:1px solid #fdba74;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#9a3412">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Progresso --}}
        @php
            $municipalityCoreOk =
                !empty($municipalityProfile['mayor_full_name']) &&
                !empty($municipalityProfile['party']) &&
                !empty($municipalityProfile['term_start_date']) &&
                !empty($municipalityProfile['term_end_date']);
            $voiceOk = !empty($municipality->voice_profile);
            $mapOk = !empty($municipality->political_map);
            $communicationContextOk =
                collect($communicationContext['channels'] ?? [])->contains(
                    fn($channel) => !empty($channel['active']),
                ) && !empty($communicationContext['monitoring_terms']);
            $notificationsOk = !empty($notificationSettings['pra_hoje']['delivery_time'] ?? null);
            $resolveAiOpsOk =
                ($resolveAiOperationalSummary['areas_ready'] ?? 0) > 0 &&
                ($resolveAiOperationalSummary['localities_active'] ?? 0) > 0;
            $resolveAiOk = !empty($resolveAiSettings) && $resolveAiOpsOk;
        @endphp
        <div style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:1rem;margin-bottom:2rem">
            <div
                style="flex:1;padding:.75rem 1rem;border-radius:8px;background:{{ $municipalityCoreOk ? '#dbeafe' : '#f3f4f6' }};border:1px solid {{ $municipalityCoreOk ? '#93c5fd' : '#e5e7eb' }}">
                <div style="font-size:.75rem;font-weight:600;color:{{ $municipalityCoreOk ? '#1d4ed8' : '#9ca3af' }}">BASE
                </div>
                <div style="font-size:.82rem;margin-top:.2rem;color:{{ $municipalityCoreOk ? '#1d4ed8' : '#6b7280' }}">
                    {{ $municipalityCoreOk ? '✓ Preenchida' : 'Pendente' }}</div>
            </div>
            <div
                style="flex:1;padding:.75rem 1rem;border-radius:8px;background:{{ $voiceOk ? '#d1fae5' : '#f3f4f6' }};border:1px solid {{ $voiceOk ? '#6ee7b7' : '#e5e7eb' }}">
                <div style="font-size:.75rem;font-weight:600;color:{{ $voiceOk ? '#065f46' : '#9ca3af' }}">PERFIL
                </div>
                <div style="font-size:.82rem;margin-top:.2rem;color:{{ $voiceOk ? '#065f46' : '#6b7280' }}">
                    {{ $voiceOk ? '✓ Concluído' : 'Pendente' }}</div>
            </div>
            <div
                style="flex:1;padding:.75rem 1rem;border-radius:8px;background:{{ $mapOk ? '#d1fae5' : '#f3f4f6' }};border:1px solid {{ $mapOk ? '#6ee7b7' : '#e5e7eb' }}">
                <div style="font-size:.75rem;font-weight:600;color:{{ $mapOk ? '#065f46' : '#9ca3af' }}">POLÍTICA
                </div>
                <div style="font-size:.82rem;margin-top:.2rem;color:{{ $mapOk ? '#065f46' : '#6b7280' }}">
                    {{ $mapOk ? '✓ Concluído' : 'Pendente' }}</div>
            </div>
            <div
                style="flex:1;padding:.75rem 1rem;border-radius:8px;background:{{ $communicationContextOk ? '#ede9fe' : '#f3f4f6' }};border:1px solid {{ $communicationContextOk ? '#c4b5fd' : '#e5e7eb' }}">
                <div style="font-size:.75rem;font-weight:600;color:{{ $communicationContextOk ? '#6d28d9' : '#9ca3af' }}">
                    COMUNICAÇÃO</div>
                <div style="font-size:.82rem;margin-top:.2rem;color:{{ $communicationContextOk ? '#6d28d9' : '#6b7280' }}">
                    {{ $communicationContextOk ? '✓ Configurada' : 'Pendente' }}</div>
            </div>
            <div
                style="flex:1;padding:.75rem 1rem;border-radius:8px;background:{{ $resolveAiOk ? '#dbeafe' : '#f3f4f6' }};border:1px solid {{ $resolveAiOk ? '#93c5fd' : '#e5e7eb' }}">
                <div style="font-size:.75rem;font-weight:600;color:{{ $resolveAiOk ? '#1d4ed8' : '#9ca3af' }}">RESOLVE AI
                </div>
                <div style="font-size:.82rem;margin-top:.2rem;color:{{ $resolveAiOk ? '#1d4ed8' : '#6b7280' }}">
                    {{ $resolveAiOk ? '✓ Configurado' : 'Pendente' }}</div>
            </div>
            <div
                style="flex:1;padding:.75rem 1rem;border-radius:8px;background:{{ $municipality->onboarding_status === 'completed' ? '#d1fae5' : '#f3f4f6' }};border:1px solid {{ $municipality->onboarding_status === 'completed' ? '#6ee7b7' : '#e5e7eb' }}">
                <div
                    style="font-size:.75rem;font-weight:600;color:{{ $municipality->onboarding_status === 'completed' ? '#065f46' : '#9ca3af' }}">
                    ATIVAÇÃO</div>
                <div
                    style="font-size:.82rem;margin-top:.2rem;color:{{ $municipality->onboarding_status === 'completed' ? '#065f46' : '#6b7280' }}">
                    {{ $municipality->onboarding_status === 'completed' ? '✓ Ativo' : 'Pendente' }}</div>
            </div>
        </div>

        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
            <h3 style="font-size:1rem;font-weight:600;margin-bottom:1rem">Base institucional do município</h3>
            <p style="font-size:.85rem;color:#6b7280;margin-bottom:1rem">Consolida dados da prefeitura, do mandato e do
                contexto local que alimentam todos os módulos.</p>
            <form method="POST"
                action="{{ route('admin.municipalities.onboarding.municipality-profile', $municipality) }}">
                @csrf
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:1rem">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Bioma
                            predominante</label>
                        <input name="biome" value="{{ $municipalityProfile['biome'] ?? '' }}" placeholder="Ex: Cerrado"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Nome completo do
                            prefeito</label>
                        <input name="mayor_full_name" value="{{ $municipalityProfile['mayor_full_name'] ?? '' }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Nome de tratamento
                            preferido</label>
                        <input name="mayor_preferred_name" value="{{ $municipalityProfile['mayor_preferred_name'] ?? '' }}"
                            placeholder="Ex: Prefeito João"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Partido</label>
                        <input name="party" value="{{ $municipalityProfile['party'] ?? '' }}" placeholder="Ex: PSD"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Início do
                            mandato</label>
                        <input type="date" name="term_start_date"
                            value="{{ $municipalityProfile['term_start_date'] ?? '' }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Fim do
                            mandato</label>
                        <input type="date" name="term_end_date"
                            value="{{ $municipalityProfile['term_end_date'] ?? '' }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Economia
                            principal</label>
                        <input name="economy_primary" value="{{ $municipalityProfile['economy_primary'] ?? '' }}"
                            placeholder="Ex: agronegócio, turismo"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Principais
                            desafios locais</label>
                        <input name="local_challenges" value="{{ $municipalityProfile['local_challenges'] ?? '' }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Potenciais do
                            município</label>
                        <input name="local_potentials" value="{{ $municipalityProfile['local_potentials'] ?? '' }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                </div>
                <div style="display:grid;gap:.75rem;margin-bottom:1rem">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Resumo do programa
                            de governo</label>
                        <textarea name="government_summary" rows="3"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipalityProfile['government_summary'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Projetos
                            prioritários em andamento</label>
                        <textarea name="priority_projects" rows="3"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipalityProfile['priority_projects'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Metas
                            quantitativas principais</label>
                        <textarea name="quantitative_goals" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipalityProfile['quantitative_goals'] ?? '' }}</textarea>
                    </div>
                </div>
                <button type="submit"
                    style="padding:.6rem 1.2rem;background:#111827;color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">Salvar
                    base institucional</button>
            </form>
        </div>

        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1rem">
                <div>
                    <h3 style="font-size:1rem;font-weight:600;margin-bottom:.35rem">Perfis de usuário e permissões</h3>
                    <p style="font-size:.85rem;color:#6b7280">A gestão detalhada dos acessos já existe no módulo de
                        usuários. Aqui fica o atalho operacional para fechar o onboarding.</p>
                </div>
                <a href="{{ route('admin.users.index') }}"
                    style="padding:.55rem .9rem;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem;text-decoration:none;color:#374151">Gerir
                    usuários</a>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:1rem">
                <div style="padding:.85rem 1rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                    <div
                        style="font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.08em">
                        Usuários ativos</div>
                    <div style="font-size:1.25rem;font-weight:700;color:#111827;margin-top:.35rem">
                        {{ $municipalUsers->count() }}</div>
                </div>
                <div style="padding:.85rem 1rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                    <div
                        style="font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.08em">
                        Prefeito vinculado</div>
                    <div style="font-size:.95rem;font-weight:700;color:#111827;margin-top:.35rem">
                        {{ $mayor?->name ?? 'Pendente' }}</div>
                </div>
                <div style="padding:.85rem 1rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                    <div
                        style="font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.08em">
                        Resumo</div>
                    <div style="font-size:.82rem;color:#374151;margin-top:.35rem">Prefeitos, secretários e assessores são
                        geridos em `Admin > Usuários`.</div>
                </div>
            </div>
            @if ($municipalUsers->isNotEmpty())
                <div style="display:flex;flex-direction:column;gap:.45rem;font-size:.82rem;color:#374151">
                    @foreach ($municipalUsers->take(5) as $user)
                        <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap">
                            <span>{{ $user->name }}</span>
                            <span style="color:#6b7280">{{ strtoupper($user->role->value ?? $user->role) }} ·
                                {{ $user->email }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Perfil de Voz --}}
        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
            <h3 style="font-size:1rem;font-weight:600;margin-bottom:1rem">Perfil estratégico e voz do prefeito</h3>
            <p style="font-size:.85rem;color:#6b7280;margin-bottom:1rem">Define como o assistente vai se comunicar em nome
                do prefeito — tom, estilo e vocabulário.</p>
            <form method="POST" action="{{ route('admin.municipalities.onboarding.voice-profile', $municipality) }}">
                @csrf
                <div style="display:grid;gap:.75rem;margin-bottom:1rem">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Tom de
                            voz</label>
                        <input name="tone" value="{{ $municipality->voice_profile['tone'] ?? '' }}"
                            placeholder="ex: próximo e acessível"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Estilo de
                            comunicação</label>
                        <input name="style" value="{{ $municipality->voice_profile['style'] ?? '' }}"
                            placeholder="ex: informal mas respeitoso"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label
                            style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Vocabulário</label>
                        <input name="vocabulary" value="{{ $municipality->voice_profile['vocabulary'] ?? '' }}"
                            placeholder="ex: simples, sem tecnicismos"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Temas
                            prioritários</label>
                        <input name="priority_themes" value="{{ $municipality->voice_profile['priority_themes'] ?? '' }}"
                            placeholder="ex: saúde, obras, educação, prestação de contas"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Evitar</label>
                        <input name="avoid" value="{{ $municipality->voice_profile['avoid'] ?? '' }}"
                            placeholder="ex: jargões políticos, termos técnicos"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Posicionamento
                            político predominante</label>
                        <input name="political_positioning"
                            value="{{ $municipality->voice_profile['political_positioning'] ?? '' }}"
                            placeholder="ex: centro, tecnico-gestor, conservador"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Principais
                            bandeiras</label>
                        <input name="key_flags" value="{{ $municipality->voice_profile['key_flags'] ?? '' }}"
                            placeholder="ex: saude, educacao, zeladoria, emprego"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Temas que prefere
                            evitar publicamente</label>
                        <input name="avoid_public_topics"
                            value="{{ $municipality->voice_profile['avoid_public_topics'] ?? '' }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Histórico
                            político relevante</label>
                        <textarea name="historical_context" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipality->voice_profile['historical_context'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Adversários
                            políticos locais</label>
                        <textarea name="political_adversaries" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipality->voice_profile['political_adversaries'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Aliados políticos
                            locais</label>
                        <textarea name="political_allies" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipality->voice_profile['political_allies'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Referências de
                            comunicação</label>
                        <textarea name="communication_references" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipality->voice_profile['communication_references'] ?? '' }}</textarea>
                    </div>
                </div>
                <button type="submit"
                    style="padding:.6rem 1.2rem;background:var(--gold);color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">Salvar
                    Perfil de Voz</button>
            </form>
        </div>

        {{-- Mapa Político --}}
        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
            <h3 style="font-size:1rem;font-weight:600;margin-bottom:1rem">Mapa político local</h3>
            <form method="POST" action="{{ route('admin.municipalities.onboarding.political-map', $municipality) }}">
                @csrf
                <div style="display:grid;gap:.75rem;margin-bottom:1rem">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Vereadores
                            aliados</label>
                        <textarea name="allies" rows="2" placeholder="Nomes e partidos dos aliados"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipality->political_map['allies'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Neutros /
                            indecisos</label>
                        <textarea name="neutral" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipality->political_map['neutral'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Oposição</label>
                        <textarea name="opposition" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipality->political_map['opposition'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Observações
                            políticas</label>
                        <textarea name="notes" rows="3" placeholder="Contexto político local, alianças, tensões..."
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipality->political_map['notes'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Parlamentares
                            estaduais aliados</label>
                        <textarea name="state_allies" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipality->political_map['state_allies'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Parlamentares
                            federais aliados</label>
                        <textarea name="federal_allies" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipality->political_map['federal_allies'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Imprensa
                            local</label>
                        <textarea name="local_press" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipality->political_map['local_press'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Lideranças
                            comunitárias relevantes</label>
                        <textarea name="community_leaders" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipality->political_map['community_leaders'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Sensibilidades
                            políticas locais</label>
                        <textarea name="local_sensitivities" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipality->political_map['local_sensitivities'] ?? '' }}</textarea>
                    </div>
                </div>
                <button type="submit"
                    style="padding:.6rem 1.2rem;background:var(--gold);color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">Salvar
                    Mapa Político</button>
            </form>
        </div>

        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
            <h3 style="font-size:1rem;font-weight:600;margin-bottom:1rem">Configuração transversal de comunicação</h3>
            <p style="font-size:.85rem;color:#6b7280;margin-bottom:1rem">Define canais ativos, monitoramento, identidade
                visual, fornecedores e sensibilidades locais para o módulo `Comunicação`.</p>
            <form method="POST"
                action="{{ route('admin.municipalities.onboarding.communication-context', $municipality) }}">
                @csrf
                <div style="margin-bottom:1rem">
                    <div style="font-size:.82rem;font-weight:600;margin-bottom:.45rem">Canais ativos</div>
                    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.75rem">
                        @foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'whatsapp' => 'WhatsApp', 'youtube' => 'YouTube', 'tiktok' => 'TikTok'] as $channelKey => $channelLabel)
                            <div style="padding:.85rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                                <label
                                    style="display:flex;align-items:center;gap:.5rem;font-size:.82rem;font-weight:600;color:#111827;margin-bottom:.6rem">
                                    <input type="checkbox" name="communication_channel_{{ $channelKey }}_active"
                                        value="1" @checked($communicationContext['channels'][$channelKey]['active'] ?? false)>
                                    {{ $channelLabel }}
                                </label>
                                <input name="communication_channel_{{ $channelKey }}_url"
                                    value="{{ $communicationContext['channels'][$channelKey]['url'] ?? '' }}"
                                    placeholder="URL ou identificador"
                                    style="width:100%;padding:.55rem .7rem;border:1px solid #d1d5db;border-radius:8px;font-size:.8rem;box-sizing:border-box">
                            </div>
                        @endforeach
                    </div>
                </div>
                <div style="display:grid;gap:.75rem;margin-bottom:1rem">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Portais locais a
                            monitorar</label>
                        <textarea name="communication_monitoring_portals" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $communicationContext['monitoring_portals'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Termos de
                            monitoramento</label>
                        <textarea name="communication_monitoring_terms" rows="2"
                            placeholder="Ex: nome do prefeito, nome do municipio, obras, projetos prioritarios"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $communicationContext['monitoring_terms'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Responsável de
                            comunicação</label>
                        <select name="communication_responsible_user_id"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;background:#fff">
                            <option value="">Selecione</option>
                            @foreach ($municipalUsers as $user)
                                <option value="{{ $user->id }}" @selected(($communicationContext['responsible_user_id'] ?? null) == $user->id)>
                                    {{ $user->name }} · {{ strtoupper($user->role->value ?? $user->role) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:.75rem;margin-bottom:1rem">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Paleta de
                            cores</label>
                        <input name="communication_visual_palette"
                            value="{{ $communicationContext['visual_palette'] ?? '' }}"
                            placeholder="Ex: #1A3A5C, #D4AF37, #F3F4F6"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label
                            style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Tipografia</label>
                        <input name="communication_visual_typography"
                            value="{{ $communicationContext['visual_typography'] ?? '' }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Brasão ou
                            logotipo</label>
                        <input name="communication_visual_logo" value="{{ $communicationContext['visual_logo'] ?? '' }}"
                            placeholder="URL ou caminho de referência"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Estilo visual
                            predominante</label>
                        <input name="communication_visual_style"
                            value="{{ $communicationContext['visual_style'] ?? '' }}"
                            placeholder="Ex: institucional, moderno, comunitario"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                </div>
                <div style="display:grid;gap:.75rem;margin-bottom:1rem">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Referências
                            visuais</label>
                        <textarea name="communication_visual_references" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $communicationContext['visual_references'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Fornecedores e
                            recursos da equipe</label>
                        <textarea name="communication_suppliers_notes" rows="3"
                            placeholder="Cadastre os principais fornecedores, contatos e observacoes operacionais."
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $communicationContext['suppliers_notes'] ?? '' }}</textarea>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:.75rem;margin-bottom:1rem">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Temas
                            historicamente polêmicos</label>
                        <textarea name="communication_sensitivity_historical_topics" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $communicationContext['sensitivity_historical_topics'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Grupos ou
                            lideranças em tensão</label>
                        <textarea name="communication_sensitivity_tense_groups" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $communicationContext['sensitivity_tense_groups'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Obras ou projetos
                            polêmicos</label>
                        <textarea name="communication_sensitivity_controversial_projects" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $communicationContext['sensitivity_controversial_projects'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Temas eleitorais
                            sensíveis</label>
                        <textarea name="communication_sensitivity_electoral_topics" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $communicationContext['sensitivity_electoral_topics'] ?? '' }}</textarea>
                    </div>
                    <div style="grid-column:1 / -1">
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Histórico de
                            crises anteriores</label>
                        <textarea name="communication_sensitivity_crisis_history" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $communicationContext['sensitivity_crisis_history'] ?? '' }}</textarea>
                    </div>
                </div>
                <button type="submit"
                    style="padding:.6rem 1.2rem;background:#111827;color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">Salvar
                    contexto de comunicação</button>
            </form>
        </div>

        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
            <h3 style="font-size:1rem;font-weight:600;margin-bottom:1rem">Canais de notificação e Pra hoje</h3>
            <p style="font-size:.85rem;color:#6b7280;margin-bottom:1rem">Define os canais transversais do município e a
                configuração inicial do briefing diário do prefeito.</p>
            <form method="POST"
                action="{{ route('admin.municipalities.onboarding.notification-settings', $municipality) }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
                    <div style="padding:1rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                        <div style="font-size:.82rem;font-weight:600;margin-bottom:.5rem">Canais disponíveis</div>
                        <label
                            style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:#374151;margin-bottom:.45rem">
                            <input type="checkbox" name="notifications_channel_platform" value="1"
                                @checked($notificationSettings['channels']['platform'] ?? true)>
                            Notificação interna na plataforma
                        </label>
                        <label
                            style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:#374151;margin-bottom:.45rem">
                            <input type="checkbox" name="notifications_channel_email" value="1"
                                @checked($notificationSettings['channels']['email'] ?? false)>
                            E-mail
                        </label>
                        <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:#374151">
                            <input type="checkbox" name="notifications_channel_whatsapp" value="1"
                                @checked($notificationSettings['channels']['whatsapp'] ?? false)>
                            WhatsApp
                        </label>
                    </div>
                    <div style="padding:1rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                        <div style="font-size:.82rem;font-weight:600;margin-bottom:.5rem">Pra hoje</div>
                        <label
                            style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:#374151;margin-bottom:.55rem">
                            <input type="checkbox" name="pra_hoje_enabled" value="1" @checked($notificationSettings['pra_hoje']['enabled'] ?? true)>
                            Briefing ativo para o prefeito
                        </label>
                        <div style="margin-bottom:.55rem">
                            <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:.25rem">Horário de
                                entrega</label>
                            <input type="time" name="pra_hoje_delivery_time"
                                value="{{ $notificationSettings['pra_hoje']['delivery_time'] ?? '07:30' }}"
                                style="width:100%;padding:.55rem .7rem;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem;box-sizing:border-box">
                        </div>
                        <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:#374151">
                            <input type="checkbox" name="pra_hoje_email_enabled" value="1"
                                @checked($notificationSettings['pra_hoje']['email_enabled'] ?? false)>
                            Entregar também por e-mail
                        </label>
                    </div>
                </div>
                @if (!$mayor)
                    <div
                        style="padding:.85rem 1rem;border-radius:10px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;font-size:.82rem;line-height:1.55;margin-bottom:1rem">
                        Ainda não há um usuário com perfil de prefeito vinculado a este município. O horário do `Pra hoje`
                        será salvo plenamente quando esse usuário existir.
                    </div>
                @endif
                <button type="submit"
                    style="padding:.6rem 1.2rem;background:#111827;color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">Salvar
                    notificações</button>
            </form>
        </div>

        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
            <h3 style="font-size:1rem;font-weight:600;margin-bottom:1rem">Plano de Governo e Mandato</h3>
            <p style="font-size:.85rem;color:#6b7280;margin-bottom:1rem">
                Conforme o módulo `Mandato`, o plano de governo digitalizado entra no onboarding, passa por extração de
                compromissos por IA e vira uma lista revisável antes de ser salva como base permanente do módulo.
            </p>

            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:1rem">
                <div style="padding:.85rem 1rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                    <div
                        style="font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.08em">
                        Documentos do plano</div>
                    <div style="font-size:1.25rem;font-weight:700;color:#111827;margin-top:.35rem">
                        {{ $mandateSummary['documents_total'] ?? 0 }}</div>
                </div>
                <div style="padding:.85rem 1rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                    <div
                        style="font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.08em">
                        Compromissos salvos</div>
                    <div style="font-size:1.25rem;font-weight:700;color:#111827;margin-top:.35rem">
                        {{ $mandateSummary['promises_total'] ?? 0 }}</div>
                </div>
                <div style="padding:.85rem 1rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                    <div
                        style="font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.08em">
                        Prévia para revisão</div>
                    <div style="font-size:1.25rem;font-weight:700;color:#111827;margin-top:.35rem">
                        {{ $mandateSummary['preview_total'] ?? 0 }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.municipalities.onboarding.documents', $municipality) }}"
                enctype="multipart/form-data" style="margin-bottom:1rem">
                @csrf
                <div style="display:grid;grid-template-columns:minmax(0,1fr) auto;gap:.75rem;align-items:end">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Plano de governo
                            digitalizado</label>
                        <input type="file" name="government_plan_file" accept=".pdf,.doc,.docx,.txt"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;background:#fff">
                    </div>
                    <button type="submit"
                        style="padding:.68rem 1.1rem;background:#111827;color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">Enviar
                        e extrair</button>
                </div>
            </form>

            @if (($mandatePlanDocuments ?? collect())->isNotEmpty())
                <div
                    style="padding:.9rem 1rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa;margin-bottom:1rem">
                    <div style="font-size:.78rem;font-weight:700;color:#374151;margin-bottom:.55rem">Documentos já enviados
                    </div>
                    <div style="display:flex;flex-direction:column;gap:.45rem">
                        @foreach ($mandatePlanDocuments as $document)
                            <div
                                style="display:flex;justify-content:space-between;gap:1rem;font-size:.82rem;color:#374151;flex-wrap:wrap">
                                <span>{{ $document->original_filename ?: $document->name }}</span>
                                <span style="color:#6b7280">{{ strtoupper($document->indexing_status) }} ·
                                    {{ $document->created_at?->format('d/m/Y H:i') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (!empty($mandateExtractionPreview))
                <form method="POST"
                    action="{{ route('admin.municipalities.onboarding.mandato-commitments', $municipality) }}">
                    @csrf
                    <div style="font-size:.82rem;font-weight:600;margin-bottom:.45rem">Revisão da lista extraída pela IA
                    </div>
                    <div style="font-size:.82rem;color:#6b7280;margin-bottom:.8rem">
                        Edite a descrição, ajuste o eixo temático, revise palavras-chave e escolha quais compromissos
                        devem entrar na base inicial do módulo.
                    </div>
                    <div
                        style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;margin-bottom:.8rem;flex-wrap:wrap">
                        <div style="font-size:.78rem;color:#6b7280">
                            Inclua tambem compromissos que a IA não tenha identificado no documento.
                        </div>
                        <button type="button" id="addMandateCommitmentRow"
                            style="padding:.58rem .95rem;background:#111827;color:#fff;border:none;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer">
                            Adicionar compromisso manual
                        </button>
                    </div>
                    <div id="mandateCommitmentsList" style="display:flex;flex-direction:column;gap:.75rem">
                        @foreach ($mandateExtractionPreview as $index => $item)
                            <div class="mandate-commitment-card"
                                style="border:1px solid #e5e7eb;border-radius:10px;padding:1rem;background:#fafafa">
                                <div
                                    style="display:flex;justify-content:space-between;align-items:flex-start;gap:.75rem;margin-bottom:.75rem">
                                    <div style="display:flex;align-items:flex-start;gap:.65rem;flex:1">
                                        <input type="checkbox" name="commitments[{{ $index }}][enabled]"
                                            value="1" checked style="margin-top:.25rem">
                                        <div style="flex:1">
                                            <label
                                                style="display:block;font-size:.75rem;font-weight:700;color:#374151;margin-bottom:.35rem">Compromisso</label>
                                            <textarea name="commitments[{{ $index }}][text]" rows="2"
                                                style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $item['text'] ?? '' }}</textarea>
                                        </div>
                                    </div>
                                    <button type="button" class="remove-mandate-commitment"
                                        style="padding:.45rem .75rem;background:#fff;border:1px solid #e5e7eb;border-radius:8px;font-size:.78rem;color:#6b7280;cursor:pointer">
                                        Remover
                                    </button>
                                </div>
                                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem">
                                    <div>
                                        <label
                                            style="display:block;font-size:.75rem;font-weight:700;color:#374151;margin-bottom:.35rem">Eixo
                                            sugerido</label>
                                        <select name="commitments[{{ $index }}][axis_id]"
                                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;background:#fff">
                                            @foreach ($mandateAxes as $axis)
                                                <option value="{{ $axis->id }}" @selected(($item['axis_id'] ?? null) == $axis->id)>
                                                    {{ trim(($axis->icon ?? '') . ' ' . $axis->name) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label
                                            style="display:block;font-size:.75rem;font-weight:700;color:#374151;margin-bottom:.35rem">Palavras-chave</label>
                                        <input type="text" name="commitments[{{ $index }}][keywords]"
                                            value="{{ $item['keywords_text'] ?? '' }}"
                                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                                    </div>
                                    <div>
                                        <label
                                            style="display:block;font-size:.75rem;font-weight:700;color:#374151;margin-bottom:.35rem">Especificidade</label>
                                        <select name="commitments[{{ $index }}][specificity]"
                                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;background:#fff">
                                            <option value="quantitativo" @selected(($item['specificity'] ?? 'qualitativo') === 'quantitativo')>Quantitativo</option>
                                            <option value="qualitativo" @selected(($item['specificity'] ?? 'qualitativo') === 'qualitativo')>Qualitativo</option>
                                        </select>
                                    </div>
                                </div>
                                <input type="hidden" name="commitments[{{ $index }}][source_document_id]"
                                    value="{{ $item['source_document_id'] ?? '' }}">
                            </div>
                        @endforeach
                    </div>
                    <div style="margin-top:1rem;display:flex;justify-content:flex-end">
                        <button type="submit"
                            style="padding:.68rem 1.1rem;background:var(--gold);color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">Salvar
                            base inicial do Mandato</button>
                    </div>
                </form>

                <template id="mandateCommitmentRowTemplate">
                    <div class="mandate-commitment-card"
                        style="border:1px solid #e5e7eb;border-radius:10px;padding:1rem;background:#fafafa">
                        <div
                            style="display:flex;justify-content:space-between;align-items:flex-start;gap:.75rem;margin-bottom:.75rem">
                            <div style="display:flex;align-items:flex-start;gap:.65rem;flex:1">
                                <input type="checkbox" data-name="enabled" value="1" checked
                                    style="margin-top:.25rem">
                                <div style="flex:1">
                                    <label
                                        style="display:block;font-size:.75rem;font-weight:700;color:#374151;margin-bottom:.35rem">Compromisso</label>
                                    <textarea data-name="text" rows="2"
                                        style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical"
                                        placeholder="Descreva o compromisso identificado pela equipe"></textarea>
                                </div>
                            </div>
                            <button type="button" class="remove-mandate-commitment"
                                style="padding:.45rem .75rem;background:#fff;border:1px solid #e5e7eb;border-radius:8px;font-size:.78rem;color:#6b7280;cursor:pointer">
                                Remover
                            </button>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem">
                            <div>
                                <label
                                    style="display:block;font-size:.75rem;font-weight:700;color:#374151;margin-bottom:.35rem">Eixo
                                    sugerido</label>
                                <select data-name="axis_id"
                                    style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;background:#fff">
                                    @foreach ($mandateAxes as $axis)
                                        <option value="{{ $axis->id }}">
                                            {{ trim(($axis->icon ?? '') . ' ' . $axis->name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:.75rem;font-weight:700;color:#374151;margin-bottom:.35rem">Palavras-chave</label>
                                <input type="text" data-name="keywords"
                                    style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box"
                                    placeholder="Ex: UBS, fila, saude">
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:.75rem;font-weight:700;color:#374151;margin-bottom:.35rem">Especificidade</label>
                                <select data-name="specificity"
                                    style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;background:#fff">
                                    <option value="quantitativo">Quantitativo</option>
                                    <option value="qualitativo" selected>Qualitativo</option>
                                </select>
                            </div>
                        </div>
                        <input type="hidden" data-name="source_document_id"
                            value="{{ data_get($municipality->settings, 'mandato.extraction_preview.document_id') }}">
                    </div>
                </template>

                <script>
                    (function() {
                        const addButton = document.getElementById('addMandateCommitmentRow');
                        const list = document.getElementById('mandateCommitmentsList');
                        const template = document.getElementById('mandateCommitmentRowTemplate');
                        let rowIndex = {{ count($mandateExtractionPreview) }};

                        if (!addButton || !list || !template) {
                            return;
                        }

                        function wireRemoval(scope) {
                            scope.querySelectorAll('.remove-mandate-commitment').forEach(function(button) {
                                button.addEventListener('click', function() {
                                    const card = button.closest('.mandate-commitment-card');
                                    if (card && list.children.length > 1) {
                                        card.remove();
                                    }
                                });
                            });
                        }

                        function applyNames(card, index) {
                            card.querySelectorAll('[data-name]').forEach(function(field) {
                                field.name = 'commitments[' + index + '][' + field.dataset.name + ']';
                            });
                        }

                        addButton.addEventListener('click', function() {
                            const card = template.content.firstElementChild.cloneNode(true);
                            applyNames(card, rowIndex++);
                            wireRemoval(card);
                            list.appendChild(card);
                        });

                        wireRemoval(list);
                    })();
                </script>
            @endif
        </div>

        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
            <h3 style="font-size:1rem;font-weight:600;margin-bottom:1rem">Banco de Projetos</h3>
            <p style="font-size:.85rem;color:#6b7280;margin-bottom:1rem">
                A biblioteca do Banco é inicializada no fechamento do onboarding e depois entra em curadoria periódica para
                acompanhar compromissos revisados, documentos enviados e novas janelas de recurso do município.
            </p>

            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:1rem">
                <div style="padding:.85rem 1rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                    <div
                        style="font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.08em">
                        Teses na biblioteca</div>
                    <div style="font-size:1.25rem;font-weight:700;color:#111827;margin-top:.35rem">
                        {{ $projectBankSummary['theses_total'] ?? 0 }}</div>
                </div>
                <div style="padding:.85rem 1rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                    <div
                        style="font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.08em">
                        Bootstrap inicial</div>
                    <div style="font-size:.95rem;font-weight:700;color:#111827;margin-top:.35rem">
                        {{ !empty($projectBankSummary['bootstrapped_at']) ? \Illuminate\Support\Carbon::parse($projectBankSummary['bootstrapped_at'])->format('d/m/Y H:i') : 'Pendente' }}
                    </div>
                </div>
                <div style="padding:.85rem 1rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                    <div
                        style="font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.08em">
                        Última curadoria</div>
                    <div style="font-size:.95rem;font-weight:700;color:#111827;margin-top:.35rem">
                        {{ !empty($projectBankSummary['last_curated_at']) ? \Illuminate\Support\Carbon::parse($projectBankSummary['last_curated_at'])->format('d/m/Y H:i') : 'Ainda não executada' }}
                    </div>
                </div>
                <div style="padding:.85rem 1rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                    <div
                        style="font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.08em">
                        Status da curadoria</div>
                    <div
                        style="font-size:.95rem;font-weight:700;color:{{ !empty($projectBankSummary['needs_refresh']) ? '#b45309' : '#065f46' }};margin-top:.35rem">
                        {{ !empty($projectBankSummary['needs_refresh']) ? 'Refresh recomendado' : 'Em dia' }}
                    </div>
                </div>
            </div>

            <div
                style="padding:.9rem 1rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa;color:#4b5563;font-size:.84rem">
                @if (!empty($projectBankSummary['needs_refresh']))
                    O Banco de Projetos foi sinalizado para nova curadoria.
                    @if (!empty($projectBankSummary['refresh_reason']))
                        Motivo: {{ $projectBankSummary['refresh_reason'] }}.
                    @endif
                @else
                    Ao concluir o onboarding, o município já sai com a biblioteca inicial pronta e depois entra no ciclo
                    diário de atualização automática do Banco.
                @endif
            </div>
        </div>

        {{-- Resolve ai --}}
        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
            <h3 style="font-size:1rem;font-weight:600;margin-bottom:1rem">③ Configurações operacionais do Resolve ai</h3>
            <p style="font-size:.85rem;color:#6b7280;margin-bottom:1rem">
                Defina prazos por prioridade, canais ativos e quando o comprovante de conclusão será obrigatório.
            </p>
            <form method="POST"
                action="{{ route('admin.municipalities.onboarding.resolve-ai-settings', $municipality) }}">
                @csrf
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:.75rem;margin-bottom:1rem">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Prazo prioridade
                            alta (horas)</label>
                        <input type="number" min="1" max="720" name="resolve_ai_priority_alta_hours"
                            value="{{ $resolveAiSettings['priority_hours']['alta'] ?? 48 }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Prazo prioridade
                            média (horas)</label>
                        <input type="number" min="1" max="1440" name="resolve_ai_priority_media_hours"
                            value="{{ $resolveAiSettings['priority_hours']['media'] ?? 168 }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Prazo prioridade
                            baixa (horas)</label>
                        <input type="number" min="1" max="2160" name="resolve_ai_priority_baixa_hours"
                            value="{{ $resolveAiSettings['priority_hours']['baixa'] ?? 360 }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Antecedência do
                            alerta de prazo (horas)</label>
                        <input type="number" min="1" max="168" name="resolve_ai_alert_lead_hours"
                            value="{{ $resolveAiSettings['alert_lead_hours'] ?? 24 }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Cobrança por
                            inatividade (horas)</label>
                        <input type="number" min="1" max="720" name="resolve_ai_inactivity_followup_hours"
                            value="{{ $resolveAiSettings['inactivity_followup_hours'] ?? 48 }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Repetição da
                            cobrança em atraso (horas)</label>
                        <input type="number" min="1" max="168" name="resolve_ai_overdue_repeat_hours"
                            value="{{ $resolveAiSettings['overdue_repeat_hours'] ?? 24 }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Janela recente
                            comparativa (dias)</label>
                        <input type="number" min="7" max="365"
                            name="resolve_ai_comparative_recent_window_days"
                            value="{{ $resolveAiSettings['comparative_recent_window_days'] ?? 90 }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Janela anterior
                            comparativa (dias)</label>
                        <input type="number" min="7" max="365"
                            name="resolve_ai_comparative_previous_window_days"
                            value="{{ $resolveAiSettings['comparative_previous_window_days'] ?? 90 }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
                    <div style="padding:1rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                        <div style="font-size:.82rem;font-weight:600;margin-bottom:.5rem">Canais ativos</div>
                        <label
                            style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:#374151;margin-bottom:.45rem">
                            <input type="checkbox" name="resolve_ai_channel_internal" value="1"
                                @checked($resolveAiSettings['channels']['internal'] ?? true)>
                            Log interno do módulo
                        </label>
                        <label
                            style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:#374151;margin-bottom:.45rem">
                            <input type="checkbox" name="resolve_ai_channel_email" value="1"
                                @checked($resolveAiSettings['channels']['email'] ?? true)>
                            E-mail para responsável/criador
                        </label>
                        <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:#9ca3af">
                            <input type="checkbox" name="resolve_ai_channel_whatsapp" value="1"
                                @checked($resolveAiSettings['channels']['whatsapp'] ?? false)>
                            WhatsApp (preparação operacional)
                        </label>
                    </div>
                    <div style="padding:1rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                        <div style="font-size:.82rem;font-weight:600;margin-bottom:.5rem">Exigir comprovante</div>
                        @foreach (['alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa'] as $value => $label)
                            <label
                                style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:#374151;margin-bottom:.45rem">
                                <input type="checkbox" name="resolve_ai_attachment_required_priorities[]"
                                    value="{{ $value }}" @checked(in_array($value, $resolveAiSettings['attachment_required_priorities'] ?? ['alta'], true))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div
                    style="padding:.85rem 1rem;border-radius:10px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e3a8a;font-size:.82rem;line-height:1.55;margin-bottom:1rem">
                    Para o e-mail funcionar no Resolve ai, o SMTP runtime precisa estar ativo em `Configurações` e as áreas
                    de contato precisam ter e-mail cadastrado. A régua automática usa a antecedência do prazo, a janela
                    de inatividade, a repetição de atraso e as janelas comparativas para disparar cobrança e leitura de
                    governança territorial.
                </div>

                <button type="submit"
                    style="padding:.6rem 1.2rem;background:#111827;color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">Salvar
                    Resolve ai</button>
            </form>
        </div>

        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
            <h3 style="font-size:1rem;font-weight:600;margin-bottom:1rem">Comunicação - SLA editorial por etapa</h3>
            <p style="font-size:.85rem;color:#6b7280;margin-bottom:1rem">
                Ajuste o tempo esperado entre revisão, aprovação, agendamento e publicação para este município.
            </p>
            <form method="POST"
                action="{{ route('admin.municipalities.onboarding.communication-settings', $municipality) }}">
                @csrf
                <div style="margin-bottom:1rem">
                    <div style="font-size:.82rem;font-weight:600;margin-bottom:.45rem">Presets operacionais</div>
                    <div id="communication-sla-presets" style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:.6rem">
                        <button type="button" data-preset="36-36-12"
                            onclick="applyCommunicationSlaPreset(36, 36, 12, this)"
                            style="padding:.5rem .8rem;border:1px solid #d1d5db;border-radius:999px;background:#fff;font-size:.82rem;font-weight:600;color:#374151;cursor:pointer">Leve</button>
                        <button type="button" data-preset="24-24-6"
                            onclick="applyCommunicationSlaPreset(24, 24, 6, this)"
                            style="padding:.5rem .8rem;border:1px solid #d1d5db;border-radius:999px;background:#fff;color:#374151;font-size:.82rem;font-weight:600;cursor:pointer">Padrão</button>
                        <button type="button" data-preset="12-12-3"
                            onclick="applyCommunicationSlaPreset(12, 12, 3, this)"
                            style="padding:.5rem .8rem;border:1px solid #d1d5db;border-radius:999px;background:#fff;font-size:.82rem;font-weight:600;color:#374151;cursor:pointer">Rigoroso</button>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem">
                        <div style="padding:.75rem .85rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                            <div style="font-size:.76rem;font-weight:700;color:#111827">Leve</div>
                            <div style="font-size:.75rem;color:#6b7280;margin-top:.2rem">36h revisão, 36h publicação, 12h
                                antecedência.</div>
                        </div>
                        <div style="padding:.75rem .85rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                            <div style="font-size:.76rem;font-weight:700;color:#111827">Padrão</div>
                            <div style="font-size:.75rem;color:#6b7280;margin-top:.2rem">24h revisão, 24h publicação, 6h
                                antecedência.</div>
                        </div>
                        <div style="padding:.75rem .85rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                            <div style="font-size:.76rem;font-weight:700;color:#111827">Rigoroso</div>
                            <div style="font-size:.75rem;color:#6b7280;margin-top:.2rem">12h revisão, 12h publicação, 3h
                                antecedência.</div>
                        </div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:1rem">
                    <div>
                        <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:.3rem">Revisão inicial
                            (horas)</label>
                        <input id="communication_sla_draft_review_hours" type="number" min="1" max="720"
                            name="communication_sla_draft_review_hours"
                            value="{{ $communicationSettings['sla']['draft_review_hours'] ?? 24 }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:.3rem">Aprovado para
                            publicar (horas)</label>
                        <input id="communication_sla_approved_publish_hours" type="number" min="1"
                            max="720" name="communication_sla_approved_publish_hours"
                            value="{{ $communicationSettings['sla']['approved_publish_hours'] ?? 24 }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:.3rem">Antecedência do
                            agendado (horas)</label>
                        <input id="communication_sla_scheduled_lead_hours" type="number" min="1" max="168"
                            name="communication_sla_scheduled_lead_hours"
                            value="{{ $communicationSettings['sla']['scheduled_lead_hours'] ?? 6 }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                </div>

                <div
                    style="padding:.85rem 1rem;border-radius:10px;background:#f8fafc;border:1px solid #e5e7eb;color:#374151;font-size:.82rem;line-height:1.55;margin-bottom:1rem">
                    Esses tempos alimentam diretamente o painel de SLA do módulo `Comunicação`, a fila crítica de
                    vencimento, a leitura por etapa e o contexto exibido no workflow editorial da peça.
                </div>

                <div style="margin-bottom:1rem">
                    <div style="font-size:.82rem;font-weight:600;margin-bottom:.45rem">Perfil aprovador por tipo</div>
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem">
                        @php
                            $communicationApproval = $communicationSettings['approval'] ?? [];
                            $approvalOptions = [
                                'mayor' => 'Prefeito',
                                'secretary' => 'Secretário',
                                'advisor' => 'Assessor',
                            ];
                        @endphp
                        @foreach ([['name' => 'communication_approver_post', 'label' => 'Post', 'value' => $communicationApproval['post'] ?? 'mayor'], ['name' => 'communication_approver_image', 'label' => 'Imagem', 'value' => $communicationApproval['image'] ?? 'advisor'], ['name' => 'communication_approver_interview', 'label' => 'Entrevista', 'value' => $communicationApproval['interview'] ?? 'secretary'], ['name' => 'communication_approver_crisis', 'label' => 'Crise', 'value' => $communicationApproval['crisis'] ?? 'mayor']] as $approvalField)
                            <div>
                                <label
                                    style="display:block;font-size:.75rem;font-weight:600;margin-bottom:.3rem">{{ $approvalField['label'] }}</label>
                                <select name="{{ $approvalField['name'] }}"
                                    style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;background:#fff">
                                    @foreach ($approvalOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($approvalField['value'] === $value)>
                                            {{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>

                <button type="submit"
                    style="padding:.6rem 1.2rem;background:#111827;color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">Salvar
                    Comunicação</button>
            </form>
        </div>

        <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1rem">
                <div>
                    <h3 style="font-size:1rem;font-weight:600;margin-bottom:.35rem">Base operacional do Resolve ai</h3>
                    <p style="font-size:.85rem;color:#6b7280">Cadastre secretarias com canais de contato e a base mínima de
                        localidades do município.</p>
                </div>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                    <a href="{{ route('admin.municipalities.contact-areas.index', $municipality) }}"
                        style="padding:.55rem .9rem;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem;text-decoration:none;color:#374151">Gerir
                        secretarias</a>
                    <a href="{{ route('admin.municipalities.localities.index', $municipality) }}"
                        style="padding:.55rem .9rem;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem;text-decoration:none;color:#374151">Gerir
                        localidades</a>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">
                <div style="padding:1rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Secretarias</div>
                    <div style="font-size:1.35rem;font-weight:700;margin-top:.2rem">
                        {{ $resolveAiOperationalSummary['areas_total'] ?? 0 }}</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">
                        {{ $resolveAiOperationalSummary['areas_ready'] ?? 0 }} com e-mail operacional</div>
                </div>
                <div style="padding:1rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb">
                    <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;font-weight:700">Localidades</div>
                    <div style="font-size:1.35rem;font-weight:700;margin-top:.2rem">
                        {{ $resolveAiOperationalSummary['localities_total'] ?? 0 }}</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">
                        {{ $resolveAiOperationalSummary['localities_active'] ?? 0 }} ativas no formulário</div>
                </div>
                <div
                    style="padding:1rem;border-radius:10px;background:{{ $resolveAiOpsOk ? '#ecfdf5' : '#fff7ed' }};border:1px solid {{ $resolveAiOpsOk ? '#a7f3d0' : '#fed7aa' }}">
                    <div
                        style="font-size:.72rem;color:{{ $resolveAiOpsOk ? '#047857' : '#c2410c' }};text-transform:uppercase;font-weight:700">
                        Prontidão</div>
                    <div
                        style="font-size:1.35rem;font-weight:700;margin-top:.2rem;color:{{ $resolveAiOpsOk ? '#047857' : '#c2410c' }}">
                        {{ $resolveAiOpsOk ? 'OK' : 'Pendente' }}</div>
                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">Exige ao menos 1 secretaria pronta e 1
                        localidade ativa</div>
                </div>
            </div>

            <div
                style="padding:.85rem 1rem;border-radius:10px;background:#f9fafb;border:1px solid #e5e7eb;color:#374151;font-size:.82rem;line-height:1.55">
                O formulário do `Resolve ai` passa a sugerir automaticamente as localidades cadastradas e usa o e-mail de
                notificação da secretaria, quando informado.
            </div>
        </div>

        {{-- Ativar --}}
        @if (
            $municipalityCoreOk &&
                $voiceOk &&
                $mapOk &&
                $communicationContextOk &&
                $notificationsOk &&
                $municipality->onboarding_status !== 'completed')
            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:2px solid #d4af37">
                <h3 style="font-size:1rem;font-weight:600;margin-bottom:.5rem">④ Ativar acesso do prefeito</h3>
                <p style="font-size:.85rem;color:#6b7280;margin-bottom:1rem">Tudo configurado! Ao ativar, o prefeito já
                    poderá acessar o assistente.</p>
                <form method="POST" action="{{ route('admin.municipalities.onboarding.complete', $municipality) }}">
                    @csrf
                    <button type="submit"
                        style="padding:.7rem 1.5rem;background:#16a34a;color:#fff;border:none;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer">✓
                        Ativar Município</button>
                </form>
            </div>
        @elseif($municipality->onboarding_status === 'completed')
            <div style="background:#d1fae5;padding:1.5rem;border-radius:12px;border:1px solid #6ee7b7;text-align:center">
                <div style="font-size:1.5rem;margin-bottom:.5rem">✅</div>
                <div style="font-weight:600;color:#065f46">Município ativo — prefeito com acesso liberado</div>
            </div>
        @elseif($municipality->onboarding_status !== 'completed')
            <div
                style="background:#fff;padding:1.25rem 1.5rem;border-radius:12px;border:1px dashed #d1d5db;color:#6b7280;font-size:.84rem;line-height:1.6">
                Para liberar o município, conclua pelo menos os blocos de base institucional, perfil estratégico, mapa
                político, contexto de comunicação e notificações.
            </div>
        @endif
    </div>

    <script>
        function setCommunicationSlaPresetActive(activeKey) {
            var buttons = document.querySelectorAll('#communication-sla-presets [data-preset]');
            buttons.forEach(function(button) {
                var isActive = button.dataset.preset === activeKey;
                button.style.background = isActive ? '#111827' : '#fff';
                button.style.color = isActive ? '#fff' : '#374151';
                button.style.borderColor = isActive ? '#111827' : '#d1d5db';
            });
        }

        function syncCommunicationSlaPresetState() {
            var draftInput = document.getElementById('communication_sla_draft_review_hours');
            var publishInput = document.getElementById('communication_sla_approved_publish_hours');
            var scheduledInput = document.getElementById('communication_sla_scheduled_lead_hours');

            if (!draftInput || !publishInput || !scheduledInput) return;

            var activeKey = [draftInput.value, publishInput.value, scheduledInput.value].join('-');
            setCommunicationSlaPresetActive(activeKey);
        }

        function applyCommunicationSlaPreset(draftHours, publishHours, scheduledLeadHours, button) {
            var draftInput = document.getElementById('communication_sla_draft_review_hours');
            var publishInput = document.getElementById('communication_sla_approved_publish_hours');
            var scheduledInput = document.getElementById('communication_sla_scheduled_lead_hours');

            if (draftInput) draftInput.value = draftHours;
            if (publishInput) publishInput.value = publishHours;
            if (scheduledInput) scheduledInput.value = scheduledLeadHours;

            var activeKey = [draftHours, publishHours, scheduledLeadHours].join('-');
            setCommunicationSlaPresetActive(activeKey);
        }

        syncCommunicationSlaPresetState();

        ['communication_sla_draft_review_hours', 'communication_sla_approved_publish_hours',
            'communication_sla_scheduled_lead_hours'
        ].forEach(function(id) {
            var input = document.getElementById(id);
            if (!input) return;
            input.addEventListener('input', syncCommunicationSlaPresetState);
            input.addEventListener('change', syncCommunicationSlaPresetState);
        });
    </script>
@endsection
