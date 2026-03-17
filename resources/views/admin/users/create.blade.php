@extends('layouts.admin')
@section('title', 'Novo Prefeito')
@section('content')
<div style="padding:2rem;max-width:600px">
    <div style="margin-bottom:1.5rem">
        <a href="{{ route('admin.users.index') }}" style="font-size:.85rem;color:#6b7280;text-decoration:none">← Prefeitos</a>
        <h1 style="font-size:1.4rem;font-weight:700;margin-top:.5rem">Novo Prefeito</h1>
    </div>
    <form method="POST" action="{{ route('admin.users.store') }}" style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb">
        @csrf
        @if($errors->any())<div style="background:#fee2e2;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#991b1b;font-size:.88rem">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        <div style="display:grid;gap:.75rem;margin-bottom:1.5rem">
            <div><label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Nome completo *</label><input name="name" value="{{ old('name') }}" required style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box"></div>
            <div><label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">E-mail *</label><input name="email" type="email" value="{{ old('email') }}" required style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box"></div>
            <div><label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Senha *</label><input name="password" type="password" required style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box"></div>
            <div><label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Telefone</label><input name="phone" value="{{ old('phone') }}" style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box"></div>
            <div><label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Município *</label>
                <select name="municipality_id" required style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                    <option value="">Selecione...</option>
                    @foreach($municipalities as $m)<option value="{{ $m->id }}" {{ old('municipality_id') == $m->id ? 'selected' : '' }}>{{ $m->name }} — {{ $m->state_code }}</option>@endforeach
                </select>
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:1rem">
            <a href="{{ route('admin.users.index') }}" style="padding:.65rem 1.2rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;color:#374151;text-decoration:none">Cancelar</a>
            <button type="submit" style="padding:.65rem 1.5rem;background:var(--gold);color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">Criar</button>
        </div>
    </form>
</div>
@endsection
