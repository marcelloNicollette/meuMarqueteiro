@extends('layouts.admin')
@section('title', 'Novo Município')
@section('content')
    <div style="padding:2rem;max-width:760px">
        <div style="margin-bottom:1.5rem">
            <a href="{{ route('admin.municipalities.index') }}" style="font-size:.85rem;color:#6b7280;text-decoration:none">←
                Municípios</a>
            <h1 style="font-size:1.4rem;font-weight:700;margin-top:.5rem">Novo Município</h1>
            <p style="font-size:.85rem;color:#6b7280;margin-top:.3rem">Digite o nome do município e clique em "Buscar" para
                preencher automaticamente.</p>
        </div>

        @if ($errors->any())
            <div
                style="background:#fee2e2;border:1px solid #fca5a5;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#991b1b;font-size:.88rem">
                @foreach ($errors->all() as $e)
                    <div>{{ $e }}</div>
                @endforeach
            </div>
        @endif

        {{-- Busca IBGE --}}
        <div style="background:#fffbeb;border:1px solid #fde68a;padding:1.25rem;border-radius:12px;margin-bottom:1.5rem">
            <h3 style="font-size:.88rem;font-weight:600;margin-bottom:.75rem;color:#92400e">🔍 Buscar município pelo nome
            </h3>
            <div style="display:flex;gap:.75rem;align-items:flex-end">
                <div style="flex:1">
                    <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.3rem;color:#374151">Nome do
                        município</label>
                    <input id="search_input" type="text" placeholder="Ex: Serrinha, Feira de Santana..."
                        style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                </div>
                <div>
                    <label
                        style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.3rem;color:#374151">UF</label>
                    <select id="search_uf"
                        style="padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                        <option value="">Todas</option>
                        @foreach (['AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA', 'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO'] as $uf)
                            <option value="{{ $uf }}">{{ $uf }}</option>
                        @endforeach
                    </select>
                </div>
                <button onclick="buscarMunicipio()"
                    style="padding:.6rem 1.5rem;background:#0f1117;color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">Buscar</button>
            </div>
            <div id="search_results" style="margin-top:.75rem;display:none"></div>
        </div>

        <form method="POST" action="{{ route('admin.municipalities.store') }}"
            style="background:#fff;padding:1.5rem;border-radius:12px;border:1px solid #e5e7eb">
            @csrf

            <h3
                style="font-size:.95rem;font-weight:600;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid #f3f4f6">
                Dados do Município</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Nome *</label>
                    <input id="f_name" name="name" value="{{ old('name') }}" required
                        style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Código IBGE *</label>
                    <input id="f_ibge" name="ibge_code" value="{{ old('ibge_code') }}" required
                        style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Estado *</label>
                    <input id="f_state" name="state" value="{{ old('state') }}" required
                        style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">UF *</label>
                    <input id="f_uf" name="state_code" value="{{ old('state_code') }}" maxlength="2" required
                        style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Região</label>
                    <input id="f_region" name="region" value="{{ old('region') }}"
                        style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">População
                        estimada</label>
                    <input id="f_population" name="population" type="number" value="{{ old('population') }}"
                        style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">IDHM</label>
                    <input id="f_idhm" name="idhm" type="number" step="0.001" value="{{ old('idhm') }}"
                        style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Plano *</label>
                    <select name="subscription_tier" required
                        style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem">
                        <option value="">Selecione...</option>
                        <option value="essencial" {{ old('subscription_tier') === 'essencial' ? 'selected' : '' }}>
                            Essencial</option>
                        <option value="estrategico" {{ old('subscription_tier') === 'estrategico' ? 'selected' : '' }}>
                            Estratégico</option>
                        <option value="parceiro" {{ old('subscription_tier') === 'parceiro' ? 'selected' : '' }}>
                            Parceiro</option>
                    </select>
                </div>
            </div>

            <h3
                style="font-size:.95rem;font-weight:600;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid #f3f4f6">
                Dados do Prefeito</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem">
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Nome completo
                        *</label>
                    <input name="mayor_name" value="{{ old('mayor_name') }}" required
                        style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">E-mail *</label>
                    <input name="mayor_email" type="email" value="{{ old('mayor_email') }}" required
                        style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                </div>
                <div style="grid-column:span 2">
                    <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:.3rem">Senha de acesso
                        *</label>
                    <input name="mayor_password" type="password" required placeholder="Mínimo 8 caracteres"
                        style="width:100%;padding:.6rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;box-sizing:border-box">
                </div>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center">
                <div style="font-size:.78rem;color:#9ca3af">* campos obrigatórios — após criar, inicie o onboarding</div>
                <div style="display:flex;gap:1rem">
                    <a href="{{ route('admin.municipalities.index') }}"
                        style="padding:.65rem 1.2rem;border:1px solid #d1d5db;border-radius:8px;font-size:.88rem;color:#374151;text-decoration:none">Cancelar</a>
                    <button type="submit"
                        style="padding:.65rem 1.5rem;background:var(--gold);color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer">✓
                        Criar Município</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        const UF_ESTADOS = {
            'AC': 'Acre',
            'AL': 'Alagoas',
            'AM': 'Amazonas',
            'AP': 'Amapá',
            'BA': 'Bahia',
            'CE': 'Ceará',
            'DF': 'Distrito Federal',
            'ES': 'Espírito Santo',
            'GO': 'Goiás',
            'MA': 'Maranhão',
            'MG': 'Minas Gerais',
            'MS': 'Mato Grosso do Sul',
            'MT': 'Mato Grosso',
            'PA': 'Pará',
            'PB': 'Paraíba',
            'PE': 'Pernambuco',
            'PI': 'Piauí',
            'PR': 'Paraná',
            'RJ': 'Rio de Janeiro',
            'RN': 'Rio Grande do Norte',
            'RO': 'Rondônia',
            'RR': 'Roraima',
            'RS': 'Rio Grande do Sul',
            'SC': 'Santa Catarina',
            'SE': 'Sergipe',
            'SP': 'São Paulo',
            'TO': 'Tocantins'
        };

        const REGIOES_UF = {
            'AC': 'Norte',
            'AL': 'Nordeste',
            'AM': 'Norte',
            'AP': 'Norte',
            'BA': 'Nordeste',
            'CE': 'Nordeste',
            'DF': 'Centro-Oeste',
            'ES': 'Sudeste',
            'GO': 'Centro-Oeste',
            'MA': 'Nordeste',
            'MG': 'Sudeste',
            'MS': 'Centro-Oeste',
            'MT': 'Centro-Oeste',
            'PA': 'Norte',
            'PB': 'Nordeste',
            'PE': 'Nordeste',
            'PI': 'Nordeste',
            'PR': 'Sul',
            'RJ': 'Sudeste',
            'RN': 'Nordeste',
            'RO': 'Norte',
            'RR': 'Norte',
            'RS': 'Sul',
            'SC': 'Sul',
            'SE': 'Nordeste',
            'SP': 'Sudeste',
            'TO': 'Norte'
        };

        async function buscarMunicipio() {
            const nome = document.getElementById('search_input').value.trim();
            const uf = document.getElementById('search_uf').value;
            const el = document.getElementById('search_results');

            if (nome.length < 3) {
                el.style.display = 'block';
                el.innerHTML = '<p style="color:#92400e;font-size:.82rem">Digite pelo menos 3 letras.</p>';
                return;
            }

            el.style.display = 'block';
            el.innerHTML = '<p style="color:#6b7280;font-size:.82rem">Buscando...</p>';

            try {
                // Busca direta na API IBGE
                let url = `https://servicodados.ibge.gov.br/api/v1/localidades/municipios?orderBy=nome`;
                const res = await fetch(url);
                const data = await res.json();

                let resultados = data.filter(m =>
                    m.nome.toLowerCase().includes(nome.toLowerCase()) &&
                    (uf === '' || m.microrregiao.mesorregiao.UF.sigla === uf)
                ).slice(0, 8);

                if (resultados.length === 0) {
                    el.innerHTML = '<p style="color:#92400e;font-size:.82rem">Nenhum município encontrado.</p>';
                    return;
                }

                el.innerHTML = '<div style="display:flex;flex-wrap:wrap;gap:.4rem">' +
                    resultados.map(m => `
                <button type="button" onclick="preencherMunicipio(${m.id}, '${m.nome.replace(/'/g,"\\'")}', '${m.microrregiao.mesorregiao.UF.sigla}', '${m.microrregiao.mesorregiao.UF.nome.replace(/'/g,"\\'")}', '${REGIOES_UF[m.microrregiao.mesorregiao.UF.sigla] || ''}')"
                    style="padding:.3rem .9rem;background:#fff;border:1px solid #d1d5db;border-radius:99px;font-size:.8rem;cursor:pointer;color:#374151">
                    ${m.nome} — ${m.microrregiao.mesorregiao.UF.sigla}
                </button>`).join('') + '</div>';
            } catch (e) {
                el.innerHTML = '<p style="color:#991b1b;font-size:.82rem">Erro ao buscar. Preencha manualmente.</p>';
            }
        }

        function preencherMunicipio(ibge, nome, uf, estado, regiao) {
            document.getElementById('f_ibge').value = ibge;
            document.getElementById('f_name').value = nome;
            document.getElementById('f_uf').value = uf;
            document.getElementById('f_state').value = estado;
            document.getElementById('f_region').value = regiao;
            document.getElementById('search_results').innerHTML =
                `<p style="color:#065f46;font-size:.82rem">✓ ${nome} (${uf}) selecionado — código IBGE: ${ibge}</p>`;

            // Buscar população automaticamente
            fetch(`https://servicodados.ibge.gov.br/api/v1/pesquisas/indicadores/29171/resultados/${ibge}`)
                .then(r => r.json())
                .then(d => {
                    const serie = d[0]?.res?.[0]?.res ?? {};
                    const anos = Object.keys(serie).sort().reverse();
                    if (anos.length > 0) {
                        document.getElementById('f_population').value = serie[anos[0]];
                    }
                }).catch(() => {});
        }

        // Buscar ao pressionar Enter
        document.getElementById('search_input').addEventListener('keypress', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarMunicipio();
            }
        });
    </script>
@endsection
