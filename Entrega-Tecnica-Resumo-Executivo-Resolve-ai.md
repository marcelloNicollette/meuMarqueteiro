# Entrega Tecnica e Resumo Executivo - Resolve ai

## 1. Resumo Executivo

O módulo `Resolve ai` foi concluido como frente operacional completa para registrar, encaminhar, acompanhar, cobrar, concluir e analisar demandas territoriais da gestao municipal dentro da plataforma.

No estado atual, o módulo entrega:

- registro rapido de demandas por texto e voz assistida
- definicao de localidade, endereco, secretaria responsável, prioridade e prazo
- workflow operacional com status claros e histórico imutavel
- comprovante de conclusao, confirmacao do criador e reabertura justificada
- painel do prefeito com backlog, filtros, criticidade e leitura executiva
- painel proprio do secretario e do assessor com visibilidade restrita por pasta
- configuracoes operacionais por município
- onboarding de secretarias, contatos e localidades
- notificacoes operacionais e cobrancas automatizadas
- memoria territorial por bairro, tema e secretaria
- indicadores comparativos de desempenho por secretaria
- leitura evolutiva de reincidencia territorial com janela configuravel por município
- integracao com `Comunicação`
- integracao com `Meu Marqueteiro`

Do ponto de vista de produto, o módulo esta pronto para operacao real no fluxo do prefeito, gabinete e secretarias.

Do ponto de vista tecnico, o ciclo principal foi fechado sem pendencia bloqueante para continuidade da plataforma.

## 2. Objetivo do Modulo

O `Resolve ai` funciona como módulo de operacao territorial e acompanhamento de demandas da gestao municipal.

O objetivo principal do módulo e:

- transformar pedidos e problemas de campo em fluxo rastreavel
- dar clareza de responsabilidade por secretaria
- organizar prazo, prioridade e cobranca operacional
- manter memoria do territorio e dos temas reincidentes
- permitir leitura executiva do que esta sendo resolvido, onde atrasa e onde melhora
- converter entregas concluidas em insumo de comunicação e narrativa política

## 3. Escopo Entregue

### 3.1 Fundacao de dados e workflow

- evolucao estrutural da tabela `demands`
- inclusao de `due_at` e carimbos operacionais
- suporte a comprovante de conclusao
- criacao de `demand_events`
- criacao de `demand_notifications`
- padronizacao do ciclo:
    - `registered`
    - `in_progress`
    - `overdue`
    - `awaiting_confirmation`
    - `completed`
    - `reopened`

### 3.2 Operacao da demanda

- registro por texto
- captura por voz assistida no painel
- sugestao de localidade
- atribuicao de secretaria responsável
- prazo automatico por prioridade
- acuse de recebimento
- atualizacao de andamento
- marcacao de conclusao
- confirmacao do criador
- reabertura com justificativa

### 3.3 Rastreabilidade e notificacao

- timeline imutavel por demanda
- log de notificacoes disparadas
- notificacao de registro
- notificacao de conclusao aguardando confirmacao
- notificacao de conclusao confirmada
- notificacao de reabertura
- disparo por canais ativos do município

### 3.4 Cobranca operacional automatizada

- alerta de prazo configuravel
- follow-up por inatividade
- repeticao automatica para demandas atrasadas
- atualizacao de eventos automaticos no histórico
- leitura da regua no painel e no onboarding

### 3.5 Perfis e visibilidade

- perfil `mayor` com visão ampla do município
- perfil `secretary` com fila propria da pasta
- perfil `advisor` com fila propria da pasta
- vinculo de usuario a `contact_area`
- permissao de registro configuravel
- redirecionamento por perfil no login

### 3.6 Base operacional do município

- onboarding de regras do `Resolve ai`
- cadastro e ampliacao de `contact_areas`
- contatos operacionais por secretaria
- e-mail de notificacao e backup por pasta
- cadastro de localidades do município
- prontidao operacional do módulo no admin

