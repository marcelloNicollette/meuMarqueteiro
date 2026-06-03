# Plano do Modulo Comunicação

## 1. Objetivo

O módulo `Comunicação` organiza a geração, revisão, aprovacao, publicacao e reaproveitamento de conteudos políticos e institucionais do município.

Ele transforma fatos de governo, entregas, crises e agendas em pecas operacionais para:

- redes sociais
- discurso
- WhatsApp
- preparo de entrevista
- resposta a crise
- prompts de imagem com IA

## 2. Base Existente Encontrada

A base ja existente do módulo estava em:

- `routes/web.php` com rotas em `mayor/content`
- `app/Http/Controllers/Mayor/ContentController.php`
- `app/Services/Communication/ContentGenerationService.php`
- `app/Models/GeneratedContent.php`
- `resources/views/mayor/content/index.blade.php`

Essa base ja entregava:

- geração de post por IA
- preparacao de entrevista
- resposta a crise
- geração de prompts de imagem
- histórico basico por município
- persistencia em `generated_contents`

## 3. Estrutura de Dados

O módulo usa `generated_contents` como base central do ciclo editorial.

Campos relevantes ja existentes:

- `type`
- `channel`
- `tone`
- `title`
- `content`
- `variations`
- `status`
- `published_at`
- `published_url`
- `tags`
- `metadata`

Status previstos na modelagem:

- `draft`
- `approved`
- `published`
- `archived`

## 4. Lacunas Encontradas na Base Inicial

Antes desta rodada, o módulo ainda tinha algumas limitacoes:

- acesso ao conteudo sem endurecimento por município no `show/update/publish`
- `status` previstos na tabela, mas pouco usados na operacao real
- ausencia de acao explicita de aprovar
- ausencia de acao explicita de arquivar
- publicacao sem URL opcional de referencia
- interface ainda mais centrada em geração do que em operacao editorial
- filtros editoriais ainda inexistentes

## 5. Iteracao 1 Consolidada

Nesta primeira iteracao, o módulo foi elevado de gerador para operacao editorial inicial.

### Backend

- endurecimento de acesso por município para conteudos
- serializacao padronizada de payload para a interface
- filtros por:
    - `status`
    - `tipo`
    - `busca`
- resumo editorial por município
- acao de `approve`
- publicacao com `published_url` opcional
- acao de `archive`

### Frontend

- cards-resumo do ciclo editorial
- filtros operacionais no painel
- workflow editorial no detalhe do conteudo
- acoes para:
    - salvar edição
    - aprovar
    - publicar
    - arquivar
- leitura de origem do conteudo quando vier de outro módulo como `Resolve ai`

## 6. Iteracao 2 Consolidada

Na segunda iteracao, o módulo subiu para uma camada de governanca editorial e calendario operacional.

### Backend

- agenda editorial usando `metadata.editorial.planned_at`
- endpoint de agendamento por conteudo
- leitura da agenda semanal
- fila editorial priorizada
- mix por canal
- mix por origem
- destaque de conteudo pronto para publicar
- destaque de planejamento vencido

### Frontend

- remodelagem completa da area de `mayor/content`
- dashboard com cards executivos
- workspace amplo de geração e revisão
- fila editorial legivel
- calendario editorial da semana
- painel lateral substituido por blocos operacionais amplos
- agendamento direto no workflow do conteudo aberto

## 7. Escopo Atual do Modulo

No estado atual, o módulo `Comunicação` entrega:

- geração de conteudo social por IA
- geração de prompt de imagem para Instagram
- preparo de entrevista
- resposta a crise
- histórico de conteudos do município
- edição manual do conteudo gerado
- workflow editorial basico
- aprovacao e publicacao
- arquivamento
- agendamento editorial
- calendario semanal de publicacao
- fila editorial priorizada
- leitura por canal e origem
- integracao com `Resolve ai`

## 8. Iteracao 3 Consolidada

Na terceira iteracao, o módulo ganhou profundidade editorial com refinamento assistido e ampliacao de variacoes.

### Backend

- endpoint de refino de texto para pecas ja abertas
- endpoint de novas variacoes assistidas para pecas de comunicação
- reaproveitamento do perfil de voz do município e do canal da peca
- registro do histórico de acoes editoriais de IA em `metadata.editorial.history`
- serializacao do ultimo ajuste editorial para leitura na interface

### Frontend

