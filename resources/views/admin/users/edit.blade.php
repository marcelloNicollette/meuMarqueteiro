@extends('layouts.admin')
@section('title', 'Editar — '.$user->name)
@section('content')
<div style="padding:2rem;max-width:600px">
    <div style="margin-bottom:1.5rem">
        <a href="{{ route('admin.users.index') }}" style="font-size:.85rem;color:#6b7280;text-decoration:none">← Usuários municipais</a>
        <h1 style="font-size:1.4rem;font-weight:700;margin-top:.5rem">Editar — {{ $user->name }}</h1>
    </div>
    <form method="POST" action="{{ route('admin.users.update', $user) }}" style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb">
        @csrf @method('PUT')
        @if($errors->any())<div style="background:#fee2e2;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#991b1b;font-size:.88rem">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        <div style="display:grid;gap:.75rem;margin-bottom:1.5rem">
            <div><label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Nome</label><input name="name" value="{{ old('name', $user->name) }}" required style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box"></div>
            <div><label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">E-mail</label><input name="email" type="email" value="{{ old('email', $user->email) }}" required style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box"></div>
            <div><label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Nova senha (deixe em branco para manter)</label><input name="password" type="password" style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box"></div>
            <div><label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Telefone</label><input name="phone" value="{{ old('phone', $user->phone) }}" style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box"></div>
            <div><label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Perfil</label>
                <select name="role" id="roleField" required style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                    @foreach (['mayor' => 'Prefeito', 'secretary' => 'Secretário', 'advisor' => 'Assessor'] as $value => $label)
                        <option value="{{ $value }}" {{ old('role', $user->role?->value) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div><label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Município</label>
                <select name="municipality_id" id="municipalityField" required style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                    <option value="">Selecione...</option>
                    @foreach($municipalities as $m)<option value="{{ $m->id }}" {{ (string) old('municipality_id', $user->municipality_id) === (string) $m->id ? 'selected' : '' }}>{{ $m->name }} — {{ $m->state_code }}</option>@endforeach
                </select>
            </div>
            <div><label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Secretaria vinculada</label>
                <select name="contact_area_id" id="contactAreaField" style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                    <option value="">Selecione...</option>
                    @foreach($contactAreas as $area)
                        <option value="{{ $area->id }}" data-municipality="{{ $area->municipality_id }}" {{ (string) old('contact_area_id', $user->contact_area_id) === (string) $area->id ? 'selected' : '' }}>
                            {{ $area->name }} — {{ $area->municipality?->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div id="advisorDemandPermission" style="display:none">
                <label style="display:flex;align-items:center;gap:.5rem;font-size:.88rem;cursor:pointer"><input type="checkbox" name="can_register_demands" value="1" {{ old('can_register_demands', $user->can_register_demands) ? 'checked' : '' }}> Assessor pode registrar demandas no Resolve ai</label>
            </div>
            <div><label style="display:flex;align-items:center;gap:.5rem;font-size:.88rem;cursor:pointer"><input type="checkbox" name="is_active" value="1" {{ $user->is_active ? 'checked' : '' }}> Usuário ativo</label></div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:1rem">
            <a href="{{ route('admin.users.index') }}" style="padding:.65rem 1.2rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;color:#374151;text-decoration:none">Cancelar</a>
            <button type="submit" style="padding:.65rem 1.5rem;background:var(--gold);color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">Salvar</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const roleField = document.getElementById('roleField');
        const municipalityField = document.getElementById('municipalityField');
        const contactAreaField = document.getElementById('contactAreaField');
        const advisorDemandPermission = document.getElementById('advisorDemandPermission');

        function syncContactAreas() {
            const municipalityId = municipalityField.value;
            [...contactAreaField.options].forEach((option, index) => {
                if (index === 0) return;
                option.hidden = municipalityId !== '' && option.dataset.municipality !== municipalityId;
            });
        }

        function syncRoleFields() {
            const role = roleField.value;
            contactAreaField.required = role === 'secretary' || role === 'advisor';
            advisorDemandPermission.style.display = role === 'advisor' ? 'block' : 'none';
        }

        roleField.addEventListener('change', syncRoleFields);
        municipalityField.addEventListener('change', syncContactAreas);
        syncContactAreas();
        syncRoleFields();
    })();
</script>
@endpush