### 3.7 Memoria territorial e governanca comparativa

- hotspots territoriais
- temas recorrentes inferidos automaticamente do texto
- histórico territorial por secretaria
- score comparativo por pasta
- taxa de resolucao
- taxa de atraso
- resolucao no prazo
- tempo medio de fechamento
- comparacao entre janela recente e janela anterior
- leitura de reincidencia em alta
- leitura de reincidencia em queda
- leitura de melhora ou piora de execução no territorio
- janela comparativa configuravel por município

### 3.8 Integracoes estrategicas

- geração de rascunho em `Comunicação` a partir de demanda concluida
- abertura de conversa de narrativa política no `Meu Marqueteiro`
- abertura de conversa de cobranca e acompanhamento no `Meu Marqueteiro`
- metadata `origin_module = resolve_ai` em conteudo e conversas

## 4. Principais Entregas Tecnicas

### Backend

- controlador central em `app/Http/Controllers/Mayor/DemandController.php`
- regras operacionais em `app/Services/ResolveAi/ResolveAiSettingsService.php`
- notificacoes em `app/Services/ResolveAi/ResolveAiNotificationService.php`
- scheduler operacional em `app/Console/Commands/DispatchResolveAiAlerts.php`
- onboarding administrativo em `app/Http/Controllers/Admin/OnboardingController.php`
- gestao de localidades em `app/Http/Controllers/Admin/MunicipalityLocalityController.php`

### Frontend

- painel principal em `resources/views/mayor/demands/index.blade.php`
- detalhe operacional da demanda em `resources/views/mayor/demands/show.blade.php`
- onboarding do módulo em `resources/views/admin/municipalities/onboarding.blade.php`
- gestao administrativa de localidades em `resources/views/admin/municipalities/localities.blade.php`

### Persistencia e dados

- ampliacao da base em `database/migrations/2026_05_25_000001_upgrade_demands_for_resolve_ai.php`
- notificacoes em `database/migrations/2026_05_25_000002_create_demand_notifications_table.php`
- perfis e secretaria em `database/migrations/2026_05_25_000003_add_secretariat_fields_to_users_table.php`
- localidades em `database/migrations/2026_05_25_000004_expand_contact_areas_and_create_localities.php`

### Rotas e operacao

- rotas do módulo em `routes/web.php`
- agendamento da regua automatica via scheduler existente

## 5. Fluxos Principais Entregues

### Fluxo 1: registrar e encaminhar uma demanda

1. o usuario registra a demanda por texto ou voz
2. informa localidade, endereco, secretaria e prioridade
3. o sistema define prazo automatico
4. a demanda entra no fluxo como `registered`
5. a secretaria responsável passa a acompanhar a execução

### Fluxo 2: acompanhar e concluir a execução

1. a secretaria acusa recebimento ou atualiza andamento
2. o histórico registra eventos e comentarios
3. o responsável conclui a entrega com nota e comprovante quando necessário
4. a demanda passa a `awaiting_confirmation`
5. o criador confirma ou reabre com justificativa

### Fluxo 3: cobrar demandas abertas e atrasadas

1. o scheduler verifica prazos e inatividade
2. demandas sem andamento entram em follow-up automatico
3. demandas atrasadas entram em repeticao de cobranca
4. notificacoes e eventos automáticos ficam registrados
5. o painel exibe a pressao operacional atual

### Fluxo 4: operar por secretaria

1. secretario ou assessor acessa a propria fila
2. visualiza apenas as demandas da sua pasta
3. atualiza andamento, comenta e conclui entregas
4. o prefeito segue acompanhando todo o município
5. a operacao fica segmentada sem perder governanca central

### Fluxo 5: transformar execução em memoria territorial

1. o módulo agrupa demandas por localidade, tema e secretaria
2. identifica hotspots e temas recorrentes
3. compara janela recente com janela anterior
4. mostra onde a reincidencia subiu ou caiu
5. compara o desempenho entre pastas

