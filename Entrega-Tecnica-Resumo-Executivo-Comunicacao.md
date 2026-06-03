# Entrega Tecnica e Resumo Executivo - Comunicação

## 1. Resumo Executivo

O módulo `Comunicação` foi concluido como frente editorial e operacional completa da plataforma, concentrando em um unico shell:

- `Produzir`
- `Menções`
- `Nucleo de Operacao`
- `Arquivo`

No estado final entregue, o módulo cobre:

- geração multicanal e multitom
- identidade de comunicação por município
- contradicao historica antes da geração
- roteiro de crise evolutivo por seções
- monitoramento recorrente de menções
- classificacao automatica com correcao manual
- kanban operacional da pauta com drag-and-drop
- fluxo de aprovacao configuravel por tipo e perfil
- arquivo com versoes, memoria, sessao de geração e trilha auditavel
- integracao com `Resolve ai`
- integracao com `Meu Assistente`

Do ponto de vista de produto, o módulo esta pronto para operacao real da equipe de comunicação municipal.

Do ponto de vista tecnico, o módulo atende o escopo principal do documento e pode ser considerado encerrado no seu ciclo principal.

## 2. Objetivo do Modulo

O `Comunicação` funciona como módulo de planejamento, producao, monitoramento, aprovacao, resposta e memoria institucional da comunicação do município.

O objetivo principal do módulo e:

- transformar entregas, agendas, fatos de governo e crises em pecas publicaveis
- dar governanca editorial para revisão, aprovacao, agenda e publicacao
- monitorar sinais externos que exigem resposta rápida
- operar a pauta diaria da equipe de comunicação
- preservar memoria institucional para reuso, coerencia e aprendizado

## 3. Estrutura Final Entregue

### 3.1 Produzir

- posts para canais digitais
- imagens com IA
- preparo de entrevista
- resposta e gestao de crise
- templates por canal e formato
- playbooks editoriais por situacao
- refinamento assistido e novas variacoes
- agenda editorial e workflow da peca

### 3.2 Menções

- monitoramento recorrente
- filtro por classe e fonte
- termometro reputacional
- urgência reputacional
- curadoria manual
- reclassificacao manual
- acao direta para abrir crise

### 3.3 Nucleo de Operacao

- kanban operacional da pauta
- leitura por prazo, prioridade, pasta e origem
- sugestoes vindas do `Resolve ai`
- arrastar e soltar entre colunas
- atividade recente
- cobertura operacional da equipe

### 3.4 Arquivo

- histórico recente
- filtros por tipo, canal, tom, perfil e periodo
- histórico de versoes
- sessao formal de geração
- trilha auditavel de eventos editoriais
- memoria de crise
- memoria de media training
- reuso de item anterior
- remocao auditavel do arquivo

## 4. Escopo Entregue

### 4.1 Geração editorial

- geração multicanal em lote
- geração multitom por peca
- combinacao real de canal x tom
- selecao de varios canais na mesma geração
- lote multicanal navegavel na interface

### 4.2 Identidade de comunicação

- configuracao de `tone`
- configuracao de `style`
- configuracao de `vocabulary`
- configuracao de `priority_themes`
- configuracao de `avoid`
- aplicacao desses sinais no prompt da IA

### 4.3 Coerencia historica

- checagem historica previa antes de gerar
- busca de referencias recentes do município
- painel de consistencia historica na peca aberta
- sinalizacao de `ok` ou `attention`

### 4.4 Crise evolutiva

- roteiro inicial estruturado por seções
- evolucao incremental por seções impactadas
- histórico de iteracoes da crise
- memoria de crise no arquivo
- reuso de crise anterior

### 4.5 Menções e reputacao

- monitoramento a cada 2 horas
- classificacao `positive`, `neutral`, `negative`, `urgent`
- filtro por janela, classe e fonte
- cadastro manual de menção
- reclassificacao manual do sentimento
- CTA `Abrir crise`

### 4.6 Workflow editorial

- observacao editorial
- pedido de ajuste
- aprovacao com observacao
- aprovacao simples
- agendamento
- reordenacao
- publicacao
- arquivamento

### 4.7 Aprovacao configuravel

- aprovador por tipo de peca
- configuracao por município
- bloqueio por perfil aprovador
- rejeicao com justificativa
- aviso ao aprovador com link direto

### 4.8 Operacao da pauta

- kanban com cinco colunas
- movimentacao por drag-and-drop
- persistencia do movimento
- evento operacional registrado no histórico da demanda
- integracao com rascunho de conteudo
- integracao com narrativa no `Meu Assistente`

