@extends('layouts.admin')
@section('title', 'Editar — ' . $municipality->name)
@section('content')
    <div style="padding:2rem;max-width:750px">
        <div style="margin-bottom:1.5rem">
            <a href="{{ route('admin.municipalities.show', $municipality) }}"
                style="font-size:.85rem;color:#6b7280;text-decoration:none">← {{ $municipality->name }}</a>
            <h1 style="font-size:1.4rem;font-weight:700;margin-top:.5rem">Editar Município</h1>
        </div>

        @if ($errors->any())
            <div
                style="background:#fee2e2;border:1px solid #fca5a5;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#991b1b;font-size:.88rem">
                @foreach ($errors->all() as $e)
                    <div>{{ $e }}</div>
                @endforeach
            </div>
        @endif
        @if (session('success'))
            <div
                style="background:#d1fae5;border:1px solid #6ee7b7;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#065f46">
                {{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.municipalities.update', $municipality) }}">
            @csrf @method('PUT')

            {{-- DADOS BÁSICOS --}}
            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
                <h3
                    style="font-size:.95rem;font-weight:600;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid #f3f4f6">
                    Dados Básicos</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Nome *</label>
                        <input name="name" value="{{ old('name', $municipality->name) }}" required
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Código
                            IBGE</label>
                        <input name="ibge_code" value="{{ old('ibge_code', $municipality->ibge_code) }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Estado *</label>
                        <input name="state" value="{{ old('state', $municipality->state) }}" required
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">UF *</label>
                        <input name="state_code" value="{{ old('state_code', $municipality->state_code) }}" maxlength="2"
                            required
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Região</label>
                        <select name="region"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                            <option value="">Selecione...</option>
                            @foreach (['Norte', 'Nordeste', 'Centro-Oeste', 'Sudeste', 'Sul'] as $r)
                                <option value="{{ $r }}"
                                    {{ old('region', $municipality->region) === $r ? 'selected' : '' }}>
                                    {{ $r }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Plano *</label>
                        <select name="subscription_tier" required
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                            <option value="essencial"
                                {{ old('subscription_tier', $municipality->subscription_tier) === 'essencial' ? 'selected' : '' }}>
                                Essencial</option>
                            <option value="estrategico"
                                {{ old('subscription_tier', $municipality->subscription_tier) === 'estrategico' ? 'selected' : '' }}>
                                Estratégico</option>
                            <option value="parceiro"
                                {{ old('subscription_tier', $municipality->subscription_tier) === 'parceiro' ? 'selected' : '' }}>
                                Parceiro</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:1rem">
                    <label style="display:flex;align-items:center;gap:.5rem;font-size:.88rem;cursor:pointer">
                        <input type="checkbox" name="subscription_active" value="1"
                            {{ old('subscription_active', $municipality->subscription_active) ? 'checked' : '' }}>
                        Assinatura ativa
                    </label>
                </div>
            </div>

            {{-- DADOS SOCIOECONÔMICOS --}}
            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
                <h3
                    style="font-size:.95rem;font-weight:600;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid #f3f4f6">
                    Dados Socioeconômicos</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">População</label>
                        <input name="population" type="number" value="{{ old('population', $municipality->population) }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">IDHM</label>
                        <input name="idhm" type="number" step="0.001" min="0" max="1"
                            value="{{ old('idhm', $municipality->idhm) }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">PIB (R$)</label>
                        <input name="gdp" type="number" step="0.01" value="{{ old('gdp', $municipality->gdp) }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Área (km²)</label>
                        <input name="area_km2" type="number" step="0.01"
                            value="{{ old('area_km2', $municipality->area_km2) }}"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                </div>
            </div>

            {{-- PERFIL DE VOZ --}}
            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem">
                <h3
                    style="font-size:.95rem;font-weight:600;margin-bottom:.4rem;padding-bottom:.75rem;border-bottom:1px solid #f3f4f6">
                    Perfil de Voz do Prefeito</h3>
                <p style="font-size:.82rem;color:#6b7280;margin-bottom:1rem">Define como o assistente se comunica em nome do
                    prefeito.</p>
                <div style="display:grid;gap:.75rem">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Tom de voz</label>
                        <input name="voice_tone"
                            value="{{ old('voice_tone', $municipality->voice_profile['tone'] ?? '') }}"
                            placeholder="ex: próximo e acessível"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Estilo</label>
                        <input name="voice_style"
                            value="{{ old('voice_style', $municipality->voice_profile['style'] ?? '') }}"
                            placeholder="ex: informal mas respeitoso"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label
                            style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Vocabulário</label>
                        <input name="voice_vocabulary"
                            value="{{ old('voice_vocabulary', $municipality->voice_profile['vocabulary'] ?? '') }}"
                            placeholder="ex: simples, sem tecnicismos"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Evitar</label>
                        <input name="voice_avoid"
                            value="{{ old('voice_avoid', $municipality->voice_profile['avoid'] ?? '') }}"
                            placeholder="ex: jargões políticos, termos técnicos"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                    </div>
                </div>
            </div>

            {{-- MAPA POLÍTICO --}}
            <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1.5rem">
                <h3
                    style="font-size:.95rem;font-weight:600;margin-bottom:.4rem;padding-bottom:.75rem;border-bottom:1px solid #f3f4f6">
                    Mapa Político da Câmara</h3>
                <div style="display:grid;gap:.75rem">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Aliados</label>
                        <textarea name="political_allies" rows="2" placeholder="Nomes e partidos dos aliados"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ old('political_allies', $municipality->political_map['allies'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Neutros /
                            indecisos</label>
                        <textarea name="political_neutral" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ old('political_neutral', $municipality->political_map['neutral'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Oposição</label>
                        <textarea name="political_opposition" rows="2"
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ old('political_opposition', $municipality->political_map['opposition'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Observações
                            políticas</label>
                        <textarea name="political_notes" rows="3" placeholder="Contexto político local, alianças, tensões..."
                            style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box;resize:vertical">{{ old('political_notes', $municipality->political_map['notes'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:1rem">
                <a href="{{ route('admin.municipalities.show', $municipality) }}"
                    style="padding:.65rem 1.2rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;color:#374151;text-decoration:none">Cancelar</a>
                <button type="submit"
                    style="padding:.65rem 1.5rem;background:var(--gold);color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">Salvar
                    Alterações</button>
            </div>
        </form>
    </div>
@endsection
