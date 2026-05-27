# Entrega Tecnica e Resumo Executivo - Radar de Recursos

## 1. Resumo Executivo

O módulo `Radar de Recursos` foi concluido como frente operacional completa de captação, monitoramento, qualificacao, curadoria e governanca de oportunidades para municípios dentro da plataforma.

No estado atual, o módulo entrega:

- catalogo operacional de fontes de recurso
- ingestao multi-fonte por APIs oficiais, scraping estruturado e diario oficial
- camada canonica de oportunidades, ciclos e fila de curadoria
- compatibilidade com camada legada durante a transicao
- leitura administrativa da operacao por fonte, grupo e município
- debug operacional por fonte com qualificados, rejeitados e motivos
- workflow humano de curadoria com fila, filtros, SLA e backlog
- acoes individuais e em lote para revisão, aprovacao, publicacao e rejeicao
- auditoria operacional completa das decisoes humanas
- exportacoes operacionais da fila, histórico e auditoria
- balanceamento da fila entre operadores
- atribuicao sugerida inteligente por carga e afinidade
- confirmacao em lote das sugestoes de atribuicao
- metas operacionais por operador
- visão executiva do time e políticas de distribuicao
- snapshots operacionais por e-mail

Do ponto de vista de produto, o módulo esta pronto para operacao no `admin` e para sustentacao do fluxo principal do radar.

Do ponto de vista tecnico, o ciclo principal foi fechado sem pendencia bloqueante para seguir ao proximo módulo da plataforma.

## 2. Objetivo do Modulo

O `Radar de Recursos` funciona como módulo de descoberta e governanca de oportunidades de captação para municípios, consolidando multiplas fontes, reduzindo ruido operacional e organizando a decisão humana quando a publicacao automatica não e suficiente.

O objetivo principal do módulo e:

- monitorar continuamente fontes de recurso relevantes para o município
- consolidar oportunidades em uma camada canonica reutilizavel
- qualificar sinais de relevancia antes da exposicao operacional
- manter observabilidade completa sobre coletas e falhas
- suportar curadoria humana com escala, rastreabilidade e governanca
- alimentar fluxos posteriores da plataforma com oportunidades consistentes

## 3. Escopo Entregue

### 3.1 Fundacao de dados e status

- consolidacao do vocabulario de status do radar
- camada de compatibilidade entre legado e modelo canonico
- estrategia de transicao sem ruptura do fluxo existente
- serializacao coerente para leitura administrativa e operacional

### 3.2 Camada canonica do módulo

- criacao de `resource_sources`
- criacao de `resource_opportunities`
- criacao de `resource_opportunity_cycles`
- criacao de `resource_curation_queue`
- criacao de `resource_user_saves`
- criacao de `resource_reopen_notifications`
- ampliacao estrutural para URLs longas nas tabelas legadas e canonicas

### 3.3 Catalogo de fontes e operacao administrativa

- catalogo operacional de fontes no admin
- resumo por grupo de pipeline
- indicadores de saude por fonte
- configuracao operacional de fontes no painel
- leitura de readiness e metadados operacionais

### 3.4 Pipelines por grupo

- grupo A estabilizado para APIs oficiais
- grupo B amadurecido com scraping estruturado nas fontes priorizadas
- grupo C operacional com `DOU` e `Programas Estaduais`
- grupo D consolidado como camada humana de curadoria

### 3.5 Grupo B - scraping estruturado

- calibracao progressiva das fontes priorizadas
- endurecimento de filtros por fonte com telemetria real
- reducao de ruido operacional em FNDE, Funasa, FNAS, Caixa e FINEP
- consolidacao de comportamento mais enxuto antes do avancar para o grupo seguinte

### 3.6 Grupo C - diario oficial

- endurecimento do `DOU` com eliminacao de paginas genericas do portal
- debug operacional com exibicao de qualificados e rejeitados
- piloto estadual por UF em `BA`
- segunda UF-piloto em `SP`
- tolerancia a falha por entrypoint
- consolidacao do resumo operacional do grupo no admin

### 3.7 Curadoria humana no admin

