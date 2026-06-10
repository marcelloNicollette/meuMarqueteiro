[OPEN] Radar de Recursos detail opening with GET instead of POST

## Symptoms
- Ao abrir "Detalhes da oportunidade", a aplicação bate em `GET /mayor/mandato/federal-programs/detail`
- A rota aceita apenas `POST`
- Laravel retorna `MethodNotAllowedHttpException`

## Expected
- O detalhe deve ser carregado via `POST` assíncrono, como previsto no JavaScript da tela

## Hypotheses
1. O botão "Ver detalhes" está disparando submit/navegação por comportamento implícito no browser.
2. Algum link/form externo à função `openProgramDetail()` está apontando para a rota `detail`.
3. O `fetch()` falha antes de enviar e há fallback automático do browser para a URL da rota.
4. Existe JS quebrado na página antes do clique e o handler inline não executa.
5. O ambiente remoto está com Blade/JS em cache desatualizado e renderiza markup diferente do arquivo atual.

## Evidence Plan
- Inspecionar a Blade da tela e a rota `detail`
- Confirmar o controller e o método HTTP esperado
- Procurar qualquer uso da rota `mayor.mandato.federal-programs.detail` fora do `fetch`
- Reproduzir o comportamento via rota/listagem e revisar se há inconsistência estrutural no HTML/JS

## Status
- Sessão de debug iniciada

## Evidence Update
- A rota `detail` estava restrita a `POST`; isso gerava `405` quando a requisição chegava por `GET`.
- Após abrir a rota para `GET` e `POST`, o backend passou a falhar ao tentar espelhar um alerta legado para a camada canônica.
- O erro confirmado foi `Nenhuma fonte de recurso cadastrada para espelhar a oportunidade canônica.`, originado em `CanonicalResourceSyncService`.
- Isso indica ambiente sem catálogo `resource_sources` consistente para alguns registros legados do radar.

## Fix Applied
- A rota de detalhe passou a aceitar `GET` e `POST`.
- O payload do detalhe agora faz fallback para dados legados quando o espelhamento canônico falha.
- O modal oculta ações dependentes da camada canônica nesse modo legado para evitar novas quebras.
