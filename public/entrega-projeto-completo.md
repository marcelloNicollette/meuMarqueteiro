# Entrega Consolidada do Projeto Meu Marqueteiro

## Objetivo deste arquivo

Este arquivo consolida os principais documentos de entrega tecnica da plataforma `Meu Marqueteiro` em um unico ponto de consulta dentro de `public`.

Ele serve para consumo rapido por pessoas, IA, integracoes ou qualquer fluxo que precise entender como o projeto funciona de ponta a ponta.

## Visao Geral da Plataforma

A plataforma esta organizada em modulos que se complementam:

- `Meu Marqueteiro`: assistente central com chat, memoria, audio, exportacao e compartilhamento.
- `Comunicacao`: producao editorial, monitoramento de mencoes, pauta operacional e arquivo institucional.
- `Resolve ai`: operacao territorial de demandas, cobranca, notificacao, historico e governanca por secretaria.
- `Mandato`: traducao do plano de governo em compromissos, acoes, progresso e leitura executiva.
- `Projetos`: elaboracao guiada, governanca editorial, revisoes e exportacao institucional de projetos.
- `Radar de Recursos`: descoberta, qualificacao, curadoria e operacao de oportunidades de captacao.

## Como o projeto inteiro funciona

Em termos funcionais, o fluxo consolidado da plataforma e este:

1. O prefeito e a equipe usam `Meu Marqueteiro` como entrada conversacional para orientacao, memoria e acao.
2. O `Resolve ai` transforma problemas e demandas do territorio em execucao rastreavel por secretaria.
3. O `Mandato` conecta a operacao real aos compromissos do plano de governo e mede cumprimento.
4. O modulo de `Projetos` estrutura propostas institucionais completas com revisao, aprovacao e exportacao.
5. O `Radar de Recursos` identifica oportunidades de captacao para destravar entregas e projetos.
6. O modulo de `Comunicacao` transforma entregas, fatos de governo, crises e pautas em conteudo publicavel.
7. As integracoes entre modulos permitem reaproveitar contexto, acelerar resposta e manter memoria institucional.

## Documentos consolidados

Os conteudos abaixo foram agregados integralmente a partir destes arquivos:

- `Entrega-Tecnica-Resumo-Executivo-Meu-Marqueteiro.md`
- `Entrega-Tecnica-Resumo-Executivo-Comunicacao.md`
- `Entrega-Tecnica-Resumo-Executivo-Resolve-ai.md`
- `Entrega-Tecnica-Resumo-Executivo-Mandato.md`
- `Entrega-Tecnica-Resumo-Executivo-Modulo-Projetos.md`
- `Entrega-Tecnica-Resumo-Executivo-Radar-de-Recursos.md`

---

## Fonte: `Entrega-Tecnica-Resumo-Executivo-Meu-Marqueteiro.md`

# Entrega Tecnica e Resumo Executivo - Modulo Meu Marqueteiro

## 1. Resumo Executivo

O módulo `Meu Marqueteiro` foi concluido como nucleo conversacional da plataforma, preservando a identidade do assistente politico e ampliando seu papel para operar de forma integrada aos dados do município, do mandato e dos demais módulos.

No estado atual, o módulo entrega:

- chat central com histórico de conversas
- memoria ativa entre sessoes
- organização automatica e manual de tags
- filtros de histórico por tag, busca e periodo
- sugestao automatica de exportacao
- compartilhamento seletivo de trechos
- alertas proativos de riscos, prazos e oportunidades
- entrada e saida por audio com estrategia hibrida entre navegador e servidor

Do ponto de vista de produto, o módulo esta pronto para operacao no sistema.

Do ponto de vista operacional, existe apenas uma observacao remanescente: a validacao funcional final do fallback server-side de audio depende de quota ativa da conta OpenAI configurada no ambiente.

## 2. Objetivo do Modulo

