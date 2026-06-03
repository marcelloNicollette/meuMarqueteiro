# Plano do Modulo Radar de Recursos

Data: 2026-05-18

## 1. Direcao do módulo

Nome alvo do produto:

- Radar de Recursos

Nome atual no codigo:

- Radar de Programas Federais

Mudanca central:

- o módulo deixa de ser um radar quase todo baseado em programas federais e passa a ser um radar multifuente de captação para municípios
- o escopo passa a cobrir 16 fontes, com captura por API oficial, scraping estruturado, monitor DOU/DOE e curadoria humana

## 2. Escopo consolidado a partir do documento

Objetivo do módulo:

- monitorar continuamente oportunidades de captação para o município
- ranquear por compatibilidade com o perfil do município
- separar compatibilidade de viabilidade de captação
- permitir expandir o card na mesma tela sem perder contexto
- alimentar Projetos e o assistente Meu Assistente

Fontes previstas no documento:

1. Transferegov.br
2. Portal da Transparencia Federal
3. FNDE
4. Funasa
5. Fundo Nacional de Saude
6. FNAS
7. Emendas Parlamentares
8. BNDES
9. Caixa Economica Federal
10. Banco do Brasil
11. FINEP
12. BID
13. Banco Mundial
14. NDB
15. Programas estaduais
16. Diario Oficial da Uniao

Recursos obrigatórios do módulo:

- cards em dois niveis: compacto e expandido
- score de compatibilidade explicavel
- viabilidade de captação separada da compatibilidade
- filtros por tema, tipo de recurso, prazo, porte, compatibilidade e viabilidade
- encerrados visiveis por 60 dias com notificacao de reabertura
- workflow de curadoria humana com fila de validacao
- integracao com módulo Projetos
- alertas de prazo para o município e para o Meu Assistente
- histórico pesquisavel

## 3. Estado atual do projeto

Ja existe:

- model e tabela unica em `federal_program_alerts`
- painel admin de sync por município
- tela do prefeito com vitrine de cards simples
- sync principal com Portal da Transparencia
- matching via Claude
- integracao basica com o chat via `Perguntar ao assistente`
- uso parcial do radar em alertas proativos do chat
- integracao indireta com Projetos por funding match

Arquivos centrais atuais:

- `app/Models/FederalProgramAlert.php`
- `database/migrations/2024_01_01_000014_create_federal_program_alerts_table.php`
- `app/Http/Controllers/Admin/FederalProgramsController.php`
- `app/Http/Controllers/Mayor/FederalProgramController.php`
- `app/Services/FederalPrograms/FederalProgramSyncService.php`
- `app/Services/FederalPrograms/ClaudeMatchingService.php`
- `app/Services/FederalPrograms/TransparenciaClient.php`
- `resources/views/admin/federal-programs/index.blade.php`
- `resources/views/mayor/federal-programs/index.blade.php`
- `app/Services/AI/ChatProactiveAlertService.php`

## 4. Gaps entre o documento e o codigo atual

### Gap de produto

- nome ainda esta espalhado como `Radar de Programas Federais`
- escopo atual cobre basicamente Portal da Transparencia e logica herdada de programas federais
- não existe catalogo real de 16 fontes operando

### Gap de dados

- a tabela atual não comporta bem curadoria, fila de validacao, notificacao de reabertura, histórico, viabilidade e explicabilidade completa
- não existe entidade separada para fonte monitorada
- não existe entidade separada para oportunidade canonica versus publicacao/ciclo
- não existe entidade de inscricao do usuario para reabertura

### Gap de pipeline

- não existe pipeline independente por grupo de fonte
- não existe monitor DOU/DOE com NLP
- não existe fila interna de curadoria humana
- não existe monitoramento de falha estrutural de scrapers

### Gap de UX funcional

- a tela do prefeito não tem card em dois niveis reais
- não existe `Salvar`, `Compartilhar`, `Notificar reabertura` e `Gerar Projeto` com contexto completo
- não existe contador de encerrados em exposicao de 60 dias

### Gap de scoring

- hoje existe so `match_score` e `match_reason`
- não existe viabilidade separada
- não existe trilha explicavel estruturada com fatores do score

### Gap de integracao

- o chat ainda fala em `radar federal`
- o módulo Projetos não recebe ainda um contexto formal do edital via CTA `Gerar Projeto`
- não existe vinculo direto entre oportunidade e projetos salvos compatíveis