- painel de `Refino Assistido` dentro do editor
- presets editoriais de ajuste rapido
- orientacao livre adicional para o refinamento
- refino do texto atual sem sair do workflow
- geração de novas variacoes a partir da peca aberta
- leitura da ultima acao editorial feita por IA

## 9. Proximo Bloco Mais Aderente

## 9. Iteracao 4 Consolidada

Na quarta iteracao, o módulo ganhou uma biblioteca editorial reutilizavel com templates por canal e formato.

### Backend

- nova tabela `content_templates` por município
- model `ContentTemplate` e relacionamento em `Municipality`
- endpoints para criar, atualizar e excluir templates
- aplicacao de template no gerador de comunicação
- aplicacao de template no gerador de imagem
- uso do formato editorial mesmo sem template salvo, para orientar a IA no momento da geração
- persistencia da referencia de template em `metadata` do conteudo gerado

### Frontend

- seletor de template dentro da aba de comunicação
- seletor de template dentro da aba de imagem
- biblioteca de templates ao lado da fila editorial
- criacao de template a partir da configuracao atual da aba
- acao de aplicar template direto da biblioteca
- acao de excluir template sem sair do workspace

## 10. Iteracao 5 Consolidada

Na quinta iteracao, o módulo ganhou uma camada de planejamento mais forte com visão mensal, reordenacao da agenda e leitura comparativa do desempenho editorial.

### Backend

- calendario mensal derivado da agenda ja existente
- ordenacao da agenda por `metadata.editorial.sequence`
- endpoint de reordenacao para subir e descer pecas dentro do mesmo dia
- atribuicao automatica de sequencia ao agendar uma nova peca
- leitura editorial dos ultimos 30 dias por:
    - canal
    - tipo
    - origem
- totais de criados, agendados e publicados na janela recente

### Frontend

- calendario semanal com controles de subir e descer por peca agendada
- visão mensal do planejamento logo abaixo da agenda quente
- bloco de inteligencia editorial com comparativos por origem, tipo e canal
- recarga orientada da tela apos reordenacao e reagendamento para manter a leitura consistente

## 11. Iteracao 6 Consolidada

Na sexta iteracao, o módulo ganhou uma camada de aprovacao colaborativa com observacoes, mantendo o fluxo editorial no mesmo workspace.

### Backend

- evolucao da aprovacao simples para aprovacao com nota opcional
- novo endpoint de colaboracao editorial por conteudo
- registro de:
    - observacao
    - pedido de ajuste
    - aprovacao com observacao
- persistencia do histórico colaborativo em `metadata.editorial.collaboration`
- serializacao do resumo colaborativo e da trilha de revisão para a interface

### Frontend

- legenda curta do semaforo na `Inteligencia Editorial`
- painel de colaboracao dentro de `Editor e Revisão`
- campo de observacao para registrar contexto da revisão
- acoes rápidas para:
    - registrar observacao
    - pedir ajuste
    - aprovar com observacao
- trilha de revisão com nome, papel, tipo de acao e momento do registro

## 12. Iteracao 7 Consolidada

Na setima iteracao, o módulo ganhou SLA editorial por etapa para fechar o ciclo operacional entre colaboracao, fila, agenda e publicacao.

### Backend

- leitura de configuracao de SLA por município em `settings.communication.sla`
- defaults operacionais para:
    - revisão inicial
    - aprovacao pronta para publicar
    - antecedencia da execução agendada
- snapshot de SLA por conteudo serializado junto da peca
- identificação automatica da etapa ativa:
    - `draft_review`
    - `approved_release`
    - `scheduled_execution`
    - `completed`
- classificacao operacional por status de SLA:
    - `on_track`
    - `at_risk`
    - `overdue`
    - `complete`
- board agregado com:
    - totais ativos
    - vencidos
    - em risco
    - dentro do SLA
    - taxa de publicacao no prazo nos ultimos 30 dias
- fila editorial reordenada considerando prioridade de SLA, vencimento e agenda

### Frontend

- secao executiva `SLA Editorial por Etapa` no topo da operacao
- cards-resumo de:
    - vencidos agora
    - em risco
    - dentro do SLA
    - taxa de publicacao no prazo
- leitura por etapa com top pecas para ataque operacional imediato
- `Fila Critica de Vencimento` com acesso direto ao conteudo afetado
- badges de SLA na `Fila Editorial`
- contexto de SLA dentro do `Workflow editorial` da peca aberta

## 13. Iteracao 8 Consolidada

Na oitava iteracao, o módulo ganhou playbooks editoriais por situacao para fechar a execução diaria entre geração, revisão, SLA e publicacao.