- fila de curadoria operacional
- filtros por status, fonte, município, operador, prioridade, score e SLA
- ordenacao por score, recencia, prioridade e vencimento
- acoes individuais por item
- acoes em lote
- auditoria de eventos humanos
- KPIs operacionais da curadoria
- excecoes criticas com acoes rápidas
- exportacao CSV e XLSX da fila e da auditoria

### 3.8 Coordenacao do time de curadoria

- bloco `Minha fila`
- atalhos operacionais por operador
- balanceamento da fila entre operadores
- sugestao inteligente de atribuicao
- comparacao por operador e afinidade operacional
- limites de carga e overflow operacional
- políticas de distribuicao
- visão executiva do time
- confirmacao em lote das sugestoes
- metas operacionais por operador

### 3.9 Sync, observabilidade e suporte operacional

- sync assincrono por job
- persistencia das execucoes
- polling no admin
- reconciliacao e reprocessamento
- observabilidade das coletas
- snapshots operacionais por e-mail
- configuracao SMTP via painel
- auditoria e rollback de configuracao operacional

## 4. Principais Entregas Tecnicas

### Backend

- controlador central em `app/Http/Controllers/Admin/FederalProgramsController.php`
- pipeline principal em `app/Services/FederalPrograms/FederalProgramSyncService.php`
- fetcher de diarios em `app/Services/FederalPrograms/DiaryMonitorRadarFetcher.php`
- pipeline de diario em `app/Services/FederalPrograms/DiaryMonitorRadarPipelineService.php`
- pipeline de scraping em `app/Services/FederalPrograms/StructuredScrapingRadarPipelineService.php`
- leitura consolidada em `app/Services/Radar/HybridRadarReadService.php`
- exports em `app/Services/Radar/RadarSyncExportService.php`
- snapshots em `app/Services/Radar/RadarSyncSnapshotService.php`

### Frontend

- painel admin em `resources/views/admin/federal-programs/index.blade.php`
- vitrine do prefeito em `resources/views/mayor/federal-programs/index.blade.php`

### Persistencia e dados

- fundacao da camada canonica em `database/migrations/2026_05_19_000001_create_resource_opportunities_table.php`
- ciclos em `database/migrations/2026_05_19_000002_create_resource_opportunity_cycles_table.php`
- fila de curadoria em `database/migrations/2026_05_19_000003_create_resource_curation_queue_table.php`
- saves e reabertura em `database/migrations/2026_05_19_000004_create_resource_user_saves_table.php` e `database/migrations/2026_05_19_000005_create_resource_reopen_notifications_table.php`
- ampliacao de URLs longas em `database/migrations/2026_05_20_000001_expand_long_urls_for_radar_resources.php`
- seed operacional de fontes em `database/seeders/ResourceSourcesSeeder.php`

### Rotas e operacao

- rotas do módulo em `routes/web.php`
- sync por comando em `app/Console/Commands/SyncFederalPrograms.php`
- execução assincrona em `app/Jobs/SyncFederalProgramsJob.php`

## 5. Fluxos Principais Entregues

### Fluxo 1: coleta multi-fonte

1. o sistema executa as fontes ativas por grupo
2. cada fonte captura entradas por API, scraping ou diario
3. os candidatos passam por filtros operacionais por fonte
4. o módulo registra volume bruto, filtrado e qualificado
5. os itens elegiveis seguem para a camada canonica e para a fila quando necessário

### Fluxo 2: leitura operacional no admin

1. o operador acessa o painel do radar no `admin`
2. consulta saude das fontes, histórico de execucoes e resumos por grupo
3. abre debug operacional para entender qualificados e rejeitados
4. identifica ruido, falhas de entrypoint ou comportamento atipico
5. ajusta configuracao operacional da fonte quando necessário

### Fluxo 3: curadoria humana

1. itens que exigem revisão entram na `resource_curation_queue`
2. o time filtra a fila por score, SLA, município, fonte ou responsável
3. o operador assume ou recebe itens
4. a revisão segue por `start_review`, `approve`, `publish` ou `reject`
5. a auditoria registra o evento, contexto e responsável

### Fluxo 4: ganho de escala operacional

