@extends('layouts.admin')
@section('title', 'Radar de Programas Federais — Admin')
@section('content')

    <div style="padding:2rem;max-width:1100px">

        {{-- ── Header ─── --}}
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem">
            <div>
                <h1 style="font-size:1.4rem;font-weight:700">Radar de Programas Federais</h1>
                <p style="font-size:.85rem;color:#6b7280;margin-top:.3rem">
                    Sincronismo Transferegov + Portal da Transparência + análise Claude por município
                </p>
            </div>
            <div style="display:flex;gap:.75rem;align-items:center">
                <a href="{{ route('admin.settings.integrations') }}"
                    style="padding:.5rem 1rem;border:1px solid #d1d5db;border-radius:8px;font-size:.85rem;color:#374151;text-decoration:none;background:#fff">
                    ⚙️ Configurar APIs
                </a>
                <button onclick="syncAll(false)"
                    style="padding:.5rem 1.2rem;background:#0f1117;color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer">
                    ↻ Sincronizar Todos
                </button>
            </div>
        </div>

        {{-- ── Stats globais ─── --}}
        <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:.75rem;margin-bottom:1.5rem">
            @foreach ([['l' => 'Total programas', 'v' => $stats['total'], 'c' => '#0f1117'], ['l' => 'Abertos', 'v' => $stats['open'], 'c' => '#1a5fa8'], ['l' => 'Encerrando', 'v' => $stats['closing'], 'c' => '#e65100'], ['l' => 'Candidatados', 'v' => $stats['applied'], 'c' => '#059669'], ['l' => 'Alta compatib.', 'v' => $stats['high_match'], 'c' => '#b8902a'], ['l' => 'Última sync', 'v' => $stats['last_sync'] ? \Carbon\Carbon::parse($stats['last_sync'])->diffForHumans() : 'Nunca', 'c' => '#6b7280']] as $s)
                <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:.9rem 1rem">
                    <div style="font-size:1.3rem;font-weight:700;color:{{ $s['c'] }}">{{ $s['v'] }}</div>
                    <div style="font-size:.72rem;color:#9ca3af;margin-top:.15rem">{{ $s['l'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- ── Chave Transparência ─── --}}
        @php
            $tpKey =
                \App\Models\SystemSetting::get('integration_transparencia_chave') ?:
                \App\Models\SystemSetting::get('transparencia_api_key');
        @endphp
        @if (!$tpKey)
            <div
                style="background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:1rem 1.25rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:1rem">
                <span style="font-size:1.2rem">⚠️</span>
                <div style="flex:1">
                    <strong style="font-size:.88rem;color:#92400e">Chave do Portal da Transparência não configurada</strong>
                    <p style="font-size:.8rem;color:#b45309;margin-top:.2rem">
                        Sem ela, apenas o Transferegov será usado. Obtenha grátis em
                        <a href="https://portaldatransparencia.gov.br/api" target="_blank"
                            style="color:#b45309">portaldatransparencia.gov.br/api</a>
                    </p>
                </div>
                <a href="{{ route('admin.settings.integrations') }}"
                    style="padding:.45rem .9rem;background:#f59e0b;color:#fff;border-radius:7px;font-size:.8rem;text-decoration:none;white-space:nowrap">
                    Configurar
                </a>
            </div>
        @endif

        {{-- ── Toast de feedback ─── --}}
        <div id="toast"
            style="display:none;background:#0f1117;color:#fff;padding:.75rem 1.1rem;border-radius:9px;
         font-size:.84rem;margin-bottom:1rem;border-left:3px solid #b8902a"
            id="toast"></div>

        {{-- ── Tabela por município ─── --}}
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                        <th
                            style="padding:.85rem 1rem;text-align:left;font-size:.75rem;color:#6b7280;font-weight:600;letter-spacing:.05em;text-transform:uppercase">
                            Município</th>
                        <th
                            style="padding:.85rem 1rem;text-align:center;font-size:.75rem;color:#6b7280;font-weight:600;letter-spacing:.05em;text-transform:uppercase">
                            Programas</th>
                        <th
                            style="padding:.85rem 1rem;text-align:center;font-size:.75rem;color:#6b7280;font-weight:600;letter-spacing:.05em;text-transform:uppercase">
                            Abertos</th>
                        <th
                            style="padding:.85rem 1rem;text-align:center;font-size:.75rem;color:#6b7280;font-weight:600;letter-spacing:.05em;text-transform:uppercase">
                            Match médio</th>
                        <th
                            style="padding:.85rem 1rem;text-align:center;font-size:.75rem;color:#6b7280;font-weight:600;letter-spacing:.05em;text-transform:uppercase">
                            Última sync</th>
                        <th
                            style="padding:.85rem 1rem;text-align:right;font-size:.75rem;color:#6b7280;font-weight:600;letter-spacing:.05em;text-transform:uppercase">
                            Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($municipalities as $m)
                        @php
                            $ps = $programStats->get($m->id);
                            $total = $ps->total ?? 0;
                            $open = $ps->open_count ?? 0;
                            $avgScore = $ps ? round($ps->avg_score * 100) : null;
                            $lastUpd = $ps ? \Carbon\Carbon::parse($ps->last_updated)->diffForHumans() : null;
                            $scoreColor = $avgScore >= 80 ? '#059669' : ($avgScore >= 60 ? '#e65100' : '#9ca3af');
                        @endphp
                        <tr style="border-bottom:1px solid #f3f4f6" id="row-{{ $m->id }}">
                            <td style="padding:.9rem 1rem">
                                <div style="font-weight:500;font-size:.9rem;color:#111">{{ $m->name }}</div>
                                <div style="font-size:.75rem;color:#9ca3af">{{ $m->state_code }} • IBGE {{ $m->ibge_code }}
                                </div>
                            </td>
                            <td style="padding:.9rem 1rem;text-align:center">
                                <button onclick="showPrograms({{ $m->id }}, '{{ $m->name }}')"
                                    style="font-size:.9rem;font-weight:600;color:#1a5fa8;background:none;border:none;cursor:pointer;text-decoration:underline">
                                    {{ $total }}
                                </button>
                            </td>
                            <td
                                style="padding:.9rem 1rem;text-align:center;font-size:.88rem;color:#{{ $open > 0 ? '059669' : '9ca3af' }};font-weight:{{ $open > 0 ? '600' : '400' }}">
                                {{ $open }}
                            </td>
                            <td style="padding:.9rem 1rem;text-align:center">
                                @if ($avgScore !== null)
                                    <span
                                        style="font-size:.85rem;font-weight:600;color:{{ $scoreColor }}">{{ $avgScore }}%</span>
                                @else
                                    <span style="font-size:.8rem;color:#d1d5db">—</span>
                                @endif
                            </td>
                            <td style="padding:.9rem 1rem;text-align:center;font-size:.8rem;color:#9ca3af">
                                <span id="sync-time-{{ $m->id }}">{{ $lastUpd ?? 'Nunca' }}</span>
                            </td>
                            <td style="padding:.9rem 1rem;text-align:right">
                                <div style="display:flex;gap:.5rem;justify-content:flex-end;align-items:center">
                                    <button onclick="syncMunicipality({{ $m->id }}, '{{ $m->name }}', false)"
                                        id="btn-sync-{{ $m->id }}"
                                        style="padding:.38rem .8rem;background:#0f1117;color:#fff;border:none;border-radius:7px;font-size:.78rem;cursor:pointer;white-space:nowrap">
                                        ↻ Sync
                                    </button>
                                    <button onclick="syncMunicipality({{ $m->id }}, '{{ $m->name }}', true)"
                                        title="Re-analisar todos os programas"
                                        style="padding:.38rem .7rem;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:7px;font-size:.78rem;cursor:pointer">
                                        🔄 Forçar
                                    </button>
                                    <button onclick="clearClosed({{ $m->id }}, '{{ $m->name }}')"
                                        title="Remover programas encerrados"
                                        style="padding:.38rem .7rem;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:7px;font-size:.78rem;cursor:pointer">
                                        🗑
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ── Histórico do cron ─── --}}
        <div style="margin-top:1.5rem;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:1rem 1.25rem">
            <div style="font-size:.8rem;font-weight:600;color:#374151;margin-bottom:.5rem">⏰ Agendamento automático</div>
            <div style="font-size:.8rem;color:#6b7280;line-height:1.7">
                O comando <code
                    style="background:#e5e7eb;padding:.1rem .4rem;border-radius:4px">marqueteiro:sync-federal-programs</code>
                é executado automaticamente toda <strong>segunda-feira às 03h00</strong> (BRT).<br>
                Para rodar manualmente via terminal:
            </div>
            <pre
                style="background:#0f1117;color:#e2e8f0;padding:.85rem 1rem;border-radius:8px;font-size:.78rem;margin-top:.6rem;overflow-x:auto">docker exec marqueteiro_app php artisan marqueteiro:sync-federal-programs
