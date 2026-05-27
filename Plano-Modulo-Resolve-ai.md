# Plano do Modulo Resolve ai

## 1. Visão Geral

O módulo `Resolve ai` e a frente da plataforma responsável por registrar, encaminhar, acompanhar, cobrar e concluir demandas operacionais da gestao municipal.

O objetivo do módulo e transformar um fluxo informal de solicitacoes recebidas pelo prefeito, gabinete ou secretarias em um processo rastreavel, com:

- registro rapido por voz ou texto
- definicao de localidade, secretaria e prioridade
- prazo operacional
- histórico imutavel de andamento
- conclusao com comprovante
- confirmacao do criador
- leitura executiva do backlog e dos atrasos

## 2. Base de Referencia

Este plano foi consolidado a partir do documento:

- `qu4tro.ai - MÓDULO Resolve aí.docx`

Principais blocos funcionais especificados:

- registro da demanda em menos de dois minutos
- fluxo de encaminhamento e acompanhamento
- status claros do ciclo de vida
- painel com totais, lista filtrada e leitura rápida
- configuracoes por município
- histórico por demanda
- comprovante de conclusao
- integracao futura com `Comunicação` e `Meu Marqueteiro`

## 3. Escopo Funcional do Modulo

### 3.1 Registro

- descrição por texto ou voz
- localidade e endereco complementar
- secretaria responsável
- prioridade
- prazo
- data e hora automaticas

### 3.2 Workflow

- registrada
- em andamento
- atrasada
- aguardando confirmacao
- concluida
- reaberta

### 3.3 Acompanhamento

- acuse de recebimento
- atualizacoes de andamento
- linha do tempo imutavel
- comprovante de conclusao
- confirmacao do criador
- reabertura com justificativa

### 3.4 Painel

- total abertas
- total atrasadas
- total concluidas no mes
- distribuicao por secretaria
- lista com filtros
- ordenacao por criticidade e prazo

## 4. Reaproveitamento do Projeto Atual

O projeto ja possuia uma base inicial de demandas em:

- `app/Models/Demand.php`
- `app/Http/Controllers/Mayor/DemandController.php`
- `resources/views/mayor/demands/index.blade.php`
- `resources/views/mayor/demands/show.blade.php`
- `app/Models/ContactArea.php`

A estrategia adotada para o `Resolve ai` foi evoluir essa base, em vez de criar um módulo paralelo.

## 5. Primeira Iteracao Implementada

Nesta primeira iteracao, foi entregue a fundacao operacional do módulo:

### 5.1 Workflow e dados

- ampliacao da tabela `demands`
- novo campo `due_at`
- carimbos de fluxo: `acknowledged_at`, `last_progress_at`, `completion_requested_at`, `confirmed_at`, `reopened_at`
- dados complementares: `address`, `completion_note`, `reopened_reason`
- suporte a comprovante de conclusao
- tabela nova `demand_events` para timeline imutavel

### 5.2 Fluxo operacional

- registro de demanda ja no modelo `Resolve ai`
- prazo automatico por prioridade
- acuse de recebimento / início de andamento
- marcacao de conclusao com comprovante
- confirmacao da conclusao
- reabertura com justificativa
- atualizacao manual de andamento
- mudanca automatica para `atrasada` quando o prazo vence

### 5.3 Interface

- renomeacao da navegacao para `Resolve ai`
- novo painel com cards de totais
- distribuicao por secretaria
- filtros por status, prioridade, secretaria, criador, localidade e periodo
- lista operacional com leitura de prazo e criticidade
- detalhe completo da demanda com histórico
- formularios de workflow dentro da propria demanda

### 5.4 Validacao tecnica

- migration aplicada com sucesso
- diagnostics limpos nos arquivos alterados
- rotas de demandas validadas no `artisan route:list`

## 6. Arquivos Principais

- `database/migrations/2026_05_25_000001_upgrade_demands_for_resolve_ai.php`
- `database/migrations/2026_05_25_000002_create_demand_notifications_table.php`
- `app/Models/DemandEvent.php`
- `app/Models/DemandNotification.php`
- `app/Models/Demand.php`
- `app/Http/Controllers/Mayor/DemandController.php`
- `app/Services/ResolveAi/ResolveAiSettingsService.php`
- `app/Services/ResolveAi/ResolveAiNotificationService.php`
- `app/Console/Commands/DispatchResolveAiAlerts.php`
- `app/Http/Controllers/Admin/MunicipalityLocalityController.php`
- `app/Models/MunicipalityLocality.php`
- `resources/views/mayor/demands/index.blade.php`
- `resources/views/mayor/demands/show.blade.php`
- `resources/views/layouts/mayor.blade.php`

## 7. Próximas Iteracoes Recomendadas

### Iteracao 2

