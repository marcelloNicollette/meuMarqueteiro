# Entrega Tecnica e Resumo Executivo - Modulo Mandato

## 1. Resumo Executivo

O módulo `Mandato` foi concluido como frente de traducao do plano de governo em operacao diaria, acompanhamento executivo, leitura por eixo tematico e integracao orientada a entregas dentro da plataforma.

No estado atual, o módulo entrega:

- onboarding com upload do plano de governo e extracao inicial de compromissos por IA
- revisão manual da base inicial antes da ativacao definitiva
- organização do módulo em shell unico com areas de `Dashboard`, `Compromissos do Plano`, `Acoes de Governo` e `Briefings`
- gestao de eixos tematicos com configuracao propria
- cadastro, edição e exclusao de acoes de governo
- sugestao semantica de vinculo entre acao e compromisso
- nivel de atendimento do compromisso dentro da propria acao
- progresso fisico simples ou por marcos
- projecao de cumprimento do mandato com leitura de risco
- alerta operacional geral e por eixo tematico
- drill-down por eixo com leitura de atendimento e acoes associadas
- lista dedicada de compromissos pendentes sem acao vinculada
- integracao por leitura com `Meu Assistente`
- integracao automatica com `Comunicação` quando a acao e concluida
- integracao com `Projetos` por vinculo da acao a projeto salvo
- integracao por sugestao com `Radar de Recursos`
- integracao por sugestao com `Resolve ai`

Do ponto de vista de produto, o módulo esta pronto para operar como base estruturada do plano de governo dentro do painel do prefeito.

Do ponto de vista tecnico, o fluxo principal foi fechado e validado ate o capitulo `8.5` do PDF, sem pendencia bloqueante para uso interno do módulo.

## 2. Objetivo do Modulo

O `Mandato` funciona como módulo de traducao do plano politico em execução rastreavel.

O objetivo principal do módulo e:

- transformar promessas do plano de governo em base operacional organizada por eixo
- ligar acoes reais da gestao aos compromissos assumidos
- medir atendimento, lacunas e risco de entrega
- apoiar priorizacao executiva por eixo, promessa e acao
- gerar leitura pronta para cobranca política, comunicação e narrativa de cumprimento

## 3. Escopo Entregue

### 3.1 Fundacao do módulo

- criacao das tabelas `mandate_axes`, `mandate_promises`, `mandate_actions`
- shell unico do módulo com navegacao por `area`
- base do município carregada dentro do painel do prefeito

### 3.2 Onboarding do plano de governo

- upload do documento do plano no onboarding administrativo
- extracao inicial de compromissos por IA
- revisão da lista extraida antes de salvar
- edição de descrição, eixo, palavras-chave e inclusao manual de itens faltantes
- persistencia da base inicial do `Mandato`

### 3.3 Eixos tematicos

- cadastro de eixo com nome, icone e descrição
- edição dos eixos existentes
- leitura por eixo no dashboard
- drill-down dedicado de cada eixo

### 3.4 Compromissos do plano

- base de compromissos organizada por eixo
- score de atendimento por compromisso
- estados `pending`, `partial` e `fulfilled`
- contagem de acoes vinculadas
- lista especifica de compromissos pendentes sem acao vinculada

### 3.5 Acoes de governo

- criacao de acao com eixo, secretaria, descrição, datas e investimento
- vinculacao da acao a um ou mais compromissos
- nivel de atendimento configurado no momento do vinculo
- justificativa opcional por compromisso vinculado
- edição, exclusao e revisão da acao
- selecao de `status` com:
    - `planejado`
    - `nao_iniciado`
    - `em_andamento`
    - `concluido`
    - `suspenso`
- campos de `funding_source`, `beneficiaries`, `region`, `proof_url` e `is_public`

### 3.6 Progresso e marcos

- acompanhamento por percentual fisico
- modo opcional de progresso por marcos
- snapshot de progresso no histórico da acao
- leitura de marcos concluidos na interface