O `Meu Marqueteiro` funciona como assistente pessoal do prefeito dentro da plataforma, com foco em orientacao política, comunicação publica, leitura de contexto do mandato, apoio tatico e aproveitamento de informacoes ja disponiveis em outros módulos.

O objetivo principal do módulo e:

- centralizar interacoes estrategicas do usuario em formato conversacional
- recuperar contexto anterior da propria conversa
- ampliar a qualidade da resposta com dados do município e do mandato
- transformar conversas em acao pratica, conteudo reaproveitavel ou compartilhamento seletivo

## 3. Escopo Entregue

### 3.1 Chat e experiencia principal

- interface principal de chat com histórico por conversa
- criacao de novas conversas
- carregamento de conversa ativa
- organização visual refinada para melhor uso do espaco
- alertas proativos reposicionados em lateral direita compacta

### 3.2 Metadados e organização de conversa

- titulo automatico por conversa
- tags automaticas por heuristica de assunto
- intencao principal da conversa
- resumo operacional em contexto
- edição manual de tags na conversa ativa
- filtro do histórico por tag, texto, compartilhamentos ativos e periodo

### 3.3 Memoria ativa

- memoria vetorial por conversa
- memoria resumida persistida no contexto
- recuperacao semantica de memorias relevantes
- fallback seguro quando embeddings falham ou retornam pouco contexto
- badge visual de memoria ativa na resposta do assistente

### 3.4 Exportacao

- sugestao automatica de exportacao quando a resposta e reaproveitavel
- envio do conteudo para o módulo de comunicação
- protecao contra duplicidade por mensagem de origem

### 3.5 Compartilhamento seletivo

- compartilhamento de trecho de mensagem
- destinatarios elegiveis com regra de acesso
- tela dedicada para visualizacao de trecho compartilhado
- histórico de compartilhamentos
- revogacao segura sem apagar o histórico
- indicadores visuais no chat e no histórico de conversas
- visão geral global de compartilhamentos

### 3.6 Audio

- entrada por voz no navegador com transcricao nativa quando houver suporte
- saida por voz no navegador com leitura nativa quando houver suporte
- persistencia de preferencias de audio por usuario
- player por mensagem
- replay da ultima resposta
- indicador visual de resposta lida
- fila simples de leitura
- velocidade de reproducao ajustavel
- fallback server-side para STT/TTS
- cache temporario de audio
- armazenamento temporario de audio de entrada
- configuracao administrativa explicita de voz, modelo e TTL
- diagnostico administrativo do audio fallback
- limpeza agendada dos temporarios

## 4. Principais Entregas Tecnicas

### Backend

- controlador central do módulo em `app/Http/Controllers/ChatController.php`
- servico principal do assistente em `app/Services/AI/AssistantService.php`
- memoria vetorial em `app/Services/AI/ConversationMemoryService.php`
- metadados da conversa em `app/Services/AI/ConversationMetadataService.php`
- alertas proativos em `app/Services/AI/ChatProactiveAlertService.php`
- sugestao de exportacao em `app/Services/AI/ConversationExportSuggestionService.php`
- audio server-side em `app/Services/AI/ChatAudioService.php`

### Frontend

- interface principal do chat em `resources/views/mayor/chat/index.blade.php`
- tela de compartilhamento seguro em `resources/views/mayor/chat/shared.blade.php`
- painel administrativo de configuracao de IA em `resources/views/admin/settings/index.blade.php`
- painel administrativo de diagnostico em `resources/views/admin/diagnostic/index.blade.php`

### Persistencia e dados

- conversa e mensagens em `database/migrations/2024_01_01_000009_create_conversations_and_messages_table.php`
- memoria vetorial em `database/migrations/2026_05_14_000002_create_conversation_memories_table.php`
- compartilhamentos em `database/migrations/2026_05_14_000003_create_message_shares_table.php`
- revogacao de compartilhamento em `database/migrations/2026_05_15_000004_add_revocation_fields_to_message_shares_table.php`

### Operacao