- configuracoes operacionais por município
- prazo por prioridade configuravel
- canais ativos por município
- exigencia de comprovante por prioridade
- disparo inicial de notificacoes por e-mail e log interno
- comando agendado para alertas de prazo e atraso

### Iteracao 3

- perfis `secretary` e `advisor`
- vinculo direto de usuario com `contact_area`
- permissao de registro de demandas para assessor
- visibilidade restrita por secretaria no Resolve ai
- painel proprio do secretario com sua fila operacional

### Iteracao 4

- onboarding operacional do módulo:
    - secretarias e orgãos
    - contatos responsaveis
    - e-mail operacional de notificacao
    - contato backup por pasta
    - bairros e localidades
    - base territorial sugerida no formulario do Resolve ai

### Iteracao 5

- integracao com `Comunicação`
- criacao de conteudo a partir de demanda concluida
- rascunho de comunicação gerado direto da demanda concluida
- metadata de origem `resolve_ai` em `GeneratedContent`

### Iteracao 6

- integracao de leitura com `Meu Marqueteiro`
- conversa contextualizada de narrativa política a partir da demanda concluida
- conversa contextualizada de cobranca e acompanhamento pos-entrega

### Iteracao 7

- cobrancas proativas por inatividade
- repeticao automatica de lembrete para demandas atrasadas
- atualizacao da cadencia operacional no painel e onboarding

### Iteracao 8

- recorrencia por bairro e tema
- hotspots territoriais com localidade dominante
- histórico territorial por secretaria
- memoria operacional do territorio no painel do Resolve ai

### Iteracao 9

- indicador de desempenho por secretaria
- recorrencia historica mais longa por bairro e tema
- leitura evolutiva de reincidencia e resolucao territorial
- score comparativo por pasta com taxa de resolucao, atraso e tempo medio de fechamento
- comparacao de janela recente vs janela anterior para mostrar melhora ou piora da execução
- janela comparativa configuravel por município para leitura territorial e governanca operacional

## 8. Status Atual

O `Resolve ai` entrou em desenvolvimento a partir da base preexistente de demandas e agora possui uma primeira camada funcional e coerente com a especificacao do módulo.

Estado atual:

- fundacao de dados: concluida
- workflow principal: concluido na primeira camada
- painel inicial: concluido
- timeline imutavel: concluida
- configuracoes operacionais do módulo: concluida na segunda camada
- notificacoes operacionais basicas: concluida na segunda camada
- scheduler de alertas de prazo: concluido na segunda camada
- perfis por secretaria: concluido na terceira camada
- vinculo de usuarios com secretaria: concluido na terceira camada
- painel proprio do secretario: concluido na terceira camada
- onboarding operacional de secretarias e contatos: concluido na quarta camada
- onboarding operacional de localidades: concluido na quarta camada
- sugestao de localidades no formulario: concluida na quarta camada
- integracao com Comunicação: concluida na quinta camada
- integracao com Meu Marqueteiro: concluida na sexta camada
- cobrancas proativas automatizadas por prazo: concluida na setima camada
- lembretes por inatividade e atraso recorrente: concluidos na setima camada
- recorrencia por bairro e tema: concluida na oitava camada
- histórico territorial por secretaria: concluido na oitava camada
- indicadores de desempenho por secretaria: concluidos na nona camada
- leitura evolutiva de reincidencia territorial: concluida na nona camada
- janela comparativa configuravel por município: concluida na nona camada

## 9. Fechamento do Modulo

Pelo documento funcional consolidado e pelas iteracoes executadas, o escopo principal do `Resolve ai` esta essencialmente entregue.

Entregas centrais ja cobertas:

- registro por texto e voz assistida
- workflow com prazo, prioridade e secretaria responsável
- histórico imutavel, comprovante e confirmacao
- painel operacional com filtros e backlog
- configuracoes por município
- onboarding operacional de secretarias, contatos e localidades
- notificacoes e cobrancas automatizadas
- memoria territorial por bairro, tema e secretaria
- governanca comparativa por secretaria
- leitura evolutiva de reincidencia territorial
- integracao com `Comunicação`
- integracao com `Meu Marqueteiro`

O que ainda falta para considerar encerramento formal da entrega:

- validacao funcional em ambiente real com massa de dados do município
- calibracao fina da heuristica de temas caso apareca ruido em uso real
- decidir se a classificacao territorial precisara de cache ou persistencia quando o volume crescer
- produzir documento final de fechamento do módulo, nos moldes do que foi feito no `Radar de Recursos`

## 10. Proximo Passo Objetivo

O proximo passo mais aderente ao documento original e:

- fazer a validacao final do `Resolve ai` com dados reais e preparar o fechamento formal da entrega, deixando como melhorias de maturidade apenas a calibracao fina da heuristica territorial e eventual cache/persistencia analitica se o volume aumentar.