### Fluxo 6: aproveitar demanda concluida em outros módulos

1. uma demanda concluida fica elegivel para integracao
2. o prefeito gera rascunho em `Comunicação`
3. ou abre conversa de narrativa/cobranca no `Meu Marqueteiro`
4. o contexto da demanda acompanha o novo fluxo
5. a entrega operacional vira insumo politico e comunicacional

## 6. Funcionalidades do Modulo

### 6.1 Registro e triagem

- entrada por texto
- entrada por voz assistida
- definicao de localidade
- definicao de secretaria
- prioridade alta, media ou baixa
- prazo manual ou automatico

### 6.2 Workflow

- registro
- andamento
- atraso automatico
- conclusao com comprovante
- confirmacao
- reabertura

### 6.3 Histórico e comentarios

- timeline imutavel
- comentarios operacionais
- eventos automaticos e manuais
- trilha de responsabilidade

### 6.4 Notificacoes e regua

- disparo inicial para responsaveis
- follow-up por inatividade
- lembrete por atraso
- repeticao de cobranca
- leitura de configuracao no painel

### 6.5 Perfis operacionais

- prefeito
- secretario
- assessor
- visibilidade por secretaria
- fila propria por pasta

### 6.6 Base territorial

- localidades cadastradas
- sugestao no formulario
- histórico por bairro
- temas recorrentes inferidos
- hotspots territoriais

### 6.7 Governanca comparativa

- desempenho por secretaria
- score comparativo
- tendencia recente vs anterior
- reincidencia em alta
- reincidencia em queda
- melhora ou piora de execução

### 6.8 Integracoes

- `Comunicação`
- `Meu Marqueteiro`
- contexto `resolve_ai` em conteudo e conversa

## 7. Como Utilizar o Modulo por Completo

### 7.1 Configurar o município

1. acessar o onboarding do município no `admin`
2. configurar prazos por prioridade, regua de cobranca e canais ativos
3. definir a janela comparativa territorial
4. cadastrar secretarias com contatos operacionais
5. cadastrar localidades ativas do município

### 7.2 Preparar usuarios e perfis

1. criar ou editar usuarios no `admin`
2. definir perfil `mayor`, `secretary` ou `advisor`
3. vincular o usuario a uma `contact_area` quando for secretaria ou assessor
4. ajustar permissao de registro quando necessário
5. validar o acesso com o perfil correspondente

### 7.3 Registrar uma demanda

1. acessar o painel do `Resolve ai`
2. usar texto ou voz para registrar o problema
3. preencher localidade, endereco, secretaria e prioridade
4. revisar o prazo calculado
5. salvar a demanda

### 7.4 Acompanhar a execução

1. abrir a demanda na fila
2. acusar recebimento ou registrar andamento
3. usar comentarios para atualizacao de campo
4. monitorar prazo, atraso e histórico
5. concluir quando a entrega estiver realizada

### 7.5 Confirmar ou reabrir

1. o criador acessa a demanda concluida
2. revisa a nota de entrega e o comprovante
3. confirma a conclusao quando estiver correta
4. ou reabre com justificativa
5. o fluxo volta para a secretaria se necessário

### 7.6 Operar a governanca territorial

1. revisar os cards de backlog e criticidade
2. consultar `Hotspots territoriais`, `Temas recorrentes` e `Histórico por secretaria`
3. abrir `Desempenho por secretaria`
4. analisar `Reincidencia em alta`, `Reincidencia em queda` e `Execução no territorio`
5. usar essa leitura para cobrar as pastas e ajustar a operacao

### 7.7 Acionar integracoes estrategicas

1. abrir uma demanda concluida ou aguardando confirmacao
2. gerar rascunho em `Comunicação` quando houver entrega comunicavel
3. abrir conversa de narrativa no `Meu Marqueteiro`
4. abrir conversa de cobranca pos-entrega no `Meu Marqueteiro`
5. seguir com o contexto automatico herdado da demanda

