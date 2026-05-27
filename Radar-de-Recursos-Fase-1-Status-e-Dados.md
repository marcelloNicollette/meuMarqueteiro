# Radar de Recursos - Fase 1

Data: 2026-05-18

## Objetivo

Fechar a fundacao tecnica do módulo para permitir a transicao de `Radar de Programas Federais` para `Radar de Recursos` sem quebrar a base atual.

## Entregas desta fase

- dicionario canonico de status do módulo
- normalizacao de status legados
- expansao inicial do schema da tabela atual
- criacao da tabela de fontes monitoradas
- alinhamento do backend e das telas principais ao novo vocabulario

## Status canonicos

- `pending_review`
- `published`
- `closing_soon`
- `monitoring`
- `closed_recently`
- `archived`
- `reopened`
- `rejected`

## Regras de normalizacao legada

- `open` -> `published`
- `closing` -> `closing_soon`
- `applied` -> `published`
- `approved` -> `published`
- `closed` -> `closed_recently` ou `archived`, conforme prazo
- `historical` -> `monitoring`
- `low_priority` -> `monitoring`

## Estrutura de dados adicionada

### Nova tabela

- `resource_sources`

Objetivo:

- catalogar fontes monitoradas do Radar de Recursos
- armazenar metodo de captura, frequencia, guia de acesso e campos a indexar

### Expansao da tabela atual

Tabela:

- `federal_program_alerts`

Novos grupos de campos:

- identificação ampliada:
    - `resource_source_id`
    - `short_title`
    - `source_key`
- origem e pipeline:
    - `capture_method`
    - `resource_scope`
    - `curation_status`
- ciclo de vida:
    - `published_at`
    - `closed_at`
    - `archived_at`
    - `closed_visibility_until`
- dados de recurso:
    - `estimated_size`
    - `counterpart_percentage`
    - `documentation_requirements`
- explicabilidade:
    - `compatibility_factors`
    - `viability_level`
    - `viability_reason`
    - `viability_factors`
- suporte tecnico:
    - `source_metadata`

## Codigo-fonte de referencia

- enum de status:
    - `app/Enums/ResourceOpportunityStatus.php`
- model de fonte:
    - `app/Models/ResourceSource.php`
- migrations:
    - `database/migrations/2026_05_18_000007_create_resource_sources_table.php`
    - `database/migrations/2026_05_18_000008_expand_federal_program_alerts_for_radar_de_recursos.php`

## Observacao importante

A tabela `federal_program_alerts` foi mantida como base de transicao para evitar ruptura imediata no módulo atual. A renomeacao estrutural completa do dominio deve ocorrer nas fases seguintes, quando o catalogo de fontes, a curadoria e a nova vitrine estiverem implantados.
