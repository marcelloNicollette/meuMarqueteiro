@extends('layouts.admin')
@section('title', 'Integrações')
@section('content')
<div style="padding:2rem;max-width:1050px">

    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem">
        <div>
            <h1 style="font-size:1.4rem;font-weight:700">Monitor de Integrações</h1>
            <p style="font-size:.85rem;color:#6b7280;margin-top:.3rem">Ingestão de dados públicos → indexação RAG por município</p>
        </div>
        <div style="display:flex;gap:.75rem;align-items:center">
            <a href="{{ route('admin.settings.integrations') }}" style="padding:.5rem 1rem;border:1px solid #d1d5db;border-radius:8px;font-size:.85rem;color:#374151;text-decoration:none;background:#fff">⚙️ Configurar APIs</a>
            <form method="POST" action="{{ route('admin.integrations.sync-all') }}">
                @csrf
                <button type="submit" style="padding:.5rem 1.2rem;background:var(--gold);color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer">↻ Enfileirar Todos</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div style="background:#d1fae5;border:1px solid #6ee7b7;padding:.9rem 1rem;border-radius:8px;margin-bottom:1.25rem;color:#065f46;font-size:.88rem">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2;border:1px solid #fca5a5;padding:.9rem 1rem;border-radius:8px;margin-bottom:1.25rem;color:#991b1b;font-size:.88rem">{{ session('error') }}</div>
    @endif

    {{-- STATS --}}
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.75rem;margin-bottom:1.5rem">
        @foreach([
            ['label'=>'Municípios ativos', 'valor'=>$stats['municipios_ativos'], 'cor'=>'#0f1117'],
            ['label'=>'APIs ativas',        'valor'=>$stats['apis_ativas'].' / '.$stats['apis_total'], 'cor'=>'#16a34a'],
            ['label'=>'Embeddings totais',  'valor'=>number_format($stats['total_embeddings'],0,',','.'), 'cor'=>'#2563eb'],
            ['label'=>'Última sync',        'valor'=>$stats['ultima_sync'] ? \Carbon\Carbon::parse($stats['ultima_sync'])->format('d/m H:i') : '—', 'cor'=>'#6b7280'],
            ['label'=>'Cobertura APIs',     'valor'=>$stats['apis_total']>0 ? round(($stats['apis_ativas']/$stats['apis_total'])*100).'%' : '0%', 'cor'=>'#d97706'],
        ] as $s)
        <div style="background:#fff;padding:.9rem 1rem;border-radius:10px;border:1px solid #e5e7eb;text-align:center">
            <div style="font-size:1.4rem;font-weight:700;color:{{ $s['cor'] }}">{{ $s['valor'] }}</div>
            <div style="font-size:.72rem;color:#6b7280;margin-top:.2rem">{{ $s['label'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- APIS ATIVAS --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1rem;overflow:hidden">
        <div style="padding:.9rem 1.5rem;background:#f9fafb;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center">
            <h3 style="font-size:.9rem;font-weight:600">APIs configuradas e ativas</h3>
            <span style="font-size:.78rem;color:#6b7280">Estas APIs serão consultadas nos jobs de ingestão</span>
        </div>
        <div style="padding:1rem 1.5rem">
            @if(count($apisAtivas) === 0)
                <div style="text-align:center;padding:1rem;color:#9ca3af;font-size:.88rem">
                    Nenhuma API ativa. <a href="{{ route('admin.settings.integrations') }}" style="color:var(--gold)">Configure as integrações</a> primeiro.
                </div>
            @else
                <div style="display:flex;flex-wrap:wrap;gap:.4rem">
                    @foreach($apisAtivas as $key => $api)
                    <span style="padding:.25rem .75rem;background:#d1fae5;color:#065f46;border-radius:99px;font-size:.78rem;font-weight:500">✓ {{ $api['nome'] }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- MUNICÍPIOS --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden">
        <div style="padding:.9rem 1.5rem;background:#f9fafb;border-bottom:1px solid #e5e7eb">
            <h3 style="font-size:.9rem;font-weight:600">Status por município</h3>
        </div>

        @if($municipalities->isEmpty())
            <div style="padding:2rem;text-align:center;color:#9ca3af;font-size:.88rem">Nenhum município ativo.</div>
        @else
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <th style="padding:.7rem 1.5rem;text-align:left;font-size:.75rem;font-weight:600;color:#6b7280">MUNICÍPIO</th>
                    <th style="padding:.7rem 1rem;text-align:left;font-size:.75rem;font-weight:600;color:#6b7280">PLANO</th>
                    <th style="padding:.7rem 1rem;text-align:center;font-size:.75rem;font-weight:600;color:#6b7280">EMBEDDINGS</th>
                    <th style="padding:.7rem 1rem;text-align:center;font-size:.75rem;font-weight:600;color:#6b7280">DOCS</th>
                    <th style="padding:.7rem 1rem;text-align:left;font-size:.75rem;font-weight:600;color:#6b7280">ÚLTIMA SYNC</th>
                    <th style="padding:.7rem 1rem;text-align:center;font-size:.75rem;font-weight:600;color:#6b7280">AÇÕES</th>
                </tr>
            </thead>
            <tbody>
                @foreach($municipalities as $m)
                @php $embedCount = $embeddingCounts[$m->id] ?? 0; @endphp
                <tr style="border-bottom:1px solid #f9fafb">
                    <td style="padding:.85rem 1.5rem">
                        <div style="font-weight:600;font-size:.9rem;color:#0f1117">{{ $m->name }}</div>
                        <div style="font-size:.74rem;color:#9ca3af">{{ $m->state }} {{ $m->ibge_code ? '· IBGE '.$m->ibge_code : '· sem código IBGE' }}</div>
                    </td>
                    <td style="padding:.85rem 1rem">
                        @php $planoColors = ['essencial'=>['#f3f4f6','#374151'],'estrategico'=>['#fef3c7','#92400e'],'parceiro'=>['#d1fae5','#065f46']] @endphp
                        @php [$bg,$txt] = $planoColors[$m->subscription_tier] ?? ['#f3f4f6','#374151'] @endphp
                        <span style="padding:.2rem .65rem;background:{{ $bg }};color:{{ $txt }};border-radius:99px;font-size:.74rem;font-weight:600;text-transform:uppercase">{{ $m->subscription_tier }}</span>
                    </td>
                    <td style="padding:.85rem 1rem;text-align:center">
                        <span style="font-size:.9rem;font-weight:600;color:{{ $embedCount > 0 ? '#16a34a' : '#9ca3af' }}">
                            {{ number_format($embedCount, 0, ',', '.') }}
                        </span>
                        @if($embedCount > 0)
                            <div style="font-size:.7rem;color:#6b7280">chunks RAG</div>
                        @else
                            <div style="font-size:.7rem;color:#f87171">sem dados</div>
                        @endif
                    </td>
                    <td style="padding:.85rem 1rem;text-align:center">
                        <span style="font-size:.88rem;color:#374151">{{ $m->documents_count }}</span>
                    </td>
                    <td style="padding:.85rem 1rem">
                        <span style="font-size:.8rem;color:#6b7280">
                            {{ $m->data_last_synced_at ? \Carbon\Carbon::parse($m->data_last_synced_at)->format('d/m/Y H:i') : 'Nunca' }}
                        </span>
                    </td>
                    <td style="padding:.85rem 1rem">
                        <div style="display:flex;gap:.4rem;justify-content:center">
                            {{-- Sync imediato (síncrono) --}}
                            <form method="POST" action="{{ route('admin.integrations.sync-now', $m) }}">
                                @csrf
                                <button type="submit"
                                    title="Executar agora (síncrono — pode demorar)"
                                    style="padding:.3rem .7rem;border:1px solid #d97706;border-radius:6px;font-size:.75rem;background:#fef3c7;color:#92400e;cursor:pointer;font-weight:500">
                                    ⚡ Agora
                                </button>
                            </form>
                            {{-- Enfileirar job --}}
                            <form method="POST" action="{{ route('admin.integrations.sync', $m) }}">
                                @csrf
                                <button type="submit"
                                    title="Enfileirar job (assíncrono)"
                                    style="padding:.3rem .7rem;border:1px solid #d1d5db;border-radius:6px;font-size:.75rem;background:#fff;color:#374151;cursor:pointer">
                                    ↻ Job
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- INSTRUÇÃO --}}
    <div style="margin-top:1rem;padding:1rem 1.25rem;background:#f9fafb;border-radius:8px;font-size:.78rem;color:#6b7280;line-height:1.6">
        <strong>⚡ Agora</strong> — executa a ingestão de forma síncrona (pode demorar 30–60s dependendo das APIs).<br>
        <strong>↻ Job</strong> — enfileira para execução em background (requer <code>php artisan queue:work</code> rodando).<br>
        <strong>Enfileirar Todos</strong> — enfileira jobs para todos os municípios ativos de uma vez.<br>
        Para agendar ingestão automática diária: <code>php artisan marqueteiro:ingest</code> via cron ou Laravel Scheduler.
    </div>
</div>
@endsection