### 3.7 Leitura executiva e projecao

- KPIs de atendimento global
- leitura de compromissos atendidos, parciais e pendentes
- projecao de cumprimento ate o fim do mandato
- velocidade media diaria das acoes em andamento
- alerta geral de risco
- alerta por eixo abaixo do ritmo necessário

### 3.8 Atendimento por eixo tematico

- cards por eixo no dashboard
- percentual medio de atendimento por eixo
- contagem de promessas, acoes e pendencias
- abertura rápida do eixo ou criacao de nova acao ja filtrada

### 3.9 Lista de compromissos pendentes

- secao dedicada aos compromissos ainda sem acao vinculada
- agrupamento por eixo
- botao `Criar acao vinculada`
- botao `Verificar acoes existentes`

### 3.10 Integracoes com outros módulos

- `Meu Assistente`
    - leitura de compromissos pendentes sem acao
    - leitura de projecao e risco
    - leitura de eixos abaixo da media
    - leitura de acoes concluidas como repertorio politico e comunicacional
- `Comunicação`
    - ao marcar a acao como `concluido`, o sistema cria ou atualiza automaticamente sugestao de pauta no nucleo de operacao
    - contexto pre-carregado com titulo, eixo, descrição, beneficiarios, regiao e evidencia
- `Projetos`
    - acao pode ser vinculada a um projeto salvo
    - projeto concluido passa a sugerir revisão do status da acao vinculada
- `Radar de Recursos`
    - compromissos pendentes sem acao podem receber sugestao de oportunidade ativa compativel
- `Resolve ai`
    - demandas concluidas e recorrentes podem aparecer como evidencia potencial para compromisso aberto

## 4. Principais Entregas Tecnicas

### Backend

- controlador central em `app/Http/Controllers/Mayor/MandatoController.php`
- catalogo de eixos em `app/Services/Mandato/MandateAxisCatalogService.php`
- extracao inicial de compromissos em `app/Services/Mandato/MandatePromiseExtractionService.php`
- sugestao de vinculo semantico em `app/Services/Mandato/MandatePromiseLinkingService.php`
- progresso e snapshots em `app/Services/Mandato/MandateActionProgressService.php`
- projecao de cumprimento em `app/Services/Mandato/MandateProjectionService.php`
- integracao com `Comunicação` em `app/Services/Mandato/MandateCommunicationSuggestionService.php`
- integracao com `Radar de Recursos` em `app/Services/Mandato/MandateRadarOpportunitySuggestionService.php`
- integracao com `Resolve ai` em `app/Services/Mandato/MandateResolveAiEvidenceSuggestionService.php`
- leitura do `Mandato` no assistente em `app/Services/AI/AssistantContextService.php`

### Frontend

- shell principal em `resources/views/mayor/mandato/shell.blade.php`
- gestao de eixos em `resources/views/mayor/mandato/eixos.blade.php`
- cadastro de acao em `resources/views/mayor/mandato/acao-create.blade.php`
- edição de acao em `resources/views/mayor/mandato/acao-edit.blade.php`
- apoio visual de marcos em `resources/views/mayor/mandato/partials/action-milestones.blade.php`
- onboarding administrativo em `resources/views/admin/municipalities/onboarding.blade.php`

### Persistencia e dados

- base do módulo em `database/migrations/2026_01_01_000020_create_mandate_axes_table.php`
- promessas do mandato em `database/migrations/2026_01_01_000021_create_mandate_promises_table.php`
- acoes do mandato em `database/migrations/2026_01_01_000022_create_mandate_actions_table.php`
- campos de extracao em `database/migrations/2026_05_25_000006_add_extraction_fields_to_mandate_promises_table.php`
- marcos em `database/migrations/2026_05_26_000007_add_milestones_to_mandate_actions.php`
- snapshots de progresso em `database/migrations/2026_05_26_000008_add_snapshot_fields_to_mandate_action_progress_logs.php`
- vinculo com projetos em `database/migrations/2026_05_26_000009_add_project_link_to_mandate_actions.php`