### 4.9 Arquivo e memoria institucional

- filtros aderentes ao documento
- versoes por item
- memoria aplicada
- sessao de geração
- log de geração
- remocao auditavel do arquivo
- reuso para novas pecas

### 4.10 Integracoes

- `Resolve ai` para transformar demanda em conteudo
- `Resolve ai` para alimentar a pauta operacional
- `Meu Assistente` para abrir narrativa estrategica
- `Meu Assistente` com alerta proativo de menções urgentes e negativas

## 5. Validacao Final Contra o Documento

### R1 - Geração multicanal e multitom

- [x] atendido

Como foi fechado:

- selecao de multiplos canais no `Produzir`
- geração de uma peca por canal no backend
- lote multicanal retornado para a interface

### R2 - Adaptacao por identidade de comunicação

- [x] atendido

Como foi fechado:

- onboarding com voz, vocabulario, temas prioritarios e restricoes
- aplicacao desses campos na instrucao usada pela IA

### R3 - Deteccao de contradicao historica

- [x] atendido

Como foi fechado:

- checagem historica previa com referencias recentes do município
- registro de `historical_check`
- painel visual de consistencia no editor

### R4 - Roteiro de crise evolutivo

- [x] atendido

Como foi fechado:

- crise estruturada por seções
- evolucao incremental por seções afetadas
- histórico de iteracoes

### R5 - Pipeline de monitoramento de menções

- [x] atendido

Como foi fechado:

- monitoramento recorrente
- classes de sentimento
- urgência
- curadoria manual
- entrada manual
- disparo para crise

### R6 - Classificacao de sentimento por NLP com correcao manual

- [x] atendido

Como foi fechado:

- classificacao automatica por IA
- leitura operacional das classes
- reclassificacao manual no shell de `Menções`

### R7 - Kanban da pauta

- [x] atendido

Como foi fechado:

- quadro com cinco colunas
- cards operacionais ricos
- drag-and-drop com persistencia
- evento operacional registrado

### R8 - Fluxo de aprovacao configuravel

- [x] atendido

Como foi fechado:

- aprovador por tipo de peca
- configuracao por município
- bloqueio de aprovacao por perfil
- publicacao dependente da aprovacao correta
- rejeicao com justificativa
- aviso ao aprovador com link direto

### R9 - Arquivo com histórico de versoes

- [x] atendido

Como foi fechado:

- histórico de versoes
- sessao formal de geração
- trilha auditavel da geração
- remocao auditavel do arquivo

### R10 - Integracao com Resolve ai e Meu Assistente

- [x] atendido

Como foi fechado:

- `Resolve ai` integrado ao `Nucleo de Operacao` e a geração de rascunho
- `Meu Assistente` integrado a narrativa e a alertas proativos de menções sensiveis

## 6. Fluxos Principais Entregues

### Fluxo 1: gerar e revisar uma peca

1. abrir `Comunicação > Produzir`
2. escolher o tipo de peca
3. preencher tema, contexto, canal, tom, template e playbook
4. gerar a peca
5. revisar no `Editor e Revisão`
6. refinar, variar, aprovar, agendar ou publicar

### Fluxo 2: operar um lote multicanal

1. selecionar mais de um canal na aba de `Comunicação`
2. definir os tons desejados
3. gerar o lote
4. navegar entre os canais pelo seletor do lote
5. aprovar ou editar cada peca individualmente

### Fluxo 3: responder uma crise em evolucao

1. abrir a aba `Crise`
2. descrever a situacao e gerar o roteiro inicial
3. revisar as seções do plano
4. marcar as seções impactadas quando surgir um fato novo
5. informar a mudanca de cenario
6. evoluir o roteiro apenas nas seções afetadas
7. preservar o histórico das evolucoes

### Fluxo 4: operar menções e reputacao

1. abrir `Comunicação > Menções`
2. filtrar por urgência, negatividade, fonte e periodo
3. revisar o termometro reputacional
4. registrar menção manual quando necessário
5. reclassificar a menção, se a leitura automatica precisar de ajuste
6. abrir crise direto quando a menção exigir resposta

### Fluxo 5: operar a pauta da equipe

1. abrir `Comunicação > Nucleo de Operacao`
2. filtrar por tipo, pasta, prioridade e periodo
3. arrastar cards entre as colunas do kanban
4. revisar prazos, cobertura e atividade recente
5. abrir demanda detalhada, gerar conteudo ou abrir narrativa

