@extends('layouts.admin')
@section('title', 'Municípios')
@section('content')
    <div style="padding:2rem">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
            <h1 style="font-size:1.4rem;font-weight:700">Municípios</h1>
            <a href="{{ route('admin.municipalities.create') }}"
                style="padding:.6rem 1.2rem;background:var(--gold);color:#fff;border-radius:8px;font-weight:600;text-decoration:none;font-size:.9rem">+
                Novo Município</a>
        </div>

        @if (session('success'))
            <div
                style="background:#d1fae5;border:1px solid #6ee7b7;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#065f46">
                {{ session('success') }}</div>
        @endif

        <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden">
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                        <th style="padding:.9rem 1rem;text-align:left;font-size:.8rem;color:#6b7280;font-weight:600">
                            MUNICÍPIO</th>
                        <th style="padding:.9rem 1rem;text-align:left;font-size:.8rem;color:#6b7280;font-weight:600">
                            PREFEITO</th>
                        <th style="padding:.9rem 1rem;text-align:left;font-size:.8rem;color:#6b7280;font-weight:600">PLANO
                        </th>
                        <th style="padding:.9rem 1rem;text-align:left;font-size:.8rem;color:#6b7280;font-weight:600">STATUS
                        </th>
                        <th style="padding:.9rem 1rem;text-align:left;font-size:.8rem;color:#6b7280;font-weight:600">AÇÕES
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($municipalities as $m)
                        <tr style="border-bottom:1px solid #f3f4f6">
                            <td style="padding:.9rem 1rem">
                                <div style="font-weight:600;font-size:.9rem">{{ $m->name }}</div>
                                <div style="font-size:.78rem;color:#9ca3af">{{ $m->state_code }} · IBGE {{ $m->ibge_code }}
                                </div>
                            </td>
                            <td style="padding:.9rem 1rem;font-size:.88rem;color:#374151">{{ $m->mayor?->name ?? '—' }}</td>
                            <td style="padding:.9rem 1rem">
                                <span
                                    style="padding:.2rem .7rem;border-radius:99px;font-size:.75rem;font-weight:600;background:{{ $m->subscription_tier === 'parceiro' ? '#fef3c7' : ($m->subscription_tier === 'estrategico' ? '#ede9fe' : '#f3f4f6') }};color:{{ $m->subscription_tier === 'parceiro' ? '#92400e' : ($m->subscription_tier === 'estrategico' ? '#6d28d9' : '#374151') }}">
                                    {{ $m->getTierLabel() }}
                                </span>
                            </td>
                            <td style="padding:.9rem 1rem">
                                <div style="display:flex;flex-direction:column;gap:.4rem">
                                    {{-- Ativo / Inativo --}}
                                    <form method="POST" action="{{ route('admin.municipalities.toggle', $m) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                            title="{{ $m->subscription_active ? 'Clique para desativar' : 'Clique para ativar' }}"
                                            style="padding:.25rem .7rem;border-radius:99px;font-size:.75rem;font-weight:600;cursor:pointer;border:none;
                                        background:{{ $m->subscription_active ? '#d1fae5' : '#f3f4f6' }};
                                        color:{{ $m->subscription_active ? '#065f46' : '#9ca3af' }}">
                                            {{ $m->subscription_active ? '● Ativo' : '○ Inativo' }}
                                        </button>
                                    </form>
                                    {{-- Onboarding --}}
                                    <span
                                        style="padding:.2rem .7rem;border-radius:99px;font-size:.72rem;font-weight:600;
                                background:{{ $m->onboarding_status === 'completed' ? '#dbeafe' : ($m->onboarding_status === 'in_progress' ? '#fef3c7' : '#f3f4f6') }};
                                color:{{ $m->onboarding_status === 'completed' ? '#1e40af' : ($m->onboarding_status === 'in_progress' ? '#92400e' : '#6b7280') }}">
                                        {{ $m->onboarding_status === 'completed' ? 'Onboarding OK' : ($m->onboarding_status === 'in_progress' ? 'Em onboarding' : 'Pendente') }}
                                    </span>
                                </div>
                            </td>
                            <td style="padding:.9rem 1rem">
                                <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                                    <a href="{{ route('admin.municipalities.show', $m) }}"
                                        style="padding:.3rem .7rem;border:1px solid #e5e7eb;border-radius:6px;font-size:.78rem;color:#374151;text-decoration:none">Ver</a>
                                    <a href="{{ route('admin.municipalities.edit', $m) }}"
                                        style="padding:.3rem .7rem;border:1px solid #e5e7eb;border-radius:6px;font-size:.78rem;color:#374151;text-decoration:none">Editar</a>
                                    @if ($m->onboarding_status !== 'completed')
                                        <a href="{{ route('admin.municipalities.onboarding.show', $m) }}"
                                            style="padding:.3rem .7rem;border:1px solid #d4af37;border-radius:6px;font-size:.78rem;color:#92400e;text-decoration:none">Onboarding</a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.municipalities.destroy', $m) }}"
                                        onsubmit="return confirm('Excluir {{ addslashes($m->name) }}? Esta ação não pode ser desfeita.')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            style="padding:.3rem .7rem;border:1px solid #fca5a5;border-radius:6px;font-size:.78rem;color:#dc2626;background:#fff;cursor:pointer">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:2rem;text-align:center;color:#9ca3af;font-size:.9rem">Nenhum
                                município cadastrado ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:1rem">{{ $municipalities->links() }}</div>
    </div>
@endsection