## 5. Proposta de arquitetura alvo

### 5.1 Camada de catalogo de fontes

- criar um catalogo interno de fontes monitoradas
- campos minimos:
    - `key`
    - `name`
    - `resource_scope`
    - `capture_method`
    - `refresh_frequency`
    - `is_active`
    - `access_guide`
    - `index_fields`
    - `maintenance_notes`

### 5.2 Camada de oportunidades

- evoluir o modelo atual para representar oportunidade de recurso de forma mais ampla
- recomendacao:
    - renomear conceitualmente `FederalProgramAlert` para um módulo de oportunidades
    - ou criar novas tabelas e manter a antiga como legado temporario

Estrutura sugerida:

- `resource_sources`
- `resource_opportunities`
- `resource_opportunity_cycles`
- `resource_curation_queue`
- `resource_reopen_notifications`
- `resource_user_saves`
- `resource_shares`
- `resource_sync_runs`

### 5.3 Scoring

- compatibilidade:
    - porte populacional
    - indicadores do município por tema
    - UF/regiao
    - alinhamento com compromissos e projetos
- viabilidade:
    - CAUC
    - regularidade fiscal
    - histórico de inadimplencia
    - capacidade de contrapartida

### 5.4 Status padronizados

- `pending_review`
- `published`
- `closing_soon`
- `closed_recently`
- `archived`
- `monitoring`
- `reopened`
- `rejected`

Observacao:

- os status do radar precisam ser unificados antes de qualquer rename visual definitivo

## 6. TODO completo do módulo

### Fase 1 - Fundacao e rename do módulo

- renomear o produto de `Radar de Programas Federais` para `Radar de Recursos`
- mapear todas as rotas, controllers, views, labels e icones impactados
- fechar o vocabulario de status do módulo
- revisar a migration atual e decidir entre:
    - evolucao incremental da tabela atual
    - nova estrutura de dados com migracao de legado

Entregas:

- nomenclatura consolidada
- dicionario de status
- decisão de arquitetura de dados

### Fase 2 - Modelo de dados do Radar de Recursos

- criar tabelas para fontes, oportunidades, ciclos, curadoria, saves e notificacoes
- preservar compatibilidade de leitura com a tabela atual durante a transicao
- incluir campos para:
    - titulo curto e titulo oficial
    - fonte e orgao
    - tag tematica
    - tipo de recurso
    - porte estimado
    - prazo
    - compatibilidade
    - fatores do score
    - viabilidade
    - fatores da viabilidade
    - contrapartida
    - documentacao
    - link do edital
    - origem do registro
    - status de curadoria
    - data de encerramento
    - data limite de exposicao apos encerramento

Entregas:

- migrations
- models
- relacoes
- estrategia de migracao do legado

### Fase 3 - Catalogo das 16 fontes

- implementar catalogo interno das 16 fontes do documento
- registrar para cada uma:
    - metodo de captura
    - periodicidade
    - campos a indexar
    - observacoes operacionais
- ligar isso ao painel admin

Entregas:

- seed ou config de fontes
- painel admin de fontes
- orientacoes de captura persistidas

### Fase 4 - Pipelines de ingestao por grupo

- grupo A: APIs oficiais
    - Transferegov
    - Portal da Transparencia
- grupo B: scraping estruturado
    - FNDE
    - Funasa
    - FNS
    - FNAS
    - BNDES
    - Caixa
    - Banco do Brasil
    - FINEP
- grupo C: monitor DOU/DOE
    - DOU
    - programas estaduais
- grupo D: curadoria humana
    - BID
    - Banco Mundial
    - NDB

Entregas:

- um job por grupo
- logs por fonte
- retries e alertas de falha

### Fase 5 - Fila de curadoria humana

- criar fila interna para itens `pending_review`
- painel admin para:
    - validar
    - enriquecer
    - aprovar
    - rejeitar
    - corrigir tag e urgência
- impedir publicacao direta no radar para fontes que exigem curadoria

Entregas:

- painel interno de curadoria
- workflow de aprovacao
- auditoria basica

### Fase 6 - Scoring explicavel

- reescrever o calculo de compatibilidade com fatores estruturados
- armazenar:
    - score final
    - fatores positivos
    - fatores limitantes
    - explicacao curta para o card expandido
- incorporar dados do município, situacao e projetos relacionados

Entregas:

- service de scoring
- estrutura persistida de explicabilidade