- agendamento consolidado em `app/Console/ScheduleRegistrar.php`
- limpeza de audio temporario em `app/Console/Commands/PruneChatAudioCache.php`

## 5. Fluxos Principais Entregues

### Fluxo 1: conversa com memoria

1. usuario envia mensagem
2. sistema recupera contexto e memorias relevantes
3. assistente responde com contexto operacional
4. memoria usada e registrada em metadata
5. conversa recebe metadados atualizados

### Fluxo 2: resposta exportavel

1. assistente gera resposta conclusiva
2. sistema sugere exportacao
3. usuario salva no módulo de conteudo
4. sistema evita duplicidade

### Fluxo 3: compartilhamento seletivo

1. usuario seleciona trecho de uma mensagem
2. sistema valida o trecho
3. trecho e compartilhado com destinatario elegivel
4. histórico fica visivel para o dono
5. acesso pode ser revogado com seguranca

### Fluxo 4: audio progressivo

1. navegador usa STT/TTS nativo quando suportado
2. sem suporte nativo, frontend usa fallback server-side
3. preferencias do usuario permanecem as mesmas
4. UX do chat e mantida com os mesmos controles

## 6. Validacoes Realizadas

Durante a entrega foram validados tecnicamente:

- renderizacao do chat
- fluxo de mensagens
- recuperacao e exibicao de memoria ativa
- exportacao sem duplicidade
- compartilhamento e revogacao
- filtros e indicadores de compartilhamento
- ajuste visual dos alertas proativos
- controles de audio no frontend
- rotas de audio server-side
- cache tecnico de audio
- scheduler com limpeza horaria de temporarios
- edição manual de tags
- filtro por periodo no histórico

Tambem foi confirmado no ambiente:

- configuracao real do fallback de audio com provider carregado
- chave OpenAI presente no sistema

## 7. Ponto de Atencao Operacional

O unico item não encerrado como validacao pratica ponta a ponta foi o teste real de STT/TTS server-side no provider externo, porque a conta OpenAI configurada retornou erro de quota.

Isso significa:

- o codigo do módulo esta concluido
- as rotas e servicos estao prontos
- a configuracao do ambiente esta carregando corretamente
- a validacao final do audio depende apenas de restabelecer credito/quota no provider

## 8. Status Final

### Status do módulo

- concluido no codigo
- pronto para operacao do nucleo funcional
- pronto para handoff ao proximo módulo

### Pendencia remanescente

- validacao funcional final do fallback de audio server-side apos reativacao de quota da OpenAI

## 9. Recomendacao de Encerramento

Para fins de projeto e continuidade da plataforma, o módulo `Meu Marqueteiro` pode ser considerado finalizado.

Recomendacao objetiva:

- encerrar este módulo como entregue
- registrar a observacao operacional do audio como dependencia externa
- seguir para o proximo módulo da plataforma

## 10. Referencias

- documento de estado atual: `Meu-Marqueteiro-Estado-Atual.md`
- referencia funcional original: `módulo-chat.txt`
- diagnostico de planejamento: `Diagnóstico-chat.txt`

---

## Fonte: `Entrega-Tecnica-Resumo-Executivo-Comunicacao.md`

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
- integracao com `Meu Marqueteiro`

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
- integracao com narrativa no `Meu Marqueteiro`

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
- `Meu Marqueteiro` para abrir narrativa estrategica
- `Meu Marqueteiro` com alerta proativo de menções urgentes e negativas

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

### R10 - Integracao com Resolve ai e Meu Marqueteiro

- [x] atendido

Como foi fechado:

- `Resolve ai` integrado ao `Nucleo de Operacao` e a geração de rascunho
- `Meu Marqueteiro` integrado a narrativa e a alertas proativos de menções sensiveis

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
5. abrir narrativa no `Meu Marqueteiro` quando a pauta pedir articulacao política

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

### 7.12 Usar a integracao com `Meu Marqueteiro`

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
- alerta proativo de menções no `Meu Marqueteiro`
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
- [x] integracao com `Meu Marqueteiro`
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

