@extends('layouts.admin')
@section('title', 'Diagnóstico do Sistema')
@section('content')
<div style="padding:2rem;max-width:850px">

    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem">
        <div>
            <h1 style="font-size:1.4rem;font-weight:700">Diagnóstico do Sistema</h1>
            <p style="font-size:.85rem;color:#6b7280;margin-top:.3rem">Verifique se chat, RAG e integrações estão funcionando</p>
        </div>
        <button onclick="location.reload()" style="padding:.5rem 1rem;border:1px solid #d1d5db;border-radius:8px;font-size:.85rem;background:#fff;cursor:pointer">↻ Atualizar</button>
    </div>

    {{-- CHECKS --}}
    @php
        $icons = ['ok'=>'✓','warning'=>'⚠','error'=>'✕','info'=>'ℹ'];
        $colors = [
            'ok'      => ['bg'=>'#d1fae5','border'=>'#6ee7b7','txt'=>'#065f46','icon'=>'#16a34a'],
            'warning' => ['bg'=>'#fef3c7','border'=>'#fcd34d','txt'=>'#92400e','icon'=>'#d97706'],
            'error'   => ['bg'=>'#fee2e2','border'=>'#fca5a5','txt'=>'#991b1b','icon'=>'#dc2626'],
            'info'    => ['bg'=>'#dbeafe','border'=>'#93c5fd','txt'=>'#1e40af','icon'=>'#2563eb'],
        ];
    @endphp

    <div style="display:grid;gap:.75rem;margin-bottom:1.5rem">
        @foreach($checks as $key => $check)
        @php $c = $colors[$check['status']] @endphp
        <div style="background:{{ $c['bg'] }};border:1px solid {{ $c['border'] }};border-radius:10px;padding:1rem 1.25rem;display:flex;gap:1rem;align-items:flex-start">
            <div style="font-size:1.1rem;color:{{ $c['icon'] }};font-weight:700;margin-top:.1rem">{{ $icons[$check['status']] }}</div>
            <div style="flex:1">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-weight:600;font-size:.9rem;color:{{ $c['txt'] }}">{{ $check['label'] }}</span>
                    @if($check['detalhe'])
                        <span style="font-size:.75rem;color:{{ $c['txt'] }};opacity:.75">{{ $check['detalhe'] }}</span>
                    @endif
                </div>
                <div style="font-size:.82rem;color:{{ $c['txt'] }};margin-top:.25rem;opacity:.9">{{ $check['msg'] }}</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- TESTES AO VIVO --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;margin-bottom:1.25rem;overflow:hidden">
        <div style="padding:1rem 1.5rem;background:#f9fafb;border-bottom:1px solid #e5e7eb">
            <h3 style="font-size:.95rem;font-weight:600">Testes ao vivo</h3>
        </div>
        <div style="padding:1.25rem 1.5rem;display:grid;gap:.75rem">

            {{-- Teste IA --}}
            <div style="display:flex;align-items:center;gap:1rem">
                <button onclick="testar('ai')"
                    style="padding:.5rem 1.2rem;background:#0f1117;color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;white-space:nowrap">
                    ▶ Testar chat com IA
                </button>
                <div id="result-ai" style="font-size:.82rem;color:#6b7280;flex:1"></div>
            </div>

            {{-- Teste RAG --}}
            <div style="display:flex;align-items:center;gap:1rem">
                <button onclick="testar('rag')"
                    style="padding:.5rem 1.2rem;background:#0f1117;color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;white-space:nowrap">
                    ▶ Testar busca RAG
                </button>
                <div id="result-rag" style="font-size:.82rem;color:#6b7280;flex:1"></div>
            </div>

        </div>
    </div>

    {{-- STATUS POR MUNICÍPIO --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;margin-bottom:1.25rem">
        <div style="padding:1rem 1.5rem;background:#f9fafb;border-bottom:1px solid #e5e7eb">
            <h3 style="font-size:.95rem;font-weight:600">Configuração por município</h3>
        </div>
        <div style="padding:.5rem 0">
            @forelse($munChecks as $m)
            @php $c = $colors[$m['status']] @endphp
            <div style="padding:.75rem 1.5rem;border-bottom:1px solid #f9fafb;display:flex;align-items:center;gap:1rem">
                <span style="font-size:.9rem;color:{{ $c['icon'] }};font-weight:700">{{ $icons[$m['status']] }}</span>
                <div style="flex:1">
                    <span style="font-weight:600;font-size:.88rem">{{ $m['nome'] }}</span>
                    @if(count($m['issues']) > 0)
                        <span style="font-size:.78rem;color:#9ca3af;margin-left:.5rem">— {{ implode(', ', $m['issues']) }}</span>
                    @else
                        <span style="font-size:.78rem;color:#16a34a;margin-left:.5rem">— totalmente configurado</span>
                    @endif
                </div>
            </div>
            @empty
            <div style="padding:1.5rem;text-align:center;color:#9ca3af;font-size:.88rem">Nenhum município ativo.</div>
            @endforelse
        </div>
    </div>

    {{-- FLUXO EXPLICADO --}}
    <div style="background:#f9fafb;border-radius:12px;border:1px solid #e5e7eb;padding:1.25rem 1.5rem">
        <h3 style="font-size:.9rem;font-weight:600;margin-bottom:1rem;color:#374151">Como funciona o fluxo atual</h3>
        <div style="display:grid;gap:.6rem;font-size:.82rem;color:#6b7280">
            <div style="display:flex;gap:.75rem">
                <span style="background:#0f1117;color:#fff;border-radius:99px;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0">1</span>
                <div><strong style="color:#374151">Chat do prefeito</strong> → AssistantService monta system prompt com dados do município (population, IDHM, voice_profile, mapa político, compromissos em andamento)</div>
            </div>
            <div style="display:flex;gap:.75rem">
                <span style="background:#0f1117;color:#fff;border-radius:99px;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0">2</span>
                <div><strong style="color:#374151">RAG (se houver embeddings)</strong> → busca chunks similares em document_embeddings para o município + base geral. Requer pgvector + chave OpenAI para embed</div>
            </div>
            <div style="display:flex;gap:.75rem">
                <span style="background:#0f1117;color:#fff;border-radius:99px;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0">3</span>
                <div><strong style="color:#374151">APIs externas</strong> → ainda <em>não</em> buscam dados automaticamente. São apenas marcadas como ativas. O próximo passo é criar jobs que buscam e indexam os dados via RAG</div>
            </div>
            <div style="display:flex;gap:.75rem">
                <span style="background:#d97706;color:#fff;border-radius:99px;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0">!</span>
                <div><strong style="color:#d97706">Provider: {{ $provider }} | Modelo: {{ $model }}</strong> — configurado em Configurações → IA</div>
            </div>
        </div>
    </div>

</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

async function testar(tipo) {
    const el  = document.getElementById('result-' + tipo);
    el.style.color = '#6b7280';
    el.textContent = 'Testando...';

    try {
        const url = tipo === 'ai'
            ? '{{ route("admin.diagnostic.test-ai") }}'
            : '{{ route("admin.diagnostic.test-rag") }}';

        const res  = await fetch(url, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json'} });
        const data = await res.json();

        if (data.ok) {
            el.style.color = '#16a34a';
            if (tipo === 'ai') {
                el.textContent = `✓ OK — ${data.provider}/${data.model} respondeu em ${data.tempo_ms}ms (${data.tokens} tokens): "${data.resposta}"`;
            } else {
                el.textContent = `✓ OK — ${data.chunks} chunks encontrados para "${data.municipio}" em ${data.tempo_ms}ms`;
            }
        } else {
            el.style.color = '#dc2626';
            el.textContent = `✗ Erro: ${data.erro}`;
        }
    } catch(e) {
        el.style.color = '#dc2626';
        el.textContent = `✗ Erro: ${e.message}`;
    }
}
</script>
@endsection