### Rotas e operacao

- rotas do módulo em `routes/web.php`
- entrada principal em `mayor.mandato.painel`
- rotas de eixos, acoes, briefings e integracao com `Radar`

## 5. Como Fazer Funcionar

### 5.1 Preparacao tecnica

Para deixar o módulo operacional no ambiente:

1. executar as migrations do projeto
2. garantir que o município e seus usuarios existam
3. acessar o onboarding administrativo do município
4. subir o documento do plano de governo
5. revisar e salvar a base inicial do `Mandato`
6. ativar o município, se ainda estiver pendente

Comandos minimos esperados:

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

Se o ambiente ja usa `MAMP`, basta garantir banco, `.env` e migrations aplicadas antes de abrir o painel.

### 5.2 Como ativar o módulo no onboarding

Caminho de cliques no admin:

1. entrar no painel administrativo
2. abrir `Municipios`
3. escolher o município
4. abrir `Onboarding`
5. localizar a secao `Plano de Governo e Mandato`
6. enviar o arquivo do plano de governo
7. revisar a lista extraida pela IA
8. corrigir descrição, eixo e palavras-chave quando necessário
9. adicionar manualmente compromissos que não vieram do documento, se precisar
10. clicar em `Salvar base inicial do Mandato`
11. concluir a ativacao do município

Sem esse passo, o módulo pode abrir, mas não tera base inicial consistente de compromissos.

### 5.3 Como abrir o módulo no painel do prefeito

Caminho de cliques:

1. entrar com usuario `mayor`
2. no menu lateral, clicar em `Mandato`
3. o sistema abre o shell principal do módulo

Atalhos de topo ja disponiveis no shell:

- `Nova acao`
- `Gerenciar eixos`
- `Radar de Recursos`

Abas principais do shell:

- `Dashboard`
- `Compromissos do Plano`
- `Acoes de Governo`
- `Briefings`

## 6. Como Usar no Dia a Dia

### 6.1 Configurar os eixos tematicos

Caminho de cliques:

1. abrir `Mandato`
2. clicar em `Gerenciar eixos`
3. revisar os eixos existentes
4. para criar novo eixo, preencher `Nome do eixo`, `Icone` e `Descrição`
5. clicar em `Salvar`

Uso recomendado:

- criar um eixo por frente real de governo
- evitar eixos muito genericos
- manter descrição curta com subareas para orientar a base

### 6.2 Criar uma nova acao de governo

Caminho de cliques:

1. abrir `Mandato`
2. clicar em `Nova acao`
3. preencher `Título da acao`
4. selecionar `Eixo tematico`
5. informar `Secretaria responsável`
6. opcionalmente escolher `Projeto vinculado`
7. preencher `Descrição`
8. definir `Status`
9. informar `% de execução fisica`
10. preencher datas quando existirem
11. selecionar os compromissos relacionados
12. revisar o `Nivel de atendimento` de cada compromisso marcado
13. preencher `Investimento previsto`, `Fonte de recurso`, `Beneficiários estimados` e `Regiao / Bairro` quando houver
14. informar `Link de comprovacao`, se existir
15. marcar `Visivel no painel publico (cidadao)` quando fizer sentido
16. salvar a acao

Observacao pratica:

- o formulario ja aceita criacao orientada pelo compromisso, quando você entra por `Criar acao vinculada`
- o botao de sugestao semantica ajuda a preselecionar compromissos compatíveis, mas a revisão final continua humana

### 6.3 Atualizar andamento ou concluir uma acao

Caminho de cliques:

1. abrir `Mandato`
2. entrar na aba `Acoes de Governo`
3. localizar a acao pelo eixo ou contexto em revisão
4. clicar em `Abrir acao`
5. ajustar `Status`, `% de execução fisica`, datas, marcos e comprovacao
6. clicar em `Salvar alteracoes`

Efeito importante:

- se a acao passar para `Concluido`, o módulo envia automaticamente uma sugestao de pauta para `Comunicação`

### 6.4 Trabalhar a partir dos compromissos pendentes

Caminho de cliques:

1. abrir `Mandato`
2. entrar na aba `Compromissos do Plano`
3. localizar a secao `Lista de compromissos pendentes`
4. escolher um compromisso sem acao vinculada
5. clicar em `Criar acao vinculada` para abrir o formulario ja contextualizado
6. ou clicar em `Verificar acoes existentes` para revisar se ja existe alguma entrega ligada ao tema

Atalhos adicionais que podem aparecer no card:

- `Abrir Radar de Recursos`
- `Abrir demanda concluida`

Esses atalhos existem para apoiar decisão, não para registrar nada automaticamente.

### 6.5 Ler o dashboard executivo

Caminho de cliques:

1. abrir `Mandato`
2. permanecer na aba `Dashboard`
3. ler os KPIs principais
4. olhar o bloco `Projecao ate o fim do mandato`
5. verificar se existe alerta geral de risco
6. abrir o eixo sugerido quando houver alerta por eixo

O dashboard e o melhor ponto de entrada para:

- leitura rápida do ritmo de execução
- identificação de eixos abaixo da media
- abertura dos compromissos pendentes sem acao
- revisão das acoes concluidas mais recentes

### 6.6 Usar as integracoes na pratica

#### Comunicação

1. concluir uma acao no `Mandato`
2. abrir `Comunicação`
3. entrar no nucleo de operacao
4. filtrar por `Mandato em conteudo`, se quiser
5. abrir a sugestao gerada automaticamente

#### Projetos

1. abrir ou criar uma acao no `Mandato`
2. selecionar `Projeto vinculado`
3. salvar
4. quando o projeto for concluido, revisar a sugestao de atualizar o status da acao

#### Radar de Recursos

1. abrir `Mandato`
2. entrar em `Compromissos do Plano`
3. localizar um compromisso pendente sem acao
4. quando houver sugestao, clicar em `Abrir Radar de Recursos`
5. avaliar se a oportunidade pode destravar a execução

#### Resolve ai

1. abrir `Mandato`
2. entrar em `Compromissos do Plano`
3. localizar um compromisso com evidencia sugerida do `Resolve ai`
4. clicar em `Abrir demanda concluida`
5. revisar se aquela entrega recorrente deve virar acao de governo registrada no `Mandato`

#### Meu Assistente

Nao exige clique adicional dentro do `Mandato`.

O assistente passa a ler automaticamente:

- compromissos pendentes sem acao
- ritmo e projecao
- eixos abaixo da media
- acoes concluidas como repertorio de comunicação

## 7. Fluxos Principais Entregues

### Fluxo 1: subir o plano e formar a base inicial

1. admin envia o documento do plano no onboarding
2. IA extrai compromissos iniciais
3. equipe revisa a lista
4. base e salva no município
5. módulo passa a operar com compromissos reais do plano

### Fluxo 2: abrir uma acao a partir de um compromisso

1. usuario entra em `Compromissos do Plano`
2. identifica item pendente sem acao
3. clica em `Criar acao vinculada`
4. completa o formulario
5. salva a acao com nivel de atendimento associado

### Fluxo 3: acompanhar risco e projecao

1. usuario abre o `Dashboard`
2. sistema calcula projeicao com base no ritmo recente
3. exibe alerta geral e alertas por eixo quando necessário
4. usuario abre o eixo em risco
5. prioriza novas acoes ou revisa a carteira atual

### Fluxo 4: concluir entrega e acionar comunicação

