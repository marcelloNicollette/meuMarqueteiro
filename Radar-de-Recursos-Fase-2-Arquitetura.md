# Radar de Recursos - Fase 2

Data: 2026-05-19

## Objetivo

Criar a camada canonica de dados do `Radar de Recursos` sem romper a operacao atual baseada em `federal_program_alerts`.

## Estrategia de transicao

- `federal_program_alerts` continua como camada transitória e município-especifica
- novas tabelas passam a representar o dominio canonico do módulo
- a leitura atual pode continuar funcionando enquanto a ingestao e a UI migram gradualmente
- a migracao de dados do legado para o novo modelo fica desacoplada e pode ser feita por jobs/comandos depois

## Desenho das tabelas

### `resource_opportunities`

Representa a oportunidade canonica, independente do município.

Campos centrais:

- `resource_source_id`
- `canonical_key`
- `source_fingerprint`
- `title`
- `short_title`
- `official_title`
- `issuing_body`
- `thematic_area`
- `resource_type`
- `funding_type`
- `resource_scope`
- `summary`
- `description`
- `eligibility_rules`
- `documentation_requirements`
- `counterpart_rules`
- `estimated_size`
- `source_url`
- `curation_status`
- `latest_status`
- `compatibility_factors_template`
- `viability_factors_template`
- `source_metadata`

Papel:

- centralizar identidade e metadados estaveis da oportunidade
- servir de pai para ciclos, saves, fila de curadoria e notificacoes

### `resource_opportunity_cycles`

Representa cada abertura, edição, republicacao ou ciclo operacional da oportunidade.

Campos centrais:

- `resource_opportunity_id`
- `external_cycle_key`
- `publication_reference`
- `status`
- `is_current`
- `notice_url`
- `application_url`
- `published_at`
- `opens_at`
- `deadline_at`
- `closed_at`
- `closed_visibility_until`
- `reopened_from_cycle_id`
- `total_value`
- `min_value`
- `counterpart_percentage`
- `estimated_size`
- `cycle_metadata`

Papel:

- separar vida util do edital do cadastro canonico da oportunidade
- suportar reabertura, histórico e regra de 60 dias para encerrados

### `resource_curation_queue`

Fila operacional de validacao humana.

Campos centrais:

- `resource_opportunity_id`
- `resource_opportunity_cycle_id`
- `resource_source_id`
- `municipality_id`
- `queue_status`
- `priority`
- `assigned_to_user_id`
- `reviewed_by_user_id`
- `source_payload_snapshot`
- `enrichment_payload`
- `decision_notes`
- `entered_queue_at`
- `sla_due_at`
- `review_started_at`
- `reviewed_at`
- `published_at`

Papel:

- controlar triagem, enriquecimento e decisão editorial
- separar curadoria do status publico do card

### `resource_user_saves`

Itens salvos pelo usuario para acompanhamento.

Campos centrais:

- `user_id`
- `municipality_id`
- `resource_opportunity_id`
- `resource_opportunity_cycle_id`
- `saved_from`
- `notes`
- `preferences`
- `last_viewed_at`

Papel:

- habilitar salvar, organizar e retomar oportunidades relevantes

### `resource_reopen_notifications`

Assinaturas para avisar reabertura de oportunidades encerradas.

Campos centrais:

- `user_id`
- `municipality_id`
- `resource_opportunity_id`
- `last_cycle_id`
- `channel`
- `status`
- `criteria`
- `subscribed_at`
- `last_notified_at`
- `cancelled_at`

Papel:

- registrar interesse explicito do usuario em reabertura
- operar separado das preferencias globais de notificacao

## Relacoes

- `resource_sources` 1:N `resource_opportunities`
- `resource_opportunities` 1:N `resource_opportunity_cycles`
- `resource_opportunities` 1:N `resource_curation_queue`
- `resource_opportunities` 1:N `resource_user_saves`
- `resource_opportunities` 1:N `resource_reopen_notifications`
- `resource_opportunity_cycles` pode referenciar outro ciclo em `reopened_from_cycle_id`

## Compatibilidade com a camada atual

- `federal_program_alerts` continua atendendo a vitrine atual e os fluxos existentes
- as novas tabelas recebem o desenho alvo para as próximas fases
- a camada atual pode depois ser alimentada por adaptadores ou deixar de ser a fonte principal

## Resultado esperado desta fase

- arquitetura do dominio consolidada
- migrations da camada canonica prontas
- base preparada para curadoria, reabertura, saves e histórico sem depender da tabela legada
