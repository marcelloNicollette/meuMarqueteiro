@extends('layouts.admin')
@section('title', 'Prefeitos')
@section('content')
    <div style="padding:2rem">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
            <h1 style="font-size:1.4rem;font-weight:700">Prefeitos</h1>
            <a href="{{ route('admin.users.create') }}"
                style="padding:.6rem 1.2rem;background:var(--gold);color:#fff;border-radius:8px;font-weight:600;text-decoration:none;font-size:.9rem">+
                Novo Prefeito</a>
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
                        <th style="padding:.9rem 1rem;text-align:left;font-size:.8rem;color:#6b7280;font-weight:600">PREFEITO
                        </th>
                        <th style="padding:.9rem 1rem;text-align:left;font-size:.8rem;color:#6b7280;font-weight:600">
                            MUNICÍPIO</th>
                        <th style="padding:.9rem 1rem;text-align:left;font-size:.8rem;color:#6b7280;font-weight:600">STATUS
                        </th>
                        <th style="padding:.9rem 1rem;text-align:left;font-size:.8rem;color:#6b7280;font-weight:600">ÚLTIMO
                            ACESSO</th>
                        <th style="padding:.9rem 1rem;text-align:left;font-size:.8rem;color:#6b7280;font-weight:600">AÇÕES
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr style="border-bottom:1px solid #f3f4f6">
                            <td style="padding:.9rem 1rem">
                                <div style="font-weight:600;font-size:.9rem">{{ $user->name }}</div>
                                <div style="font-size:.78rem;color:#9ca3af">{{ $user->email }}</div>
                            </td>
                            <td style="padding:.9rem 1rem;font-size:.88rem">{{ $user->municipality?->name ?? '—' }}</td>
                            <td style="padding:.9rem 1rem">
                                <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        title="{{ $user->is_active ? 'Clique para desativar' : 'Clique para ativar' }}"
                                        style="padding:.25rem .7rem;border-radius:99px;font-size:.75rem;font-weight:600;cursor:pointer;border:none;
                                    background:{{ $user->is_active ? '#d1fae5' : '#f3f4f6' }};
                                    color:{{ $user->is_active ? '#065f46' : '#9ca3af' }}">
                                        {{ $user->is_active ? '● Ativo' : '○ Inativo' }}
                                    </button>
                                </form>
                            </td>
                            <td style="padding:.9rem 1rem;font-size:.85rem;color:#6b7280">
                                @if ($user->last_login_at)
                                    <div style="font-size:.82rem;color:#374151;font-weight:500">
                                        {{ $user->last_login_at->format('d/m/Y H:i') }}</div>
                                    <div style="font-size:.72rem;color:#9ca3af">{{ $user->last_login_at->diffForHumans() }}
                                    </div>
                                    @if ($user->last_login_ip)
                                        <div style="font-size:.7rem;color:#d1d5db;font-family:monospace">
                                            {{ $user->last_login_ip }}</div>
                                    @endif
                                @else
                                    <span style="color:#d1d5db;font-size:.8rem">— nunca</span>
                                @endif
                            </td>
                            <td style="padding:.9rem 1rem">
                                <div style="display:flex;gap:.5rem;align-items:center">
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                        style="padding:.3rem .7rem;border:1px solid #e5e7eb;border-radius:6px;font-size:.78rem;color:#374151;text-decoration:none">Editar</a>
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                        onsubmit="return confirm('Excluir {{ addslashes($user->name) }}? Esta ação não pode ser desfeita.')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            style="padding:.3rem .7rem;border:1px solid #fca5a5;border-radius:6px;font-size:.78rem;color:#dc2626;background:#fff;cursor:pointer">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:2rem;text-align:center;color:#9ca3af">Nenhum prefeito
                                cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:1rem">{{ $users->links() }}</div>
    </div>
@endsection