1. usuario abre a acao
2. atualiza a execução ate `Concluido`
3. salva a alteracao
4. sistema cria ou atualiza sugestao no módulo `Comunicação`
5. equipe de comunicação decide se avanca para producao

### Fluxo 5: destravar compromisso com módulo vizinho

1. usuario entra na lista de compromissos pendentes
2. sistema sugere oportunidade do `Radar` ou evidencia do `Resolve ai`
3. usuario abre o módulo relacionado
4. revisa a oportunidade ou a entrega concluida
5. decide se cria uma nova acao ou atualiza a leitura do compromisso

## 8. Validacoes Realizadas

Durante a entrega foram validados tecnicamente:

- extracao inicial e revisão de compromissos no onboarding
- shell unico do módulo e navegacao por `area`
- criacao e edição de acoes
- sugestao semantica de vinculo acao-compromisso
- progresso por percentual e por marcos
- projecao de cumprimento e alerta por eixo
- lista de compromissos pendentes sem acao
- leitura do `Mandato` no `Meu Assistente`
- criacao automatica de sugestao para `Comunicação` ao concluir acao
- vinculo da acao a `Projetos`
- sugestao do `Radar de Recursos` para compromisso pendente
- sugestao de evidencia do `Resolve ai` para compromisso aberto
- recompilacao de views com `php artisan view:cache`
- diagnostics sem erros nos arquivos alterados

Tambem foi validado no ambiente:

- `php artisan test tests/Feature/MandatoSmokeTest.php`
- `php artisan test`

## 9. Checklist Final do Que Ficou Pronto

- [x] onboarding do plano de governo com extracao inicial
- [x] revisão manual da base inicial do mandato
- [x] shell unico do módulo
- [x] dashboard executivo
- [x] gestao de eixos tematicos
- [x] cadastro e edição de acoes
- [x] sugestao semantica de vinculo com compromissos
- [x] niveis de atendimento por compromisso
- [x] progresso por percentual
- [x] progresso opcional por marcos
- [x] projecao de cumprimento
- [x] alerta geral de risco
- [x] alerta por eixo
- [x] drill-down por eixo
- [x] lista de compromissos pendentes sem acao vinculada
- [x] integracao por leitura com `Meu Assistente`
- [x] integracao automatica com `Comunicação`
- [x] integracao com `Projetos`
- [x] sugestao com `Radar de Recursos`
- [x] sugestao com `Resolve ai`

## 10. Pendencias Opcionais Futuras

- bloco `9. Integracao com o Site da Prefeitura` mantido como evolucao futura
- `R9` de controle de acesso por pasta ainda deve ser formalizado como endurecimento transversal de governanca, sem bloquear o módulo atual
- `R10` de arquitetura preparada para integracao publica segue como diretriz arquitetural futura
- ampliar testes focados em cenarios multiusuario e maior volume de dados

## 11. Status Final

### Status do módulo

- concluido no codigo ate o escopo funcional do PDF antes do bloco `9`
- validado no fluxo principal
- pronto para uso interno no painel do prefeito
- pronto para handoff documental

### Pendencia bloqueante

- nenhuma pendencia bloqueante identificada no escopo funcional fechado

## 12. Recomendacao de Encerramento

Para fins de projeto e continuidade da plataforma, o módulo `Mandato` pode ser considerado finalizado no seu escopo principal implementado.

Recomendacao objetiva:

- encerrar o módulo como entregue ate `8.5`
- registrar o bloco `9` como evolucao futura
- manter `R9` e `R10` como pendencias tecnicas transversais
- usar este documento como base de handoff funcional e operacional

## 13. Referencias

- referencia funcional original: `qu4tro.ai - MÓDULO Mandato.pdf`
- shell do módulo: `resources/views/mayor/mandato/shell.blade.php`
- controlador principal: `app/Http/Controllers/Mayor/MandatoController.php`
- smoke test principal: `tests/Feature/MandatoSmokeTest.php`
