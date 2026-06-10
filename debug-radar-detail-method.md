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