### Backend

- biblioteca base de playbooks operacionais por situacao dentro do proprio módulo
- resolucao de playbook por aba operacional:
    - `post`
    - `interview`
    - `crisis`
- aplicacao do playbook direto na geração de comunicação, entrevista e crise
- injecao do contexto do playbook no prompt da IA com:
    - descrição da situacao
    - guia fixo
    - checklist operacional
    - fluxo sugerido
- persistencia da referencia do playbook em `metadata.playbook` do conteudo gerado
- serializacao do playbook para leitura no editor e revisão

### Frontend

- nova secao full width `Playbooks Editoriais por Situacao`
- cards operacionais com:
    - situacao
    - canal ou formato sugerido
    - checklist resumido
    - acao direta de aplicar playbook
- seletores de playbook nas abas:
    - `Comunicação`
    - `Entrevista`
    - `Crise`
- preenchimento guiado de contexto, canal, formato e tons conforme o playbook escolhido
- leitura do playbook ativo dentro do `Workflow editorial` da peca aberta

## 14. Iteracao 9 Consolidada

Na nona iteracao, o módulo passou a tratar `Menções` como uma frente operacional aderente ao documento base, reaproveitando a estrutura existente de monitoramento em vez de criar um bloco paralelo.

### Backend

- ampliacao da classificacao automatica para:
    - `positive`
    - `neutral`
    - `negative`
    - `urgent`
- termometro reputacional derivado das menções do periodo filtrado
- filtro de fonte exposto a partir das origens reais capturadas
- suporte a cadastro manual de menção para:
    - WhatsApp
    - rede social manual
    - portal manual
    - origem manual genérica
- classificacao automatica imediata da menção manual salva
- alerta operacional para menções negativas e urgentes
- deep link de menção urgente para o fluxo de `Crise` no módulo `Comunicação`
- cadencia automatica do monitoramento ajustada para 2 horas

### Frontend

- tela `Menções` reposicionada como frente do módulo `Comunicação`
- termometro de reputacao no topo com leitura proporcional por classe
- KPI separado para menções urgentes
- filtro por fonte além do filtro por classe e periodo
- CTA `Abrir crise` para menções negativas e urgentes
- bloco de registro manual de menções para fontes não automatizaveis
- atualizacao da comunicação visual da configuracao para refletir a janela de 2 horas

## 15. Iteracao 10 Consolidada

Na decima iteracao, o módulo passou a ser reorganizado como um shell unico em `mayor/content`, alinhando a arquitetura da interface ao desenho funcional do documento base.

### Estrutura do módulo

- `Produzir` permanece como a frente editorial principal dentro de `mayor/content`
- `Menções` deixa de ser tratada como entrada paralela e passa a ser uma area interna do módulo
- `Nucleo de Operacao` passa a ter espaco proprio no shell, preparado para receber a base operacional na proxima rodada
- `Arquivo` ganha area dedicada dentro do mesmo módulo para memoria editorial e reabertura de pecas

### Backend

- `ContentController` passa a resolver a area ativa do módulo via query string:
    - `produce`
    - `mentions`
    - `operations`
    - `archive`
- agregacao de payload de `Menções` direto no shell do módulo
- agregacao inicial de leitura do `Arquivo` com base em `generated_contents`
- rota antiga de `Menções` preservada apenas como ponte para `mayor/content?area=mentions`

### Frontend

- criacao da navegacao macro do módulo `Comunicação` dentro de `mayor/content`
- `Menções` incorporada ao workspace principal com:
    - termometro
    - filtros
    - lista operacional
    - curadoria manual
- `Arquivo` incorporado ao mesmo módulo com:
    - leitura resumida
    - filtros
    - histórico recente
    - reabertura via editor
- item lateral `Menções` removido como entrada separada para reforcar `Comunicação` como módulo guarda-chuva

## 16. Iteracao 11 Consolidada

Na decima primeira iteracao, o `Nucleo de Operacao` deixou de ser apenas um espaco reservado no shell e passou a funcionar como a central operacional da equipe de comunicação dentro de `mayor/content`.

### Base reutilizada

- reaproveitamento da estrutura de `Demandas` como espinha dorsal da pauta
- uso dos mesmos campos de:
    - prioridade
    - prazo
    - pasta responsável
    - solicitante
    - localidade
    - eventos do fluxo
- sem criacao de módulo paralelo para a equipe de comunicação

### Backend

