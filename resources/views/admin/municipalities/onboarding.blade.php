@extends('layouts.admin')
@section('title', 'Onboarding — '.$municipality->name)
@section('content')
<div style="padding:2rem;max-width:750px">
    <div style="margin-bottom:1.5rem">
        <a href="{{ route('admin.municipalities.index') }}" style="font-size:.85rem;color:#6b7280;text-decoration:none">← Municípios</a>
        <h1 style="font-size:1.4rem;font-weight:700;margin-top:.5rem">Onboarding — {{ $municipality->name }}</h1>
        <p style="color:#6b7280;font-size:.88rem;margin-top:.3rem">Configure o perfil do município para ativar o assistente do prefeito.</p>
    </div>

    @if(session('success'))
        <div style="background:#d1fae5;border:1px solid #6ee7b7;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#065f46">{{ session('success') }}</div>
    @endif

    {{-- Progresso --}}
    @php
        $steps = ['voice_profile' => 'Perfil de Voz', 'political_map' => 'Mapa Político', 'complete' => 'Ativar'];
        $voiceOk = !empty($municipality->voice_profile);
        $mapOk = !empty($municipality->political_map);
    @endphp
    <div style="display:flex;gap:1rem;margin-bottom:2rem">
        <div style="flex:1;padding:.75rem 1rem;border-radius:8px;background:{{ $voiceOk ? '#d1fae5' : '#f3f4f6' }};border:1px solid {{ $voiceOk ? '#6ee7b7' : '#e5e7eb' }}">
            <div style="font-size:.75rem;font-weight:600;color:{{ $voiceOk ? '#065f46' : '#9ca3af' }}">① PERFIL DE VOZ</div>
            <div style="font-size:.82rem;margin-top:.2rem;color:{{ $voiceOk ? '#065f46' : '#6b7280' }}">{{ $voiceOk ? '✓ Concluído' : 'Pendente' }}</div>
        </div>
        <div style="flex:1;padding:.75rem 1rem;border-radius:8px;background:{{ $mapOk ? '#d1fae5' : '#f3f4f6' }};border:1px solid {{ $mapOk ? '#6ee7b7' : '#e5e7eb' }}">
            <div style="font-size:.75rem;font-weight:600;color:{{ $mapOk ? '#065f46' : '#9ca3af' }}">② MAPA POLÍTICO</div>
            <div style="font-size:.82rem;margin-top:.2rem;color:{{ $mapOk ? '#065f46' : '#6b7280' }}">{{ $mapOk ? '✓ Concluído' : 'Pendente' }}</div>
        </div>
        <div style="flex:1;padding:.75rem 1rem;border-radius:8px;background:{{ $municipality->onboarding_status === 'completed' ? '#d1fae5' : '#f3f4f6' }};border:1px solid {{ $municipality->onboarding_status === 'completed' ? '#6ee7b7' : '#e5e7eb' }}">
            <div style="font-size:.75rem;font-weight:600;color:{{ $municipality->onboarding_status === 'completed' ? '#065f46' : '#9ca3af' }}">③ ATIVAÇÃO</div>
            <div style="font-size:.82rem;margin-top:.2rem;color:{{ $municipality->onboarding_status === 'completed' ? '#065f46' : '#6b7280' }}">{{ $municipality->onboarding_status === 'completed' ? '✓ Ativo' : 'Pendente' }}</div>
        </div>
    </div>

    {{-- Perfil de Voz --}}
    <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
        <h3 style="font-size:1rem;font-weight:600;margin-bottom:1rem">① Perfil de Voz do Prefeito</h3>
        <p style="font-size:.85rem;color:#6b7280;margin-bottom:1rem">Define como o assistente vai se comunicar em nome do prefeito — tom, estilo e vocabulário.</p>
        <form method="POST" action="{{ route('admin.municipalities.onboarding.voice-profile', $municipality) }}">
            @csrf
            <div style="display:grid;gap:.75rem;margin-bottom:1rem">
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Tom de voz</label>
                    <input name="tone" value="{{ $municipality->voice_profile['tone'] ?? '' }}" placeholder="ex: próximo e acessível" style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Estilo de comunicação</label>
                    <input name="style" value="{{ $municipality->voice_profile['style'] ?? '' }}" placeholder="ex: informal mas respeitoso" style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Vocabulário</label>
                    <input name="vocabulary" value="{{ $municipality->voice_profile['vocabulary'] ?? '' }}" placeholder="ex: simples, sem tecnicismos" style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Evitar</label>
                    <input name="avoid" value="{{ $municipality->voice_profile['avoid'] ?? '' }}" placeholder="ex: jargões políticos, termos técnicos" style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                </div>
            </div>
            <button type="submit" style="padding:.6rem 1.2rem;background:var(--gold);color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">Salvar Perfil de Voz</button>
        </form>
    </div>

    {{-- Mapa Político --}}
    <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
        <h3 style="font-size:1rem;font-weight:600;margin-bottom:1rem">② Mapa Político da Câmara</h3>
        <form method="POST" action="{{ route('admin.municipalities.onboarding.political-map', $municipality) }}">
            @csrf
            <div style="display:grid;gap:.75rem;margin-bottom:1rem">
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Vereadores aliados</label>
                    <textarea name="allies" rows="2" placeholder="Nomes e partidos dos aliados" style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipality->political_map['allies'] ?? '' }}</textarea>
                </div>
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Neutros / indecisos</label>
                    <textarea name="neutral" rows="2" style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipality->political_map['neutral'] ?? '' }}</textarea>
                </div>
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Oposição</label>
                    <textarea name="opposition" rows="2" style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipality->political_map['opposition'] ?? '' }}</textarea>
                </div>
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Observações políticas</label>
                    <textarea name="notes" rows="3" placeholder="Contexto político local, alianças, tensões..." style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ $municipality->political_map['notes'] ?? '' }}</textarea>
                </div>
            </div>
            <button type="submit" style="padding:.6rem 1.2rem;background:var(--gold);color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">Salvar Mapa Político</button>
        </form>
    </div>

    {{-- Ativar --}}
    @if($voiceOk && $mapOk && $municipality->onboarding_status !== 'completed')
    <div style="background:#fff;padding:1.5rem;border-radius:12px;border:2px solid #d4af37">
        <h3 style="font-size:1rem;font-weight:600;margin-bottom:.5rem">③ Ativar acesso do prefeito</h3>
        <p style="font-size:.85rem;color:#6b7280;margin-bottom:1rem">Tudo configurado! Ao ativar, o prefeito já poderá acessar o assistente.</p>
        <form method="POST" action="{{ route('admin.municipalities.onboarding.complete', $municipality) }}">
            @csrf
            <button type="submit" style="padding:.7rem 1.5rem;background:#16a34a;color:#fff;border:none;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer">✓ Ativar Município</button>
        </form>
    </div>
    @elseif($municipality->onboarding_status === 'completed')
    <div style="background:#d1fae5;padding:1.5rem;border-radius:12px;border:1px solid #6ee7b7;text-align:center">
        <div style="font-size:1.5rem;margin-bottom:.5rem">✅</div>
        <div style="font-weight:600;color:#065f46">Município ativo — prefeito com acesso liberado</div>
    </div>
    @endif
</div>
@endsection
