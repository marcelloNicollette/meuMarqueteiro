@extends('layouts.admin')
@section('title', 'APIs Externas')
@section('content')
<div style="padding:2rem;max-width:900px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
        <div>
            <h1 style="font-size:1.4rem;font-weight:700">APIs Externas e Fontes de Dados</h1>
            <p style="font-size:.85rem;color:#6b7280;margin-top:.3rem">Fontes públicas de dados municipais integradas ao assistente</p>
        </div>
        <a href="{{ route('admin.settings.index') }}" style="font-size:.85rem;color:#6b7280;text-decoration:none">← Configurações</a>
    </div>

    @if(session('success'))
        <div style="background:#d1fae5;border:1px solid #6ee7b7;padding:1rem;border-radius:8px;margin-bottom:1.5rem;color:#065f46">{{ session('success') }}</div>
    @endif

    {{-- STATUS GERAL --}}
    @php
        $total = count($integrations);
        $ativos = collect($integrations)->where('ativo', true)->count();
    @endphp
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem">
        <div style="background:#fff;padding:1rem;border-radius:10px;border:1px solid #e5e7eb;text-align:center">
            <div style="font-size:1.8rem;font-weight:700;color:#0f1117">{{ $total }}</div>
            <div style="font-size:.78rem;color:#6b7280;margin-top:.2rem">APIs disponíveis</div>
        </div>
        <div style="background:#fff;padding:1rem;border-radius:10px;border:1px solid #e5e7eb;text-align:center">
            <div style="font-size:1.8rem;font-weight:700;color:#16a34a">{{ $ativos }}</div>
            <div style="font-size:.78rem;color:#6b7280;margin-top:.2rem">Ativas</div>
        </div>
        <div style="background:#fff;padding:1rem;border-radius:10px;border:1px solid #e5e7eb;text-align:center">
            <div style="font-size:1.8rem;font-weight:700;color:#d97706">{{ $total - $ativos }}</div>
            <div style="font-size:.78rem;color:#6b7280;margin-top:.2rem">Não configuradas</div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.settings.integrations.save') }}">
        @csrf

        @foreach($grupos as $grupo => $apis)
        <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem;overflow:hidden">
            <div style="padding:1rem 1.5rem;background:#f9fafb;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;gap:.75rem">
                <span style="font-size:1.1rem">{{ $grupo === 'fiscal' ? '💰' : ($grupo === 'saude' ? '🏥' : ($grupo === 'educacao' ? '📚' : ($grupo === 'infraestrutura' ? '🏗️' : ($grupo === 'captacao' ? '🎯' : '📊')))) }}</span>
                <div>
                    <div style="font-weight:600;font-size:.95rem">{{ $grupoLabels[$grupo] }}</div>
                    <div style="font-size:.78rem;color:#6b7280">{{ count($apis) }} fontes disponíveis</div>
                </div>
            </div>

            <div style="padding:.5rem 0">
                @foreach($apis as $key => $api)
                <div style="padding:.9rem 1.5rem;border-bottom:1px solid #f9fafb;display:flex;align-items:flex-start;gap:1rem">
                    <div style="padding-top:.1rem">
                        <label style="cursor:pointer;display:flex;align-items:center">
                            <input type="checkbox" name="ativos[]" value="{{ $key }}"
                                {{ $api['ativo'] ? 'checked' : '' }}
                                style="width:16px;height:16px;accent-color:var(--gold)">
                        </label>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.2rem">
                            <span style="font-weight:600;font-size:.9rem;color:#0f1117">{{ $api['nome'] }}</span>
                            @if($api['gratuita'])
                                <span style="padding:.1rem .5rem;background:#d1fae5;color:#065f46;border-radius:99px;font-size:.7rem;font-weight:600">GRATUITA</span>
                            @else
                                <span style="padding:.1rem .5rem;background:#fef3c7;color:#92400e;border-radius:99px;font-size:.7rem;font-weight:600">REQUER CADASTRO</span>
                            @endif
                            @if($api['ativo'])
                                <span style="padding:.1rem .5rem;background:#d1fae5;color:#065f46;border-radius:99px;font-size:.7rem;font-weight:600">✓ ATIVA</span>
                            @endif
                        </div>
                        <div style="font-size:.82rem;color:#6b7280;margin-bottom:.4rem">{{ $api['descricao'] }}</div>
                        <div style="font-size:.75rem;color:#9ca3af">
                            <a href="{{ $api['url'] }}" target="_blank" style="color:var(--gold);text-decoration:none">{{ $api['url'] }}</a>
                        </div>
                        @if(!empty($api['requer_chave']))
                        <div style="margin-top:.6rem">
                            <input type="text" name="chaves[{{ $key }}]"
                                value="{{ $api['chave'] ?? '' }}"
                                placeholder="Token/chave de acesso (se necessário)"
                                style="width:100%;max-width:400px;padding:.4rem .7rem;border:1px solid #d1d5db;border-radius:6px;font-size:.8rem;box-sizing:border-box">
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        <div style="display:flex;justify-content:flex-end;margin-top:.5rem">
            <button type="submit" style="padding:.7rem 2rem;background:var(--gold);color:#fff;border:none;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer">Salvar Configurações</button>
        </div>
    </form>
</div>
@endsection