- leitura operacional agregada dentro do `ContentController`
- sincronizacao de demandas vencidas para refletir `overdue` na pauta
- serializacao operacional das demandas com:
    - tipo de pauta inferido
    - coluna do kanban
    - origem da demanda
    - canal sugerido
    - hint operacional de recurso ou providencia
- filtros do `Nucleo de Operacao` por:
    - tipo de pauta
    - pasta responsável
    - prioridade
    - periodo
    - busca textual
- quadro kanban com cinco colunas:
    - `Entrada`
    - `Em planejamento`
    - `Em producao`
    - `Em aprovacao`
    - `Concluida`
- bloco de `Sugestoes Resolve ai` com demandas prontas para virar conteudo
- leitura de prazos curtos e atividade recente a partir de `DemandEvent`

### Frontend

- area `Nucleo de Operacao` detalhada dentro do shell do módulo `Comunicação`
- cards-resumo para:
    - entrada
    - planejamento
    - producao
    - aprovacao
    - concluidas
    - atrasadas
    - sugestoes Resolve ai
- quadro kanban visual da pauta operacional
- cards de demanda com:
    - titulo
    - tipo
    - prioridade
    - status
    - pasta responsável
    - origem
    - prazo
    - canal sugerido
    - hint operacional
- integracao direta para:
    - abrir demanda no fluxo detalhado
    - gerar rascunho em `Comunicação`
    - abrir narrativa no `Meu Assistente`
- paines laterais de:
    - sugestoes Resolve ai
    - prazos e cobertura
    - atividade recente
    - mix da pauta

## 17. Iteracao 12 Consolidada

Na decima segunda iteracao, o `Arquivo` foi aprofundado dentro do shell do módulo `Comunicação`, fechando a memoria institucional prevista no documento base.

### Backend

- filtros do `Arquivo` expandidos para:
    - status
    - tipo
    - canal
    - tom
    - perfil que criou
    - periodo
    - busca por palavra-chave
- `GeneratedContent` serializado com leitura adicional de arquivo:
    - criador
    - perfil do criador
    - label de canal
    - label de tom
    - histórico de versoes
    - memoria arquivada
- suporte a gravacao de memoria em `metadata.archive` com:
    - `reference_note`
    - `outcome_note`
    - `updated_at`
    - `updated_by`
- preparo de `reuse seed` para reabrir item anterior como ponto de partida em `Produzir`
- consolidacao de blocos de memoria tematica para:
    - crise
    - media training

### Frontend

- hero do `Arquivo` ajustado para refletir total de versoes no recorte
- filtros do `Arquivo` alinhados ao documento
- cards do histórico recente com:
    - tipo
    - canal
    - tom
    - criador
    - perfil
    - total de versoes
    - CTA de reuso
- `Editor e Revisão` do `Arquivo` passa a mostrar:
    - painel de memoria institucional
    - lista de versoes do item
    - painel de memoria aplicada
- memoria de crise com atalhos para abrir ou reutilizar roteiro anterior
- memoria de media training com reuso direto de briefing anterior
- reuso de item anterior conectado ao `Produzir` nas abas:
    - `Comunicação`
    - `Entrevista`
    - `Crise`
    - `Imagem IA`

## 18. Proximo Bloco Mais Aderente

Depois desta rodada, o bloco mais aderente passa a ser a revisão final do módulo `Comunicação` contra o documento, fechando:

1. checklist funcional ponta a ponta
2. consistencia entre `Produzir`, `Menções`, `Nucleo de Operacao` e `Arquivo`
3. acabamentos residuais de usabilidade
4. validacao final do módulo como encerrado

## 19. Status

### Status atual

- módulo existente e reutilizado com sucesso
- base editorial inicial consolidada
- shell do módulo unificado em `mayor/content` como ponto central de `Produzir`, `Menções`, `Nucleo de Operacao` e `Arquivo`
- frente `Menções` agora aderente ao documento com urgência, reputacao, filtro por fonte, curadoria manual e disparo para crise
- `Nucleo de Operacao` agora funcional dentro do módulo com quadro kanban, filtros, sugestoes Resolve ai e leitura de prazos/atividade
- fluxo operacional agora coberto por producao editorial, colaboracao, fila, agenda, aprovacao, SLA por etapa, playbooks por situacao e menções
- `Arquivo` agora cobre filtros aderentes ao documento, histórico de versoes, reuso e memoria de crise/media training

### Pendencia bloqueante

- pendentes estruturais do documento:
    - revisão final de fechamento do módulo completo
