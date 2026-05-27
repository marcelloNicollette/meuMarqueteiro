@extends('layouts.admin')
@section('title', 'Áreas de contato — ' . $municipality->name)
@section('content')
    <div style="padding:2rem;max-width:960px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem">
            <div>
                <a href="{{ route('admin.municipalities.show', $municipality) }}"
                    style="font-size:.85rem;color:#6b7280;text-decoration:none">← {{ $municipality->name }}</a>
                <h1 style="font-size:1.4rem;font-weight:700;margin-top:.5rem">Áreas de contato</h1>
                <p style="color:#6b7280;font-size:.88rem">Cadastre secretarias, órgãos e pontos de contato do município.</p>
            </div>
        </div>

        @if (session('success'))
            <div
                style="background:#d1fae5;border:1px solid #6ee7b7;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#065f46">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div
                style="background:#fee2e2;border:1px solid #fca5a5;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#991b1b">
                {{ $errors->first() }}
            </div>
        @endif

        <div style="display:grid;grid-template-columns:1fr;gap:1rem">
            <div style="background:#fff;padding:1rem;border-radius:12px;border:1px solid #e5e7eb">
                <h3 style="font-size:.95rem;font-weight:600;margin-bottom:.75rem">Nova área</h3>
                <form method="POST" action="{{ route('admin.municipalities.contact-areas.store', $municipality) }}"
                    style="display:grid;grid-template-columns:1.3fr 1fr 1fr 1fr;gap:.6rem;align-items:end">
                    @csrf
                    <div>
                        <label style="display:block;font-size:.75rem;color:#6b7280;margin-bottom:.2rem">Área</label>
                        <input type="text" name="name" class="form-input"
                            style="width:100%;padding:.6rem;border:1px solid #e5e7eb;border-radius:8px"
                            placeholder="Ex: Secretaria de Educação">
                    </div>
                    <div>
                        <label style="display:block;font-size:.75rem;color:#6b7280;margin-bottom:.2rem">Contato</label>
                        <input type="text" name="contact_name" class="form-input"
                            style="width:100%;padding:.6rem;border:1px solid #e5e7eb;border-radius:8px"
                            placeholder="Nome do responsável">
                    </div>
                    <div>
                        <label style="display:block;font-size:.75rem;color:#6b7280;margin-bottom:.2rem">E-mail</label>
                        <input type="email" name="email" pattern=".+@.+" title="Informe um e-mail com @"
                            class="form-input" style="width:100%;padding:.6rem;border:1px solid #e5e7eb;border-radius:8px"
                            placeholder="email@org.gov.br">
                    </div>
                    <div>
                        <label style="display:block;font-size:.75rem;color:#6b7280;margin-bottom:.2rem">E-mail de
                            notificação</label>
                        <input type="email" name="notification_email" pattern=".+@.+" title="Informe um e-mail com @"
                            class="form-input" style="width:100%;padding:.6rem;border:1px solid #e5e7eb;border-radius:8px"
                            placeholder="resolveai@org.gov.br">
                    </div>
                    <div>
                        <label style="display:block;font-size:.75rem;color:#6b7280;margin-bottom:.2rem">Telefone</label>
                        <input type="text" name="phone" inputmode="tel" maxlength="15" class="form-input"
                            style="width:100%;padding:.6rem;border:1px solid #e5e7eb;border-radius:8px"
                            placeholder="(00) 00000-0000">
                    </div>
                    <div>
                        <label style="display:block;font-size:.75rem;color:#6b7280;margin-bottom:.2rem">Contato
                            backup</label>
                        <input type="text" name="backup_contact_name" class="form-input"
                            style="width:100%;padding:.6rem;border:1px solid #e5e7eb;border-radius:8px"
                            placeholder="Nome reserva">
                    </div>
                    <div>
                        <label style="display:block;font-size:.75rem;color:#6b7280;margin-bottom:.2rem">E-mail
                            backup</label>
                        <input type="email" name="backup_email" pattern=".+@.+" title="Informe um e-mail com @"
                            class="form-input" style="width:100%;padding:.6rem;border:1px solid #e5e7eb;border-radius:8px"
                            placeholder="backup@org.gov.br">
                    </div>
                    <div>
                        <label style="display:block;font-size:.75rem;color:#6b7280;margin-bottom:.2rem">Telefone
                            backup</label>
                        <input type="text" name="backup_phone" inputmode="tel" maxlength="15" class="form-input"
                            style="width:100%;padding:.6rem;border:1px solid #e5e7eb;border-radius:8px"
                            placeholder="(00) 00000-0000">
                    </div>
                    <div style="grid-column:1/-1">
                        <label style="display:block;font-size:.75rem;color:#6b7280;margin-bottom:.2rem">Observações
                            operacionais</label>
                        <input type="text" name="notes" class="form-input"
                            style="width:100%;padding:.6rem;border:1px solid #e5e7eb;border-radius:8px"
                            placeholder="Ex: demandas de iluminação e tapa-buraco entram direto nesta pasta">
                    </div>
                    <div style="grid-column:1/-1;display:flex;gap:.6rem;align-items:center">
                        <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:#374151">
                            <input type="checkbox" name="active" value="1" checked> Ativa
                        </label>
                        <button type="submit"
                            style="margin-left:auto;padding:.6rem 1rem;background:var(--gold);color:#fff;border:none;border-radius:8px;font-weight:600">Adicionar</button>
                    </div>
                </form>
            </div>

            <div style="background:#fff;padding:1rem;border-radius:12px;border:1px solid #e5e7eb">
                <h3 style="font-size:.95rem;font-weight:600;margin-bottom:.75rem">Cadastradas</h3>
                @if ($areas->isEmpty())
                    <div style="padding:1rem;color:#9ca3af;font-size:.9rem">Nenhuma área cadastrada ainda.</div>
                @else
                    <table style="width:100%;border-collapse:collapse">
                        <thead>
                            <tr style="border-bottom:1px solid #f3f4f6">
                                <th style="padding:.7rem;text-align:left;font-size:.75rem;color:#6b7280">ÁREA</th>
                                <th style="padding:.7rem;text-align:left;font-size:.75rem;color:#6b7280">CONTATOS</th>
                                <th style="padding:.7rem;text-align:left;font-size:.75rem;color:#6b7280">NOTIFICAÇÃO</th>
                                <th style="padding:.7rem;text-align:center;font-size:.75rem;color:#6b7280">ATIVA</th>
                                <th style="padding:.7rem;text-align:right;font-size:.75rem;color:#6b7280">AÇÕES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($areas as $a)
                                <tr style="border-bottom:1px solid #f9fafb">
                                    <td style="padding:.7rem">{{ $a->name }}</td>
                                    <td style="padding:.7rem;font-size:.82rem;line-height:1.45">
                                        <div>{{ $a->contact_name ?: '—' }}</div>
                                        <div style="color:#6b7280">{{ $a->email ?: '—' }}</div>
                                        <div style="color:#6b7280">{{ $a->phone ?: '—' }}</div>
                                        @if ($a->backup_contact_name || $a->backup_email || $a->backup_phone)
                                            <div style="margin-top:.35rem;padding-top:.35rem;border-top:1px solid #f3f4f6">
                                                <div>{{ $a->backup_contact_name ?: 'Backup sem nome' }}</div>
                                                <div style="color:#6b7280">{{ $a->backup_email ?: '—' }}</div>
                                                <div style="color:#6b7280">{{ $a->backup_phone ?: '—' }}</div>
                                            </div>
                                        @endif
                                    </td>
                                    <td style="padding:.7rem;font-size:.82rem;line-height:1.45">
                                        <div>{{ $a->notification_email ?: ($a->email ?: '—') }}</div>
                                        @if ($a->notes)
                                            <div style="color:#9ca3af;margin-top:.25rem">{{ $a->notes }}</div>
                                        @endif
                                    </td>
                                    <td style="padding:.7rem;text-align:center">{{ $a->active ? 'Sim' : 'Não' }}</td>
                                    <td style="padding:.7rem;text-align:right;white-space:nowrap">
                                        <form method="POST"
                                            action="{{ route('admin.municipalities.contact-areas.update', [$municipality, $a]) }}"
                                            style="display:inline-flex;gap:.4rem;align-items:center">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="active" value="{{ $a->active ? 1 : 0 }}">
                                            <button type="button" onclick="toggleEdit{{ $a->id }}()"
                                                style="padding:.4rem .6rem;border:1px solid #d1d5db;border-radius:8px;font-size:.8rem">Editar</button>
                                            <button
                                                formaction="{{ route('admin.municipalities.contact-areas.destroy', [$municipality, $a]) }}"
                                                formmethod="POST" onclick="return confirm('Remover esta área?')"
                                                style="padding:.4rem .6rem;border:1px solid #ef4444;color:#ef4444;border-radius:8px;font-size:.8rem">Excluir</button>
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                                <tr id="editRow{{ $a->id }}" style="display:none;background:#f9fafb">
                                    <td colspan="5" style="padding:1rem">
                                        <form method="POST"
                                            action="{{ route('admin.municipalities.contact-areas.update', [$municipality, $a]) }}"
                                            style="display:grid;grid-template-columns:1.3fr 1fr 1fr 1fr;gap:.6rem;align-items:end">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="name" value="{{ $a->name }}"
                                                style="padding:.6rem;border:1px solid #e5e7eb;border-radius:8px">
                                            <input type="text" name="contact_name" value="{{ $a->contact_name }}"
                                                style="padding:.6rem;border:1px solid #e5e7eb;border-radius:8px">
                                            <input type="email" name="email" pattern=".+@.+"
                                                title="Informe um e-mail com @" value="{{ $a->email }}"
                                                style="padding:.6rem;border:1px solid #e5e7eb;border-radius:8px">
                                            <input type="email" name="notification_email" pattern=".+@.+"
                                                title="Informe um e-mail com @" value="{{ $a->notification_email }}"
                                                style="padding:.6rem;border:1px solid #e5e7eb;border-radius:8px">
                                            <input type="text" name="phone" inputmode="tel" maxlength="15"
                                                value="{{ $a->phone }}"
                                                style="padding:.6rem;border:1px solid #e5e7eb;border-radius:8px">
                                            <input type="text" name="backup_contact_name"
                                                value="{{ $a->backup_contact_name }}"
                                                style="padding:.6rem;border:1px solid #e5e7eb;border-radius:8px">
                                            <input type="email" name="backup_email" pattern=".+@.+"
                                                title="Informe um e-mail com @" value="{{ $a->backup_email }}"
                                                style="padding:.6rem;border:1px solid #e5e7eb;border-radius:8px">
                                            <input type="text" name="backup_phone" inputmode="tel" maxlength="15"
                                                value="{{ $a->backup_phone }}"
                                                style="padding:.6rem;border:1px solid #e5e7eb;border-radius:8px">
                                            <input type="text" name="notes" value="{{ $a->notes }}"
                                                style="padding:.6rem;border:1px solid #e5e7eb;border-radius:8px;grid-column:1/4">
                                            <label
                                                style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:#374151;justify-content:center">
                                                <input type="checkbox" name="active" value="1"
                                                    @checked($a->active)> Ativa
                                            </label>
                                            <button type="submit"
                                                style="padding:.6rem 1rem;background:#111827;color:#fff;border:none;border-radius:8px">Salvar</button>
                                        </form>
                                    </td>
                                </tr>
                                <script>
                                    function toggleEdit{{ $a->id }}() {
                                        const r = document.getElementById('editRow{{ $a->id }}');
                                        r.style.display = r.style.display === 'none' ? 'table-row' : 'none';
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

<script>
    (function() {
        function onlyDigits(v) {
            return (v || '').toString().replace(/\D/g, '');
        }

        function formatPhone(d) {
            const digits = (d || '').slice(0, 11);
            if (!digits) return '';
            if (digits.length <= 2) return `(${digits}`;
            const ddd = digits.slice(0, 2);
            const rest = digits.slice(2);
            if (rest.length <= 4) return `(${ddd}) ${rest}`;
            if (rest.length <= 8) return `(${ddd}) ${rest.slice(0, 4)}-${rest.slice(4)}`;
            return `(${ddd}) ${rest.slice(0, 5)}-${rest.slice(5)}`;
        }

        function attachPhoneMask(input) {
            input.addEventListener('input', () => {
                const digits = onlyDigits(input.value);
                input.value = formatPhone(digits);
            });
            if (input.value) {
                input.value = formatPhone(onlyDigits(input.value));
            }
        }

        function attachEmailCheck(form) {
            form.addEventListener('submit', (e) => {
                const emailInput = form.querySelector('input[name="email"]');
                if (!emailInput) return;
                const v = (emailInput.value || '').trim();
                if (v && !v.includes('@')) {
                    e.preventDefault();
                    emailInput.focus();
                    alert('Informe um e-mail válido contendo "@".');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('input[name="phone"]').forEach(attachPhoneMask);
            document.querySelectorAll('form').forEach((f) => {
                if (f.querySelector('input[name="email"]')) attachEmailCheck(f);
            });
        });
    })();
</script>