---

## Fonte: `Entrega-Tecnica-Resumo-Executivo-Resolve-ai.md`

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

---

## Fonte: `Entrega-Tecnica-Resumo-Executivo-Mandato.md`

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
- integracao por leitura com `Meu Marqueteiro`
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

- `Meu Marqueteiro`
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

#### Meu Marqueteiro

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
- leitura do `Mandato` no `Meu Marqueteiro`
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
- [x] integracao por leitura com `Meu Marqueteiro`
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

---

## Fonte: `Entrega-Tecnica-Resumo-Executivo-Modulo-Projetos.md`

# Entrega Tecnica e Resumo Executivo - Modulo Projetos

## 1. Resumo Executivo

O módulo `Projetos` foi concluido como frente estruturada de elaboração, colaboracao, governanca editorial e exportacao institucional de documentos de projeto dentro da plataforma.

No estado atual, o módulo entrega:

- criacao e manutencao de projetos com dados centrais e metadados estruturados
- fluxo de perguntas dinâmicas antes da geração do documento
- geração de documento com 15 seções obrigatórias
- verificação de sobreposição com projetos existentes
- identificação de programas e oportunidades de financiamento compatíveis
- colaboracao por convite com permissoes `viewer` e `editor`
- histórico de edições e eventos relevantes do projeto
- edição manual de seções e metadados conforme regra de acesso
- revisoes documentais com comparacao entre versoes
- workflow editorial com `draft`, `approved` e `published`
- aprovacao formal por etapas com responsaveis distintos
- assinatura nominal e trilha institucional de publicacao
- exportacao final em `PDF` e `DOCX`

Do ponto de vista de produto, o módulo esta pronto para operacao no sistema.

Do ponto de vista tecnico, o fluxo principal foi fechado, validado e consolidado sem pendencia bloqueante para seguir ao proximo módulo.

## 2. Objetivo do Modulo

O `Projetos` funciona como módulo de elaboração e consolidacao de projetos institucionais, permitindo sair de uma idéia inicial para um documento formal estruturado, revisado, auditavel e exportavel.

O objetivo principal do módulo e:

- transformar a idéia base em um projeto guiado por perguntas dinâmicas
- gerar um documento completo com estrutura obrigatória e sem lacunas
- apoiar a verificação previa de conflito e viabilidade de financiamento
- permitir colaboracao controlada por papeis
- manter governanca de revisão, aprovacao e publicacao institucional

## 3. Escopo Entregue

### 3.1 Criacao e estrutura do projeto

- criacao de projeto com dados centrais
- definicao de tipo, status, secretaria responsável e fase atual
- persistencia de metadados estruturados complementares
- estrutura obrigatória de 15 seções documentais

### 3.2 Questionario dinamico e geração do documento

- perguntas dinâmicas montadas conforme o tipo de projeto
- regeneracao do questionario quando a base do projeto muda
- salvamento das respostas do questionario
- geração do documento completo a partir do contexto do projeto
- fallback seguro quando a IA não responde como esperado

### 3.3 Analises de apoio

- verificação de sobreposição com outros projetos ja existentes
- leitura de risco de conflito entre propostas
- analise de fontes e programas de financiamento compatíveis
- blindagem de dados faltantes no matching de programas

### 3.4 Colaboracao e permissoes

- convite de colaborador por projeto
- aceite de convite pelo usuario convidado
- papeis de colaboracao `viewer` e `editor`
- permissao real de edição apenas para perfis autorizados
- badges e filtros por papel no contexto do módulo
- registro de bloqueios de permissao no histórico

### 3.5 Edição estruturada e histórico

- edição manual de seções do documento
- edição estruturada de metadados operacionais
- bloqueio dos dados centrais para quem não e proprietario
- histórico de eventos do projeto com exibicao enxuta e expansivel
- preservacao do setor da tela apos envio de formularios

### 3.6 Revisoes, comparacao e restauracao

