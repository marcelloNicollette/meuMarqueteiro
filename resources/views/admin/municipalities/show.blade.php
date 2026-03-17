@extends('layouts.admin')
@section('title', $municipality->name)
@section('content')
<div style="padding:2rem;max-width:900px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem">
        <div>
            <a href="{{ route('admin.municipalities.index') }}" style="font-size:.85rem;color:#6b7280;text-decoration:none">← Municípios</a>
            <h1 style="font-size:1.4rem;font-weight:700;margin-top:.5rem">{{ $municipality->name }}</h1>
            <p style="color:#6b7280;font-size:.88rem">{{ $municipality->state }} · IBGE {{ $municipality->ibge_code }}</p>
        </div>
        <div style="display:flex;gap:.75rem">
            <a href="{{ route('admin.municipalities.edit', $municipality) }}" style="padding:.6rem 1rem;border:1px solid #d1d5db;border-radius:8px;font-size:.85rem;text-decoration:none;color:#374151">Editar</a>
            @if($municipality->onboarding_status !== 'completed')
            <a href="{{ route('admin.municipalities.onboarding.show', $municipality) }}" style="padding:.6rem 1rem;background:var(--gold);color:#fff;border-radius:8px;font-size:.85rem;text-decoration:none;font-weight:600">Onboarding</a>
            @endif
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;margin-bottom:1.5rem">
        @foreach(['Compromissos' => $stats['commitments_total'], 'Entregues' => $stats['commitments_done'], 'Em risco' => $stats['commitments_at_risk'], 'Conversas' => $stats['conversations_total'], 'Conteúdos' => $stats['contents_generated']] as $label => $val)
        <div style="background:#fff;padding:1rem;border-radius:10px;border:1px solid #e5e7eb;text-align:center">
            <div style="font-size:1.5rem;font-weight:700">{{ $val }}</div>
            <div style="font-size:.75rem;color:#6b7280;margin-top:.2rem">{{ $label }}</div>
        </div>
        @endforeach
    </div>

    <div style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb">
        <h3 style="font-size:.95rem;font-weight:600;margin-bottom:1rem">Informações</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;font-size:.88rem">
            <div><span style="color:#9ca3af">Prefeito:</span> {{ $municipality->mayor?->name ?? '—' }}</div>
            <div><span style="color:#9ca3af">E-mail:</span> {{ $municipality->mayor?->email ?? '—' }}</div>
            <div><span style="color:#9ca3af">Plano:</span> {{ $municipality->getTierLabel() }}</div>
            <div><span style="color:#9ca3af">Status:</span> {{ $municipality->onboarding_status }}</div>
            <div><span style="color:#9ca3af">População:</span> {{ $municipality->population ? number_format($municipality->population, 0, ',', '.') : '—' }}</div>
            <div><span style="color:#9ca3af">IDHM:</span> {{ $municipality->idhm ?? '—' }}</div>
        </div>
    </div>
</div>
@endsection
