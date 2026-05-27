@extends('layouts.admin')
@section('title', 'Localidades — '.$municipality->name)
@section('content')
<div style="padding:2rem;max-width:1040px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem">
        <div>
            <a href="{{ route('admin.municipalities.show', $municipality) }}" style="font-size:.85rem;color:#6b7280;text-decoration:none">← {{ $municipality->name }}</a>
            <h1 style="font-size:1.4rem;font-weight:700;margin-top:.5rem">Localidades operacionais</h1>
            <p style="color:#6b7280;font-size:.88rem">Cadastre bairros, distritos, comunidades e recortes usados no Resolve ai.</p>
        </div>
    </div>

    @if (session('success'))
        <div style="background:#d1fae5;border:1px solid #6ee7b7;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#065f46">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div style="background:#fee2e2;border:1px solid #fca5a5;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#991b1b">
            {{ $errors->first() }}
        </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr;gap:1rem">
        <div style="background:#fff;padding:1rem;border-radius:12px;border:1px solid #e5e7eb">
            <h3 style="font-size:.95rem;font-weight:600;margin-bottom:.75rem">Nova localidade</h3>
            <form method="POST" action="{{ route('admin.municipalities.localities.store', $municipality) }}" style="display:grid;grid-template-columns:1.5fr .9fr .9fr 1.2fr;gap:.6rem;align-items:end">
                @csrf
                <div>
                    <label style="display:block;font-size:.75rem;color:#6b7280;margin-bottom:.2rem">Nome</label>
                    <input type="text" name="name" style="width:100%;padding:.6rem;border:1px solid #e5e7eb;border-radius:8px" placeholder="Ex: Jardim América">
                </div>
                <div>
                    <label style="display:block;font-size:.75rem;color:#6b7280;margin-bottom:.2rem">Tipo</label>
                    <select name="type" style="width:100%;padding:.6rem;border:1px solid #e5e7eb;border-radius:8px">
                        @foreach(['bairro' => 'Bairro', 'distrito' => 'Distrito', 'comunidade' => 'Comunidade', 'zona' => 'Zona', 'rural' => 'Rural', 'outro' => 'Outro'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:.75rem;color:#6b7280;margin-bottom:.2rem">Zona / região</label>
                    <input type="text" name="zone" style="width:100%;padding:.6rem;border:1px solid #e5e7eb;border-radius:8px" placeholder="Ex: Norte">
                </div>
                <div>
                    <label style="display:block;font-size:.75rem;color:#6b7280;margin-bottom:.2rem">Observações</label>
                    <input type="text" name="notes" style="width:100%;padding:.6rem;border:1px solid #e5e7eb;border-radius:8px" placeholder="Referência opcional">
                </div>
                <div style="grid-column:1/-1;display:flex;gap:.6rem;align-items:center">
                    <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:#374151">
                        <input type="checkbox" name="active" value="1" checked> Ativa
                    </label>
                    <button type="submit" style="margin-left:auto;padding:.6rem 1rem;background:var(--gold);color:#fff;border:none;border-radius:8px;font-weight:600">Adicionar</button>
                </div>
            </form>
        </div>

        <div style="background:#fff;padding:1rem;border-radius:12px;border:1px solid #e5e7eb">
            <h3 style="font-size:.95rem;font-weight:600;margin-bottom:.75rem">Base territorial</h3>
            @if($localities->isEmpty())
                <div style="padding:1rem;color:#9ca3af;font-size:.9rem">Nenhuma localidade cadastrada ainda.</div>
            @else
                <table style="width:100%;border-collapse:collapse">
                    <thead>
                        <tr style="border-bottom:1px solid #f3f4f6">
                            <th style="padding:.7rem;text-align:left;font-size:.75rem;color:#6b7280">LOCALIDADE</th>
                            <th style="padding:.7rem;text-align:left;font-size:.75rem;color:#6b7280">TIPO</th>
                            <th style="padding:.7rem;text-align:left;font-size:.75rem;color:#6b7280">ZONA</th>
                            <th style="padding:.7rem;text-align:left;font-size:.75rem;color:#6b7280">OBSERVAÇÕES</th>
                            <th style="padding:.7rem;text-align:center;font-size:.75rem;color:#6b7280">ATIVA</th>
                            <th style="padding:.7rem;text-align:right;font-size:.75rem;color:#6b7280">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($localities as $locality)
                            <tr style="border-bottom:1px solid #f9fafb">
                                <td style="padding:.7rem">{{ $locality->name }}</td>
                                <td style="padding:.7rem">{{ ucfirst($locality->type) }}</td>
                                <td style="padding:.7rem">{{ $locality->zone ?: '—' }}</td>
                                <td style="padding:.7rem">{{ $locality->notes ?: '—' }}</td>
                                <td style="padding:.7rem;text-align:center">{{ $locality->active ? 'Sim' : 'Não' }}</td>
                                <td style="padding:.7rem;text-align:right;white-space:nowrap">
                                    <form method="POST" action="{{ route('admin.municipalities.localities.update', [$municipality, $locality]) }}" style="display:inline-flex;gap:.4rem;align-items:center">
                                        @csrf
                                        @method('PUT')
                                        <button type="button" onclick="toggleLocalityEdit{{ $locality->id }}()" style="padding:.4rem .6rem;border:1px solid #d1d5db;border-radius:8px;font-size:.8rem">Editar</button>
                                        <button formaction="{{ route('admin.municipalities.localities.destroy', [$municipality, $locality]) }}" formmethod="POST" onclick="return confirm('Remover esta localidade?')" style="padding:.4rem .6rem;border:1px solid #ef4444;color:#ef4444;border-radius:8px;font-size:.8rem">Excluir</button>
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                            <tr id="editLocalityRow{{ $locality->id }}" style="display:none;background:#f9fafb">
                                <td colspan="6" style="padding:1rem">
                                    <form method="POST" action="{{ route('admin.municipalities.localities.update', [$municipality, $locality]) }}" style="display:grid;grid-template-columns:1.4fr .9fr .8fr 1.2fr .6fr auto;gap:.6rem;align-items:end">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="name" value="{{ $locality->name }}" style="padding:.6rem;border:1px solid #e5e7eb;border-radius:8px">
                                        <select name="type" style="padding:.6rem;border:1px solid #e5e7eb;border-radius:8px">
                                            @foreach(['bairro' => 'Bairro', 'distrito' => 'Distrito', 'comunidade' => 'Comunidade', 'zona' => 'Zona', 'rural' => 'Rural', 'outro' => 'Outro'] as $value => $label)
                                                <option value="{{ $value }}" @selected($locality->type === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="zone" value="{{ $locality->zone }}" style="padding:.6rem;border:1px solid #e5e7eb;border-radius:8px">
                                        <input type="text" name="notes" value="{{ $locality->notes }}" style="padding:.6rem;border:1px solid #e5e7eb;border-radius:8px">
                                        <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:#374151;justify-content:center">
                                            <input type="checkbox" name="active" value="1" @checked($locality->active)> Ativa
                                        </label>
                                        <button type="submit" style="padding:.6rem 1rem;background:#111827;color:#fff;border:none;border-radius:8px">Salvar</button>
                                    </form>
                                </td>
                            </tr>
                            <script>
                                function toggleLocalityEdit{{ $locality->id }}() {
                                    const row = document.getElementById('editLocalityRow{{ $locality->id }}');
                                    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
                                }
                            </script>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
