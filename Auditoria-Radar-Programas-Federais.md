# Auditoria - Radar de Programas Federais

Data: 2026-05-18

## Escopo revisado

- Painel admin do radar
- Tela do prefeito
- Sync via Portal da Transparencia
- Matching por IA
- Rotas e nomenclatura operacional

## Estado geral

O módulo ja foi parcialmente migrado para o Portal da Transparencia como fonte principal. A nomenclatura institucional do admin tambem ja foi ajustada em pontos importantes. Porem, ainda existem inconsistencias de regra de negocio e de comunicação entre o que o sync salva e o que as telas exibem.

## Achados principais

### 1. Inconsistencia critica de status entre sync e telas

O sync atual gera registros históricos com status como `historical`, `monitoring` e `low_priority`, enquanto partes relevantes da interface e dos indicadores continuam esperando apenas `open`, `closing`, `applied` e `closed`.

Impacto:

- contadores do admin podem ficar zerados ou subcontados
- cards da area do prefeito podem comunicar "programas abertos" sem refletir a base real
- algumas oportunidades ficam sem rotulo coerente na tela

Arquivos afetados:

- `app/Services/FederalPrograms/TransparenciaClient.php`
- `app/Services/FederalPrograms/ClaudeMatchingService.php`
- `app/Http/Controllers/Admin/FederalProgramsController.php`
- `resources/views/admin/federal-programs/index.blade.php`
- `resources/views/mayor/federal-programs/index.blade.php`
- `app/Http/Controllers/Mayor/SituacaoController.php`

### 2. O nome "Radar de Programas Federais" ainda mistura oportunidade futura com histórico de captação

O prompt do matching deixa claro que a base atual sao transferencias e convenios históricos recebidos pelo município. Mesmo assim, a UX do radar comunica isso como se fossem editais ou programas efetivamente abertos.

Impacto:

- risco de expectativa errada do usuario
- risco de interpretar histórico como oportunidade vigente

Arquivos afetados:

- `app/Services/FederalPrograms/ClaudeMatchingService.php`
- `resources/views/admin/federal-programs/index.blade.php`
- `resources/views/mayor/federal-programs/index.blade.php`

### 3. A migracao de nome para Portal da Transparencia ainda não esta 100 por cento consolidada no produto

O admin principal ja fala em Portal da Transparencia, mas ainda existe compatibilidade legada por chave `transferegov` e o cliente antigo segue no codigo. Isso e aceitavel como transicao tecnica, mas não como estado final se a intencao for consolidar uma unica fonte nominal no módulo.

Impacto:

- dificulta manutencao
- confunde diagnostico futuro
- deixa duvida sobre qual fonte eh oficial

Arquivos afetados:

- `app/Services/FederalPrograms/TransparenciaClient.php`
- `app/Services/FederalPrograms/TransferegovClient.php`
- `app/Http/Controllers/Admin/SettingsController.php`
- `app/Http/Controllers/Admin/IntegrationMonitorController.php`

### 4. O painel do prefeito não expõe claramente a origem e o tipo de cada registro

Embora exista `source_platform` e o link de origem, a tela do prefeito prioriza o nome do programa e o score de compatibilidade, mas não explicita de forma clara se aquilo veio de convenio histórico ou de emenda nacional.

Impacto:

- leitura pouco institucional da origem dos dados
- menor confianca para uso decisorio

Arquivos afetados:

- `app/Models/FederalProgramAlert.php`
- `resources/views/mayor/federal-programs/index.blade.php`

## Conclusao sobre nomenclatura

Sim, faz sentido deixar a area 100 por cento com o nome do Portal da Transparencia se a decisão de produto for:

- usar o Portal da Transparencia como unica fonte oficial desta area
- tratar os dados como base de inteligencia para captação federal

Mas isso so deve ser consolidado junto com um ajuste de semantica:

- deixar claro quando o dado eh histórico
- separar "oportunidade monitorada" de "edital aberto"
- padronizar os status em todo o módulo

## Recomendacao objetiva

### Fase 1

- padronizar todos os status do radar
- definir uma tabela unica de status aceitos
- alinhar sync, admin, tela do prefeito e situacao do mandato

### Fase 2

- consolidar o nome da area para Portal da Transparencia em toda a superficie visivel
- manter compatibilidade legada apenas internamente, se ainda necessário

### Fase 3

- explicitar origem do registro na UI:
    - convenio histórico
    - emenda nacional
    - oportunidade monitorada

### Fase 4

- revisar as copies do radar para não prometer "programa aberto" quando o dado for histórico inferido por IA

## Registro final

O radar pode sim ficar 100 por cento posicionado como Portal da Transparencia, mas ainda não esta semanticamente fechado para isso. Antes da consolidacao visual definitiva, o ponto mais importante eh corrigir a regra de status e a linguagem de oportunidade versus histórico.