### Fluxo 6: reutilizar memoria institucional

1. abrir `Comunicação > Arquivo`
2. filtrar o recorte desejado
3. abrir um item para ver versoes, memoria e trilha auditavel
4. usar `Reusar como base`
5. voltar ao `Produzir` ja com o contexto reaplicado

## 7. Como Utilizar Todos os Itens do Modulo

### 7.1 Configurar o município no admin

1. acessar o onboarding do município no `admin`
2. configurar o perfil de voz:
    - tom
    - estilo
    - vocabulario
    - temas prioritarios
    - evitar
3. configurar o SLA editorial:
    - revisão inicial
    - aprovado para publicar
    - antecedencia do agendado
4. configurar o aprovador por tipo:
    - post
    - imagem
    - entrevista
    - crise
5. salvar as configuracoes do módulo

### 7.2 Usar `Produzir`

#### Comunicação

1. informar o tema
2. selecionar um ou mais canais
3. selecionar os tons
4. aplicar template, se existir
5. aplicar playbook, se fizer sentido
6. gerar o lote
7. abrir cada peca no editor

#### Imagem IA

1. informar o tema visual
2. definir estilo e formato
3. aplicar template de imagem
4. gerar prompts e dicas de design
5. revisar e arquivar para referencia futura

#### Entrevista

1. preencher o contexto da entrevista
2. informar temas sensiveis
3. aplicar playbook de entrevista
4. gerar a preparacao
5. salvar memoria pos-entrevista no arquivo

#### Crise

1. descrever a crise
2. aplicar playbook de crise quando necessário
3. gerar o roteiro inicial
4. evoluir o plano conforme o dia avanca
5. registrar o desfecho no arquivo

### 7.3 Usar templates

1. montar uma configuracao util no `Produzir`
2. salvar como template
3. reutilizar o template em novas geracoes
4. manter formatos padronizados por canal ou situacao

### 7.4 Usar playbooks editoriais

1. selecionar um playbook operacional por situacao
2. deixar o sistema sugerir canal, formato e checklist
3. gerar a peca com esse contexto embutido
4. manter consistencia da rotina da equipe

### 7.5 Usar refino e variacoes

1. abrir uma peca gerada
2. usar `Refino Assistido` para ajustar texto
3. pedir novas variacoes quando precisar de alternativas
4. manter o histórico editorial da IA na mesma peca

### 7.6 Usar colaboracao, aprovacao e publicacao

1. registrar observacao no painel colaborativo
2. rejeitar com justificativa quando precisar de ajuste
3. aprovar com observacao quando a peca estiver pronta
4. respeitar o perfil aprovador configurado
5. agendar ou publicar so depois da aprovacao correta

### 7.7 Usar SLA editorial

1. acompanhar os cards de SLA por etapa
2. abrir a fila critica de vencimento
3. atacar primeiro as pecas `overdue` e `at_risk`
4. usar o contexto de SLA dentro da peca aberta

### 7.8 Usar Menções

1. revisar o termometro reputacional
2. filtrar urgentes, negativas ou por fonte
3. registrar menção manual de WhatsApp ou outra origem
4. reclassificar quando a leitura automatica estiver imprecisa
5. abrir crise direto da menção

### 7.9 Usar Nucleo de Operacao

1. revisar backlog e distribuicao da pauta
2. arrastar cards entre as colunas
3. abrir a demanda detalhada quando precisar de contexto operacional
4. gerar conteudo a partir de sugestao do `Resolve ai`
5. abrir narrativa no `Meu Assistente` quando a pauta pedir articulacao política

### 7.10 Usar Arquivo

1. filtrar itens por tipo, canal, tom, perfil e periodo
2. abrir uma peca para ver:
    - versoes
    - memoria aplicada
    - sessao de geração
    - trilha auditavel
3. reusar uma peca anterior como base
4. remover do arquivo quando não fizer mais sentido manter o item no recorte operacional
5. manter a trilha auditavel preservada mesmo apos a remocao

### 7.11 Usar a integracao com `Resolve ai`

1. entrar no `Nucleo de Operacao`
2. localizar sugestoes de demandas prontas para virar conteudo
3. gerar rascunho em `Comunicação`
4. seguir no workflow editorial normal

### 7.12 Usar a integracao com `Meu Assistente`

1. abrir narrativa estrategica a partir de uma demanda ou pauta
2. receber no chat alertas proativos de menções urgentes e negativas
3. clicar no alerta
4. cair direto em `Comunicação > Menções`
5. decidir se abre crise, reposiciona a pauta ou responde institucionalmente