- criacao de revisoes documentais com snapshot
- comparacao de revisão contra anterior
- comparacao entre rascunho atual e versão final publicada
- restauracao de revisão historica
- selecao padrao da revisão relevante para o usuario
- consolidacao de uma unica revisão ativa de trabalho

### 3.7 Workflow editorial e governanca

- estados `draft`, `approved` e `published`
- abertura formal de novo rascunho a partir da versão final
- bloqueio de edição quando existe versão final publicada sem rascunho ativo
- aprovacao formal por etapas
- definicao de responsaveis por etapa com filtros elegiveis no backend
- conclusao de etapa pelo responsável correto
- aprovacao da revisão com motivo formal
- publicacao da versão final com motivo formal e assinatura nominal
- trilha institucional de substituicao da versão final anterior

### 3.8 Exportacao institucional

- exportacao em `PDF` por biblioteca dedicada
- exportacao em `DOCX` por biblioteca dedicada
- opcao de exportar apenas a revisão publicada
- inclusao de assinatura final e auditoria formal nas exportacoes
- resumo oficial da versão final vigente e histórico de publicacoes

## 4. Principais Entregas Tecnicas

### Backend

- controlador central em `app/Http/Controllers/Mayor/ProjectController.php`
- geração e governanca de revisoes em `app/Services/Projects/ProjectRevisionService.php`
- geração do documento em `app/Services/Projects/ProjectDocumentGenerationService.php`
- fluxo de perguntas em `app/Services/Projects/ProjectQuestionFlowService.php`
- estrutura documental em `app/Services/Projects/ProjectStructureService.php`
- analise de sobreposição em `app/Services/Projects/ProjectOverlapAnalysisService.php`
- analise de financiamento em `app/Services/Projects/ProjectFundingMatchService.php`
- dados de exportacao em `app/Services/Projects/ProjectExportService.php`
- exportacao `DOCX` em `app/Services/Projects/ProjectDocxExportService.php`

### Frontend

- tela principal do módulo em `resources/views/mayor/projects/show.blade.php`
- listagem de projetos em `resources/views/mayor/projects/index.blade.php`
- exportacao `PDF` em `resources/views/mayor/projects/exports/pdf.blade.php`
- exportacao `Word/HTML` em `resources/views/mayor/projects/exports/word.blade.php`

### Persistencia e dados

- estrutura base do módulo em `database/migrations/2026_05_18_000001_create_projects_module_tables.php`
- base das perguntas dinâmicas em `database/migrations/2026_05_18_000002_create_project_intake_questions_table.php`
- revisoes do documento em `database/migrations/2026_05_18_000003_create_project_document_revisions_table.php`
- workflow editorial em `database/migrations/2026_05_18_000004_add_revision_workflow_columns_to_project_document_revisions_table.php`
- etapas de aprovacao em `database/migrations/2026_05_18_000005_add_approval_steps_to_project_document_revisions_table.php`
- campos de auditoria em `database/migrations/2026_05_18_000006_add_audit_fields_to_project_document_revisions_table.php`

## 5. Fluxos Principais Entregues

### Fluxo 1: elaboração guiada do projeto

1. usuario cria o projeto com dados centrais
2. sistema gera perguntas dinâmicas conforme o tipo
3. usuario responde o questionario
4. sistema gera o documento nas 15 seções obrigatórias
5. projeto permanece salvo com histórico e metadados estruturados

### Fluxo 2: verificação tecnica antes do fechamento

1. usuario roda analise de sobreposição
2. sistema aponta possiveis conflitos com outros projetos
3. usuario roda analise de financiamento
4. sistema lista programas compatíveis
5. time pode ajustar metadados e seções antes da consolidacao

### Fluxo 3: colaboracao controlada

1. proprietario convida colaborador
2. colaborador aceita o convite
3. sistema aplica papel `viewer` ou `editor`
4. apenas perfis autorizados conseguem editar
5. bloqueios e acoes ficam registrados no histórico