docker exec marqueteiro_app php artisan marqueteiro:sync-federal-programs --municipality=1 --force
docker exec marqueteiro_app php artisan marqueteiro:sync-federal-programs --dry-run</pre>
        </div>

    </div>

    {{-- ── Modal de programas do município ─── --}}
    <div id="programsModal"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;overflow-y:auto">
        <div style="background:#fff;border-radius:14px;max-width:800px;margin:2rem auto;overflow:hidden">
            <div
                style="display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.4rem;border-bottom:1px solid #e5e7eb">
                <h2 style="font-size:1.1rem;font-weight:600" id="modalTitle">Programas</h2>
                <button onclick="closeModal()"
                    style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#9ca3af">✕</button>
            </div>
            <div id="modalBody" style="padding:1.25rem;max-height:70vh;overflow-y:auto">
                <div style="text-align:center;padding:2rem;color:#9ca3af">Carregando...</div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // ── Toast ───────────────────────────────────────────────────────────────
        function toast(msg, ok = true) {
            const el = document.getElementById('toast');
            el.style.display = 'block';
            el.style.borderLeftColor = ok ? '#059669' : '#dc2626';
            el.innerHTML = msg;
            setTimeout(() => el.style.display = 'none', 6000);
        }

        // ── Sync de um município ─────────────────────────────────────────────────
        async function syncMunicipality(id, name, force) {
            const btn = document.getElementById(`btn-sync-${id}`);
            const row = document.getElementById(`row-${id}`);
            btn.disabled = true;
            btn.textContent = '⏳ Sincronizando...';
            row.style.opacity = '.6';

            try {
                const res = await fetch(`/admin/federal-programs/${id}/sync`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    },
                    body: JSON.stringify({
                        force
                    }),
                });
                const data = await res.json();
                toast(data.message, data.ok);

                if (data.ok) {
                    document.getElementById(`sync-time-${id}`).textContent = 'agora mesmo';
                }
            } catch (e) {
                toast('Erro de comunicação: ' + e.message, false);
            } finally {
                btn.disabled = false;
                btn.textContent = '↻ Sync';
                row.style.opacity = '1';
            }
        }

        // ── Sync geral ───────────────────────────────────────────────────────────
        async function syncAll(force) {
            if (!confirm('Enfileirar sincronização para todos os municípios ativos?')) return;

            try {
                const res = await fetch('/admin/federal-programs/sync-all', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    },
                    body: JSON.stringify({
                        force
                    }),
                });
                const data = await res.json();
                toast(data.message, data.ok);
            } catch (e) {
                toast('Erro: ' + e.message, false);
            }
        }

        // ── Limpar encerrados ────────────────────────────────────────────────────
        async function clearClosed(id, name) {
            if (!confirm(`Remover programas encerrados de ${name}?`)) return;

            const res = await fetch(`/admin/federal-programs/${id}/clear`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': CSRF
                },
            });
            const data = await res.json();
            toast(data.message, data.ok);
        }

        // ── Modal de programas ───────────────────────────────────────────────────
        async function showPrograms(id, name) {
            document.getElementById('programsModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = `${name} — Programas federais`;

            const res = await fetch(`/admin/federal-programs/${id}/programs`);
            const data = await res.json();

            const AREA_COLORS = {
                saude: '#fce4ec',
                educacao: '#e3f2fd',
                infraestrutura: '#f3e5f5',
                saneamento: '#e0f2f1',
                habitacao: '#fff8e1',
                social: '#f0fdf4',
                outros: '#f3f4f6'
            };
            const STATUS = {
                open: '✅ Aberto',
                closing: '⚠️ Encerrando',
                applied: '📋 Candidatado',
                closed: '🔒 Encerrado'
            };

            const html = data.programs.length === 0 ?
                '<p style="text-align:center;color:#9ca3af;padding:2rem">Nenhum programa cadastrado.</p>' :
                data.programs.map(p => `
            <div style="border:1px solid #e5e7eb;border-radius:10px;padding:1rem;margin-bottom:.75rem">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;margin-bottom:.5rem">
                    <div>
                        <span style="font-size:.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;
                              padding:.18rem .55rem;border-radius:4px;background:${AREA_COLORS[p.area]||'#f3f4f6'}">
                            ${p.area||'outros'}
                        </span>
                        <div style="font-size:.9rem;font-weight:500;color:#111;margin-top:.4rem">${p.program_name}</div>
                        <div style="font-size:.75rem;color:#9ca3af">${p.ministry||''}</div>
                    </div>
                    <div style="text-align:right;flex-shrink:0">
                        <div style="font-size:.85rem;font-weight:700;color:#b8902a">${Math.round((p.match_score||0)*100)}%</div>
                        <div style="font-size:.7rem;color:#9ca3af">compatível</div>
                    </div>
                </div>
                <div style="font-size:.78rem;color:#6b7280;margin-bottom:.5rem">${p.match_reason||''}</div>
                <div style="display:flex;align-items:center;gap:1rem">
                    <span style="font-size:.75rem">${STATUS[p.status]||p.status}</span>
                    ${p.max_value ? `<span style="font-size:.75rem;color:#374151">R$ ${Number(p.max_value).toLocaleString('pt-BR')}</span>` : ''}
                    ${p.deadline ? `<span style="font-size:.75rem;color:#6b7280">Prazo: ${new Date(p.deadline).toLocaleDateString('pt-BR')}</span>` : ''}
                    ${p.source_url ? `<a href="${p.source_url}" target="_blank" style="font-size:.75rem;color:#1a5fa8;margin-left:auto">Edital ↗</a>` : ''}
                </div>
            </div>`).join('');

            document.getElementById('modalBody').innerHTML = html;
        }

        function closeModal() {
            document.getElementById('programsModal').style.display = 'none';
        }

        document.getElementById('programsModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
@endpush
