@extends('layouts.mayor')

@section('title', 'Configurar Eixos do Mandato')
@section('topbar-title', 'Mandato · Eixos Temáticos')

@push('styles')
<style>
.eixos-cfg-wrap { padding:1.75rem 2rem; max-width:1080px; display:flex; flex-direction:column; gap:1.5rem; }

.cfg-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.cfg-header h1 { font-family:'Lora',serif; font-size:1.35rem; color:var(--ink); margin:0; }
.cfg-header p  { font-size:.82rem; color:var(--ink-muted); margin:.2rem 0 0; }

/* Eixo card */
.axis-card {
    background:var(--surface); border:1px solid var(--border);
    border-radius:10px; overflow:hidden;
}
.axis-card-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:.85rem 1.1rem; gap:.75rem;
    border-bottom:1px solid var(--border);
}
.axis-card-name { font-weight:600; font-size:.95rem; color:var(--ink); display:flex; align-items:center; gap:.5rem; }
.axis-card-actions { display:flex; gap:.5rem; align-items:center; }
.axis-card-body { padding:.85rem 1.1rem; }

/* New axis form */
.new-axis-form {
    background:var(--surface); border:2px dashed var(--border);
    border-radius:10px; padding:1.25rem;
}
.new-axis-form h3 { font-size:.85rem; font-weight:600; color:var(--ink); margin:0 0 .9rem; }
.form-row { display:grid; grid-template-columns:1fr 80px 140px; gap:.6rem; align-items:start; }
@media(max-width:640px){ .form-row{ grid-template-columns:1fr; } }

/* Default axes */
.defaults-info { background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:1rem 1.25rem; }
.defaults-info p { font-size:.82rem; color:var(--ink-muted); margin:0 0 .75rem; }
.defaults-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:.5rem; }
@media(max-width:640px){ .defaults-grid{ grid-template-columns:1fr 1fr; } }
.default-tag { background:var(--bg); border:1px solid var(--border); border-radius:6px; padding:.35rem .6rem; font-size:.78rem; color:var(--ink-soft); }
</style>
@endpush

@section('content')
<div class="eixos-cfg-wrap">

    <div class="cfg-header">
        <div>
            <h1>Eixos Temáticos</h1>
            <p>Configure os eixos e promessas do Plano de Governo de {{ $municipality->name }}</p>
        </div>
        <a href="{{ route('mayor.mandato.painel') }}" class="btn-secondary" style="font-size:.8rem">
            ← Voltar ao painel
        </a>
    </div>

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    {{-- Eixos existentes --}}
    @if($axes->isNotEmpty())
        @foreach($axes as $axis)
        <div class="axis-card">
            <div class="axis-card-header">
                <div class="axis-card-name">
                    @if($axis->icon) <span>{{ $axis->icon }}</span> @endif
                    {{ $axis->name }}
                    <span style="font-size:.72rem;font-weight:400;color:var(--ink-muted)">({{ $axis->promises_count }} promessas)</span>
                </div>
                <div class="axis-card-actions">
                    <a href="{{ route('mayor.mandato.eixo', $axis->id) }}" class="btn-secondary" style="font-size:.75rem">
                        Ver promessas
                    </a>
                    <form method="POST" action="{{ route('mayor.mandato.eixo.destroy', $axis->id) }}"
                          onsubmit="return confirm('Remover eixo e todas as promessas vinculadas?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger" style="font-size:.75rem">Remover</button>
                    </form>
                </div>
            </div>
            {{-- Edição inline --}}
            <div class="axis-card-body">
                <form method="POST" action="{{ route('mayor.mandato.eixo.update', $axis->id) }}">
                    @csrf @method('PUT')
                    <div class="form-row">
                        <div>
                            <label style="font-size:.75rem;color:var(--ink-muted)">Nome do eixo</label>
                            <input type="text" name="name" value="{{ $axis->name }}" required style="width:100%;margin-top:.25rem">
                        </div>
                        <div>
                            <label style="font-size:.75rem;color:var(--ink-muted)">Ícone</label>
                            <input type="text" name="icon" value="{{ $axis->icon }}" maxlength="4" placeholder="🏥" style="width:100%;margin-top:.25rem;text-align:center">
                        </div>
                        <div style="display:flex;align-items:flex-end">
                            <button type="submit" class="btn-primary" style="font-size:.78rem;width:100%">Salvar</button>
                        </div>
                    </div>
                    <div style="margin-top:.5rem">
                        <label style="font-size:.75rem;color:var(--ink-muted)">Descrição (opcional)</label>
                        <input type="text" name="description" value="{{ $axis->description }}" placeholder="ex: UBS/UPA · Saúde da família · Saúde mental" style="width:100%;margin-top:.25rem">
                    </div>
                </form>
            </div>
        </div>
        @endforeach
    @endif

    {{-- Formulário novo eixo --}}
    <div class="new-axis-form">
        <h3>+ Adicionar novo eixo temático</h3>
        <form method="POST" action="{{ route('mayor.mandato.eixo.store') }}">
            @csrf
            <div class="form-row">
                <div>
                    <label style="font-size:.75rem;color:var(--ink-muted)">Nome do eixo *</label>
                    <input type="text" name="name" required placeholder="ex: Saúde" style="width:100%;margin-top:.25rem">
                </div>
                <div>
                    <label style="font-size:.75rem;color:var(--ink-muted)">Ícone</label>
                    <input type="text" name="icon" maxlength="4" placeholder="🏥" style="width:100%;margin-top:.25rem;text-align:center">
                </div>
                <div style="display:flex;align-items:flex-end">
                    <button type="submit" class="btn-primary" style="font-size:.78rem;width:100%">Criar eixo</button>
                </div>
            </div>
            <div style="margin-top:.5rem">
                <label style="font-size:.75rem;color:var(--ink-muted)">Descrição (subáreas)</label>
                <input type="text" name="description" placeholder="ex: UBS/UPA · Saúde da família · Saúde mental · Vigilância" style="width:100%;margin-top:.25rem">
            </div>
        </form>
    </div>

    {{-- Eixos padrão sugeridos --}}
    @if($axes->isEmpty())
    <div class="defaults-info">
        <p>Sugestão: os 9 eixos base do Gerenciador de Mandato (editáveis conforme seu Plano de Governo):</p>
        <div class="defaults-grid">
            @foreach([
                ['🏥','Saúde'],['🎓','Educação'],['🚌','Mobilidade e Infraestrutura'],
                ['🌿','Meio Ambiente e Saneamento'],['💼','Desenvolvimento Econômico'],
                ['🤝','Assistência Social e Direitos'],['🛡️','Segurança Pública'],
                ['💻','Gestão, Tecnologia e Transparência'],['🎭','Cultura, Esporte e Lazer'],
            ] as $e)
                <div class="default-tag">{{ $e[0] }} {{ $e[1] }}</div>
            @endforeach
        </div>
        <div style="margin-top:.9rem">
            <form method="POST" action="{{ route('mayor.mandato.eixos.seed') }}">
                @csrf
                <button type="submit" class="btn-primary" style="font-size:.82rem">
                    Importar os 9 eixos padrão
                </button>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