1. o time usa selecao multipla na fila
2. aplica acoes em lote como atribuicao, repriorizacao, aprovacao ou publicacao
3. consulta sugestoes inteligentes para itens sem responsável
4. confirma sugestoes individualmente ou em lote
5. reduz gargalo operacional sem perder rastreabilidade

### Fluxo 5: coordenacao do time

1. a coordinacao acompanha `Minha fila`, backlog e SLA
2. usa balanceamento, comparacao por operador e overflow
3. observa políticas de distribuicao e visão executiva
4. acompanha metas por operador
5. redistribui carga conforme pressao da operacao

## 6. Funcionalidades do Modulo

### 6.1 Coleta e ingestao

- sync manual e assincrono
- consolidacao por grupo de fonte
- retries e observabilidade
- histórico por execução

### 6.2 Catalogo e configuracao

- visualizacao das fontes do radar
- leitura do metodo de captura
- configuracao operacional no admin
- suporte a metadados por fonte

### 6.3 Debug operacional

- lista de qualificados por fonte
- lista de rejeitados por motivo
- exibicao de `entrypoint_unavailable`
- telemetria de entrypoints visitados e aproveitados

### 6.4 Fila de curadoria

- listagem paginada
- filtros combinados
- score minimo
- bucket de SLA
- prioridade e ordenacao

### 6.5 Acoes operacionais

- atribuir responsável
- repriorizar
- iniciar revisão
- aprovar
- publicar
- rejeitar
- aplicar acoes em lote

### 6.6 Auditoria e KPI

- histórico de eventos humanos
- filtro por periodo, evento, operador, fonte e município
- cobertura de atribuicao
- revisadas, publicadas e rejeitadas
- tempo medio ate decisão
- tempo medio ate publicacao

### 6.7 Excecoes e governanca

- itens sem responsável com SLA vencido
- revisão parada
- aprovados aguardando publicacao
- alta prioridade com score baixo
- acoes rápidas diretamente do bloco de excecoes

### 6.8 Gestao do time

- minha fila
- balanceamento
- sugestao inteligente
- comparacao por operador
- limites de carga
- overflow operacional
- políticas de distribuicao
- visão executiva
- metas operacionais por operador

### 6.9 Exportacoes e snapshots

- export CSV da fila
- export XLSX da fila
- export CSV da auditoria
- export XLSX da auditoria
- snapshots operacionais por e-mail

## 7. Como Utilizar o Modulo por Completo

### 7.1 Acompanhar a operacao geral

1. acessar o `admin` do radar
2. revisar os cards de saude da operacao
3. verificar o resumo por grupo e o histórico das ultimas execucoes
4. identificar fontes com falha, ruido ou baixo aproveitamento

### 7.2 Validar uma fonte ou grupo

1. localizar a fonte no painel
2. abrir o bloco de debug operacional
3. analisar qualificados, rejeitados e motivos
4. verificar se ha entrypoints indisponiveis
5. ajustar configuracao da fonte se necessário

### 7.3 Operar a fila de curadoria

1. acessar a secao `curation queue`
2. filtrar por fonte, município, operador, prioridade, score ou SLA
3. ordenar pela visão desejada do turno
4. assumir ou atribuir itens
5. iniciar revisão e concluir com aprovacao, publicacao ou rejeicao

### 7.4 Trabalhar com acoes em lote

1. selecionar varios itens da fila
2. escolher a acao em lote
3. definir responsável, prioridade ou nota quando necessário
4. aplicar a acao
5. acompanhar o resultado na auditoria

### 7.5 Utilizar sugestoes inteligentes

1. abrir o bloco de sugestao inteligente
2. avaliar o responsável sugerido e a justificativa
3. aplicar individualmente quando fizer sentido
4. ou selecionar varias sugestoes
5. usar `Confirmar selecionadas` para confirmar em lote

### 7.6 Coordenar o time

1. consultar `Minha fila` para leitura individual
2. abrir `balanceamento da fila` para redistribuir sem dono
3. consultar `comparacao por operador` para afinidade e produtividade
4. observar `limites de carga e overflow`
5. usar `políticas de distribuicao`, `visão executiva` e `metas por operador` para decisão gerencial

### 7.7 Auditar e reportar