## 8. Validacoes Realizadas

Durante a entrega foram validados tecnicamente:

- correcao estrutural da view principal do módulo
- validacao de rotas e diagnostics nas iteracoes entregues
- estabilizacao do fluxo por secretaria e painel proprio do secretario
- validacao do onboarding operacional com secretarias, contatos e localidades
- integracao com `Comunicação` e `Meu Marqueteiro`
- funcionamento da regua automatica de inatividade e atraso
- leitura territorial com hotspots, temas recorrentes e histórico por secretaria
- camada comparativa com score por pasta e tendencia territorial
- configuracao da janela analitica por município
- diagnostics limpos nos arquivos alterados nas ultimas iteracoes

Tambem foi validado no fluxo operacional:

- uso coerente do painel principal do `Resolve ai`
- rastreabilidade das transicoes do workflow
- segmentacao de visibilidade por perfil
- leitura executiva da governanca comparativa no painel

## 9. Checklist Final do Que Ficou Pronto

- [x] base estrutural do `Resolve ai` criada sobre `demands`
- [x] workflow operacional com status claros
- [x] prazo automatico por prioridade
- [x] comprovante de conclusao
- [x] confirmacao do criador
- [x] reabertura com justificativa
- [x] timeline imutavel por demanda
- [x] notificacoes operacionais basicas
- [x] alerta de prazo e atraso
- [x] follow-up por inatividade
- [x] repeticao automatica para atraso
- [x] configuracoes operacionais por município
- [x] perfis `secretary` e `advisor`
- [x] visibilidade por secretaria
- [x] fila propria do secretario
- [x] vinculo de usuarios com `contact_area`
- [x] onboarding de secretarias e contatos
- [x] onboarding de localidades
- [x] sugestao de localidades no formulario
- [x] integracao com `Comunicação`
- [x] integracao com `Meu Marqueteiro`
- [x] origem `resolve_ai` em conteudo e conversa
- [x] hotspots territoriais
- [x] temas recorrentes
- [x] histórico territorial por secretaria
- [x] desempenho comparativo por secretaria
- [x] leitura evolutiva de reincidencia territorial
- [x] janela comparativa configuravel por município
- [x] painel executivo com backlog, filtros e lista operacional
- [x] diagnostics limpos nos arquivos editados nas ultimas iteracoes

## 10. Pendencias Residuais de Maturidade

- validar o módulo com massa de dados real de um município em operacao
- calibrar a heuristica de temas se aparecer ruido em casos reais
- avaliar cache ou persistencia da classificacao territorial se o volume crescer
- considerar testes automatizados focados nos fluxos mais sensiveis do módulo

## 11. Status Final

### Status do módulo

- concluido no codigo
- operacional para prefeito, secretario e assessor
- configuravel por município
- apto para operacao real do fluxo principal

### Pendencia bloqueante

- nenhuma pendencia bloqueante identificada nesta etapa

## 12. Recomendacao de Encerramento

Para fins de projeto e continuidade da plataforma, o módulo `Resolve ai` pode ser considerado finalizado no seu escopo principal.

Recomendacao objetiva:

- encerrar o módulo como entregue
- registrar apenas as pendencias residuais como maturidade operacional futura
- seguir para a proxima frente do plano da plataforma

## 13. Referencias

- plano vivo do módulo: `Plano-Modulo-Resolve-ai.md`
- controlador principal: `app/Http/Controllers/Mayor/DemandController.php`
- painel principal: `resources/views/mayor/demands/index.blade.php`
- detalhe da demanda: `resources/views/mayor/demands/show.blade.php`
- configuracoes do módulo: `app/Services/ResolveAi/ResolveAiSettingsService.php`
- regua automatica: `app/Console/Commands/DispatchResolveAiAlerts.php`
