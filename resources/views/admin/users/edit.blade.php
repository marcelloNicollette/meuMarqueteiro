@extends('layouts.admin')
@section('title', 'Editar — '.$user->name)
@section('content')
<div style="padding:2rem;max-width:600px">
    <div style="margin-bottom:1.5rem">
        <a href="{{ route('admin.users.index') }}" style="font-size:.85rem;color:#6b7280;text-decoration:none">← Prefeitos</a>
        <h1 style="font-size:1.4rem;font-weight:700;margin-top:.5rem">Editar — {{ $user->name }}</h1>
    </div>
    <form method="POST" action="{{ route('admin.users.update', $user) }}" style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb">
        @csrf @method('PUT')
        <div style="display:grid;gap:.75rem;margin-bottom:1.5rem">
            <div><label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Nome</label><input name="name" value="{{ old('name', $user->name) }}" required style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box"></div>
            <div><label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">E-mail</label><input name="email" type="email" value="{{ old('email', $user->email) }}" required style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box"></div>
            <div><label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Nova senha (deixe em branco para manter)</label><input name="password" type="password" style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box"></div>
            <div><label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Telefone</label><input name="phone" value="{{ old('phone', $user->phone) }}" style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box"></div>
            <div><label style="display:flex;align-items:center;gap:.5rem;font-size:.88rem;cursor:pointer"><input type="checkbox" name="is_active" value="1" {{ $user->is_active ? 'checked' : '' }}> Usuário ativo</label></div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:1rem">
            <a href="{{ route('admin.users.index') }}" style="padding:.65rem 1.2rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;color:#374151;text-decoration:none">Cancelar</a>
            <button type="submit" style="padding:.65rem 1.5rem;background:var(--gold);color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">Salvar</button>
        </div>
    </form>
</div>
@endsection