### Fase 7 - Viabilidade de captação

- criar score e indicador separado de viabilidade
- cruzar:
    - situacao fiscal
    - CAUC
    - receita municipal
    - contrapartida exigida
    - histórico de execução
- exibir `alto`, `medio` ou `baixo` com motivo

Entregas:

- service de viabilidade
- campos persistidos
- exibicao no card expandido

### Fase 8 - Nova vitrine do prefeito

- refatorar a tela do prefeito para cards em dois niveis
- nivel compacto:
    - titulo
    - fonte
    - tag tematica
    - prazo
    - compatibilidade
    - porte
    - tipo de recurso
- nivel expandido:
    - porque e compativel
    - viabilidade
    - contrapartida
    - objeto detalhado
    - documentacao
    - projetos compatíveis
    - municípios de referencia
    - link do edital
    - reabertura

Entregas:

- nova view
- filtros
- estados vazios
- expansao inline

### Fase 9 - Acoes do card

- `Gerar Projeto`
- `Saber mais`
- `Notificar reabertura`
- `Compartilhar`
- `Salvar`

Entregas:

- saves por usuario
- notificacoes de reabertura
- compartilhamento interno
- pre-carga de contexto no módulo Projetos

### Fase 10 - Histórico e encerrados

- manter editais encerrados por 60 dias na vitrine
- depois mover para histórico pesquisavel
- suportar busca por:
    - palavra-chave
    - tema
    - orgao
    - fonte

Entregas:

- regra de exposicao por tempo
- arquivo pesquisavel
- histórico institucional

### Fase 11 - Integracao com Projetos

- CTA `Gerar Projeto` deve abrir Projetos com contexto pre-carregado
- vincular oportunidade com projeto salvo compatível
- exibir no card expandido projetos relacionados do município

Entregas:

- rota e payload de handoff
- integracao com `ProjectController`
- vinculo oportunidade-projeto

### Fase 12 - Integracao com Meu Assistente

- alertas proativos do chat devem usar o novo radar
- o assistente deve conseguir:
    - citar oportunidades relevantes
    - avisar prazos de 30 e 7 dias
    - sugerir proximo passo
- atualizar tags e linguagem do chat para `radar_recursos`

Entregas:

- ajuste em `ChatProactiveAlertService`
- notificacoes internas
- prompts atualizados

### Fase 13 - Painel admin do Radar de Recursos

- substituir o painel atual de sync por um painel do módulo completo
- visoes necessárias:
    - saude das fontes
    - fila de curadoria
    - oportunidades publicadas
    - encerradas recentes
    - falhas de captura
    - sincronismo por fonte e por município

Entregas:

- admin renovado
- indicadores operacionais
- filtros internos

### Fase 14 - Validacao do piloto

- garantir pelo menos 15 oportunidades elegiveis para município piloto
- validar entendimento do score pelo usuario
- validar alerta 30/7 dias
- validar conversão de oportunidade em projeto
- validar SLA de curadoria em ate 48 horas

Entregas:

- checklist de piloto
- roteiro de testes
- criterios de aceite

## 7. O que pode ser reaproveitado

Pode reaproveitar:

- estrutura basica de rotas admin e mayor
- tabela atual como base de migracao
- `TransparenciaClient`
- `ClaudeMatchingService` como embrião do novo scoring
- vitrine atual como base de refatoracao
- alertas proativos do chat
- ponte com Projetos via contexto de financiamento

Deve ser revisado ou substituido:

- nome de módulo e rotas atuais
- statuses atuais
- model unico `FederalProgramAlert`
- sync unico por município
- painel admin focado so em sync
- copy das telas falando apenas em programas federais

## 8. Ordem recomendada de implementacao

Ordem segura:

1. fundacao de dados e status
2. rename de produto e superficies
3. catalogo de fontes
4. pipelines por grupo
5. curadoria humana
6. scoring de compatibilidade
7. viabilidade
8. nova vitrine com cards em dois niveis
9. acoes do card
10. integracao com Projetos e chat
11. histórico, reabertura e piloto

## 9. Conclusao

O documento do módulo descreve uma evolucao de produto grande o suficiente para tratar o Radar de Recursos como um módulo novo sobre a base do radar federal existente. A recomendacao tecnica e fazer a implementacao por fases, preservando o que ja existe apenas como camada de transicao, e não tentar encaixar todo o novo escopo dentro do desenho atual sem reorganizar dados, status e pipeline.