## 8. Principais Entregas Tecnicas

### Backend

- `app/Http/Controllers/Mayor/ContentController.php`
- `app/Http/Controllers/Mayor/MentionsController.php`
- `app/Services/Communication/ContentGenerationService.php`
- `app/Services/Communication/CommunicationSettingsService.php`
- `app/Services/Social/SocialMonitorService.php`
- `app/Services/AI/ChatProactiveAlertService.php`

### Frontend

- `resources/views/mayor/content/index.blade.php`
- `resources/views/admin/municipalities/onboarding.blade.php`

### Rotas

- `routes/web.php`

### Persistencia principal

- `generated_contents`
- `metadata.editorial`
- `metadata.archive`
- `metadata.crisis`
- `metadata.generation_batch`
- `metadata.generation_session`
- `metadata.generation_log`

## 9. Validacoes Realizadas

Durante a conclusao foram validados tecnicamente:

- geração multicanal no fluxo real
- aplicacao do perfil de voz completo
- checagem historica antes da geração
- roteiro de crise evolutivo por seções
- reclassificacao manual de menções
- drag-and-drop do kanban operacional
- aprovacao configuravel por tipo e perfil
- bloqueio de publicacao por aprovacao correta
- aviso ao aprovador com link direto
- sessao de geração e trilha auditavel no arquivo
- remocao auditavel do arquivo
- alerta proativo de menções no `Meu Assistente`
- diagnostics limpos nos arquivos alterados nas iteracoes finais

## 10. Checklist Final do Que Ficou Pronto

- [x] shell unificado de `Comunicação`
- [x] `Produzir`
- [x] `Menções`
- [x] `Nucleo de Operacao`
- [x] `Arquivo`
- [x] templates por canal e formato
- [x] playbooks editoriais por situacao
- [x] refinamento assistido
- [x] novas variacoes editoriais
- [x] agenda e reordenacao editorial
- [x] visão mensal
- [x] inteligencia editorial
- [x] aprovacao colaborativa
- [x] SLA editorial por etapa
- [x] configuracao administrativa do SLA por município
- [x] geração multicanal e multitom
- [x] identidade de comunicação por município
- [x] contradicao historica pre-geração
- [x] roteiro de crise evolutivo
- [x] monitoramento de menções a cada 2 horas
- [x] correcao manual de sentimento
- [x] kanban operacional com drag-and-drop
- [x] aprovacao configuravel por tipo e perfil
- [x] notificacao ao aprovador com link direto
- [x] publicacao condicionada a aprovacao correta
- [x] histórico de versoes no arquivo
- [x] sessao formal de geração
- [x] trilha auditavel de geração
- [x] remocao auditavel do arquivo
- [x] memoria de crise
- [x] memoria de media training
- [x] reuso de item anterior
- [x] integracao com `Resolve ai`
- [x] integracao com `Meu Assistente`
- [x] alerta proativo de menções sensiveis no chat
- [x] diagnostics limpos nos arquivos finais alterados

## 11. Pendencias Residuais de Maturidade

- validar o módulo com operacao real de um município usando massa de dados do dia a dia
- acompanhar a calibragem da checagem historica com casos reais para reduzir falsos alertas
- observar o uso do fluxo de aprovacao configuravel por perfil em equipe com mais de um operador
- avaliar testes automatizados focados nos fluxos mais sensiveis do módulo

## 12. Status Final

### Status do módulo

- concluido no codigo
- aderente ao documento base no escopo principal
- configuravel por município
- operacional para uso real da equipe de comunicação

### Pendencia bloqueante

- nenhuma pendencia bloqueante identificada nesta etapa

## 13. Recomendacao de Encerramento

Para fins de projeto e continuidade da plataforma, o módulo `Comunicação` pode ser considerado finalizado no seu escopo principal.

Recomendacao objetiva:

- encerrar o módulo como entregue
- manter apenas pendencias residuais como maturidade operacional futura
- seguir para a proxima frente do plano da plataforma

## 14. Referencias

- plano vivo do módulo: `Plano-Modulo-Comunicação.md`
- auditoria final inicial: `Auditoria-Final-Modulo-Comunicação.md`
- controlador principal: `app/Http/Controllers/Mayor/ContentController.php`
- geração editorial: `app/Services/Communication/ContentGenerationService.php`
- shell do módulo: `resources/views/mayor/content/index.blade.php`
- configuracoes do módulo: `app/Services/Communication/CommunicationSettingsService.php`