### Fluxo 4: revisão e publicacao institucional

1. sistema registra revisão com snapshot do documento
2. responsaveis sao definidos por etapa de aprovacao
3. cada etapa e concluida pelo responsável elegivel
4. revisão e aprovada com motivo formal
5. versão final e publicada com assinatura nominal e auditoria

### Fluxo 5: ciclo fechado de versão final

1. ao existir versão final publicada, a edição comum e bloqueada
2. proprietario abre novo rascunho quando quiser retomar o trabalho
3. rascunho e comparado com a versão final vigente
4. nova publicacao substitui a versão final anterior
5. exportacoes institucionais passam a refletir a versão oficial vigente

## 6. Validacoes Realizadas

Durante a entrega foram validados tecnicamente:

- correcao do erro no `CTA` de regeneracao relacionado a `description`
- correcao de erros de parse na tela principal do projeto
- filtros e comportamento de permissao para `owner`, `editor` e `viewer`
- conclusao de etapas por responsável correto
- aprovacao e publicacao da revisão correta
- selecao consistente da revisão ativa
- bloqueio de revisoes antigas fora do fluxo ativo
- consolidacao da versão final vigente
- recompilacao de views com `php artisan view:cache`
- diagnostics sem erros nos arquivos editados
- exportacao final com trilha institucional e resumo oficial de publicacao

Tambem foi validado no ambiente:

- projeto `1` com fluxo real de colaboracao e aprovacao por etapas
- comportamento distinto entre proprietario e colaborador editor
- simulacao transacional do comportamento `viewer` sem persistir dados de teste

## 7. Checklist Final do Que Ficou Pronto

- [x] criacao do projeto com dados centrais
- [x] perguntas dinâmicas por tipo de projeto
- [x] geração do documento com 15 seções
- [x] fallback seguro quando a IA falha
- [x] analise de sobreposição
- [x] analise de financiamento
- [x] colaboracao por convite
- [x] papeis `viewer` e `editor`
- [x] histórico de edições visivel
- [x] edição manual de seções
- [x] edição estruturada de metadados
- [x] exportacao `PDF`
- [x] exportacao `DOCX`
- [x] versionamento por revisão
- [x] comparacao entre revisoes
- [x] restauracao de revisão
- [x] workflow `draft / approved / published`
- [x] aprovacao formal por etapas
- [x] responsaveis distintos por etapa
- [x] assinatura final de publicacao
- [x] auditoria formal nas exportacoes
- [x] bloqueio backend de revisoes antigas
- [x] consolidacao da versão final vigente

## 8. Pendencias Opcionais Futuras

- adicionar testes automatizados mais focados para o workflow de revisão e publicacao
- exibir filtros mais granulares no histórico do projeto, caso o volume operacional cresca
- ampliar a modelagem de perfil institucional dos responsaveis, caso a prefeitura queira separar funcoes formais alem de `owner/editor/viewer`

## 9. Status Final

### Status do módulo

- concluido no codigo
- validado no fluxo principal
- pronto para operacao do nucleo funcional
- pronto para handoff ao proximo módulo

### Pendencia bloqueante

- nenhuma pendencia bloqueante identificada nesta etapa

## 10. Recomendacao de Encerramento

Para fins de projeto e continuidade da plataforma, o módulo `Projetos` pode ser considerado finalizado no seu escopo principal.

Recomendacao objetiva:

- encerrar este módulo como entregue
- registrar apenas as pendencias futuras como evolucoes opcionais
- seguir para o proximo módulo da plataforma

## 11. Referencias

- referencia funcional original: `qu4tro.ai - MÓDULO Projetos.docx`
- tela principal do módulo: `resources/views/mayor/projects/show.blade.php`
- controlador principal: `app/Http/Controllers/Mayor/ProjectController.php`
- servico de revisoes: `app/Services/Projects/ProjectRevisionService.php`

---

## Fonte: `Entrega-Tecnica-Resumo-Executivo-Radar-de-Recursos.md`

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