1. abrir o bloco de auditoria da curadoria
2. filtrar por periodo, operador, fonte, município e tipo de evento
3. revisar os eventos mais recentes
4. exportar auditoria ou fila quando necessário
5. usar os snapshots por e-mail para acompanhamento recorrente

## 8. Validacoes Realizadas

Durante a entrega foram validados tecnicamente:

- fechamento progressivo do grupo B com endurecimento das fontes raspadas
- saneamento do `DOU` contra paginas genericas do portal
- piloto estadual da `BA` com qualificados reais
- piloto estadual de `SP` com recalibracao de entrypoints
- tolerancia a falha por entrypoint em diarios
- correcao de persistencia para URLs longas
- correcao do schema real de município com `state_code`
- ajuste de paginacao e layout do grupo de curadoria
- auditoria, KPIs, exports e acoes rápidas no admin
- confirmacao em lote das sugestoes e metas por operador
- diagnostics limpos nos arquivos editados nas ultimas iteracoes

Tambem foi validado no fluxo operacional:

- uso real do painel admin sem quebra estrutural
- leitura coerente dos blocos novos de governanca
- estabilidade da fila humana e dos recortes operacionais

## 9. Checklist Final do Que Ficou Pronto

- [x] base de status do radar consolidada
- [x] camada canonica criada
- [x] compatibilidade com legado preservada
- [x] catalogo operacional de fontes
- [x] resumo por grupo no admin
- [x] grupo A operacional
- [x] grupo B operacional e calibrado
- [x] `DOU` saneado operacionalmente
- [x] `Programas Estaduais` com piloto `BA`
- [x] `Programas Estaduais` com piloto `SP`
- [x] tolerancia a falha por entrypoint
- [x] debug operacional por diario
- [x] persistencia com URLs longas corrigida
- [x] sync assincrono com job e polling
- [x] logs e observabilidade da execução
- [x] reconciliacao e reprocessamento
- [x] snapshots por e-mail
- [x] configuracao SMTP operacional
- [x] fila de curadoria humana
- [x] filtros e ordenacoes da fila
- [x] acoes individuais
- [x] acoes em lote
- [x] backlog, score minimo e SLA
- [x] auditoria de curadoria
- [x] KPIs operacionais
- [x] excecoes criticas e acoes rápidas
- [x] export CSV e XLSX da fila
- [x] export CSV e XLSX da auditoria
- [x] minha fila por operador
- [x] balanceamento da fila
- [x] atribuicao sugerida inteligente
- [x] comparacao por operador e afinidade
- [x] limites de carga e overflow
- [x] políticas de distribuicao
- [x] visão executiva do time
- [x] confirmacao em lote das sugestoes
- [x] metas operacionais por operador

## 10. Pendencias Opcionais Futuras

- abrir novas UFs em `Programas Estaduais`
- transformar metas operacionais em política configuravel
- adicionar visão semanal da operacao
- ampliar automacoes de confirmacao das sugestoes
- adicionar testes automatizados mais focados para a fila humana e seus workflows

## 11. Status Final

### Status do módulo

- concluido no codigo
- operacional no `admin`
- validado no fluxo principal
- pronto para handoff ao proximo módulo

### Pendencia bloqueante

- nenhuma pendencia bloqueante identificada nesta etapa

## 12. Recomendacao de Encerramento

Para fins de projeto e continuidade da plataforma, o módulo `Radar de Recursos` pode ser considerado finalizado no seu escopo principal.

Recomendacao objetiva:

- encerrar este módulo como entregue
- registrar apenas as pendencias futuras como evolucoes opcionais
- seguir para o proximo bloco do plano, indicado pelo item `5 Resolve ai`

## 13. Referencias

- plano original do módulo: `Plano-Modulo-Radar-de-Recursos.md`
- fechamento da fase 1: `Radar-de-Recursos-Fase-1-Status-e-Dados.md`
- fechamento da fase 2: `Radar-de-Recursos-Fase-2-Arquitetura.md`
- controlador principal do admin: `app/Http/Controllers/Admin/FederalProgramsController.php`
- painel admin do radar: `resources/views/admin/federal-programs/index.blade.php`
- seeder operacional das fontes: `database/seeders/ResourceSourcesSeeder.php`
