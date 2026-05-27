<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>{{ $project->title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #1f2937;
            font-size: 12pt;
            line-height: 1.55;
            margin: 28px;
        }

        h1,
        h2,
        h3 {
            color: #111827;
            margin-bottom: 10px;
        }

        h1 {
            font-size: 20pt;
        }

        h2 {
            font-size: 14pt;
            margin-top: 24px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 6px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .meta-table td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            vertical-align: top;
        }

        .meta-label {
            width: 180px;
            font-weight: bold;
            background: #f3f4f6;
        }

        .section-description {
            color: #6b7280;
            margin-bottom: 8px;
        }

        .section-content {
            white-space: pre-wrap;
        }
    </style>
</head>

<body>
    <h1>{{ $project->title }}</h1>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Municipio</td>
            <td>{{ $project->municipality?->name ?? 'Nao informado' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Status</td>
            <td>{{ $project->status_label }}</td>
        </tr>
        <tr>
            <td class="meta-label">Tipo</td>
            <td>{{ $project->type_label }}</td>
        </tr>
        <tr>
            <td class="meta-label">Secretaria responsável</td>
            <td>{{ $project->responsible_secretariat ?: 'A definir' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Versão do documento</td>
            <td>{{ $project->generated_document_version }}</td>
        </tr>
        <tr>
            <td class="meta-label">Origem da geração</td>
            <td>
                {{ match ($generatedSource) {
                    'ai' => 'IA',
                    'published_revision' => 'Revisão final publicada',
                    default => 'Fallback estruturado',
                } }}
            </td>
        </tr>
        <tr>
            <td class="meta-label">Ultima geração</td>
            <td>{{ $generatedAt ? \Carbon\Carbon::parse($generatedAt)->format('d/m/Y H:i') : 'Nao registrada' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Responsável</td>
            <td>{{ $project->owner?->name ?? 'Nao informado' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Revisão atual</td>
            <td>{{ $latestRevisionNumber ?: 'Sem revisão registrada' }}</td>
        </tr>
        @if (($exportScope ?? 'working_copy') === 'published_revision')
            <tr>
                <td class="meta-label">Escopo da exportacao</td>
                <td>Somente versão final publicada</td>
            </tr>
            @if (!empty($exportRevision?->publication_signature_name))
                <tr>
                    <td class="meta-label">Assinatura final</td>
                    <td>
                        {{ $exportRevision->publication_signature_name }}
                        @if (!empty($exportRevision->publication_signature_role))
                            · {{ $exportRevision->publication_signature_role }}
                        @endif
                    </td>
                </tr>
            @endif
        @endif
    </table>

    <h2>Idéia inicial</h2>
    <div class="section-content">{{ $project->initial_idea }}</div>

    @if (collect($projectMetadata)->filter(fn($value) => filled($value))->isNotEmpty())
        <h2>Metadados estruturados</h2>
        <table class="meta-table">
            <tr>
                <td class="meta-label">Resumo executivo</td>
                <td>{{ $projectMetadata['executive_summary'] ?: 'Nao informado' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Objetivo principal</td>
                <td>{{ $projectMetadata['primary_goal'] ?: 'Nao informado' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Público beneficiado</td>
                <td>{{ $projectMetadata['target_audience'] ?: 'Nao informado' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Abrangencia territorial</td>
                <td>{{ $projectMetadata['territorial_scope'] ?: 'Nao informado' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Prioridade</td>
                <td>{{ $projectMetadata['priority'] ?: 'Nao informada' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Orcamento estimado</td>
                <td>
                    {{ filled($projectMetadata['estimated_budget']) ? 'R$ ' . number_format((float) $projectMetadata['estimated_budget'], 2, ',', '.') : 'Nao informado' }}
                </td>
            </tr>
            <tr>
                <td class="meta-label">Beneficiários estimados</td>
                <td>{{ $projectMetadata['expected_beneficiaries'] ?: 'Nao informado' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Previsão</td>
                <td>
                    {{ $projectMetadata['expected_start_date'] ?: 'Início não informado' }} ate
                    {{ $projectMetadata['expected_end_date'] ?: 'conclusao não informada' }}
                </td>
            </tr>
            <tr>
                <td class="meta-label">Estrategia de financiamento</td>
                <td>{{ $projectMetadata['funding_strategy'] ?: 'Nao informada' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Notas de implementacao</td>
                <td>{{ $projectMetadata['implementation_notes'] ?: 'Nao informadas' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Riscos e cuidados</td>
                <td>{{ $projectMetadata['risk_notes'] ?: 'Nao informados' }}</td>
            </tr>
        </table>
    @endif

    @if (($exportScope ?? 'working_copy') === 'published_revision' && filled($officialPublicationSummary))
        <h2>Registro oficial da versão final</h2>
        <table class="meta-table">
            <tr>
                <td class="meta-label">Versão final vigente</td>
                <td>
                    {{ filled($officialPublicationSummary['current_revision_number'] ?? null) ? 'Revisão ' . $officialPublicationSummary['current_revision_number'] : 'Nao informada' }}
                </td>
            </tr>
            <tr>
                <td class="meta-label">Publicacao vigente registrada em</td>
                <td>{{ filled($officialPublicationSummary['current_published_at'] ?? null) ? \Carbon\Carbon::parse($officialPublicationSummary['current_published_at'])->format('d/m/Y H:i') : 'Nao informado' }}
                </td>
            </tr>
            <tr>
                <td class="meta-label">Assinatura institucional vigente</td>
                <td>
                    {{ trim(
                        implode(
                            ' · ',
                            array_filter([
                                $officialPublicationSummary['current_publication_signature_name'] ?? null,
                                $officialPublicationSummary['current_publication_signature_role'] ?? null,
                            ]),
                        ),
                    ) ?:
                        'Nao informada' }}
                </td>
            </tr>
        </table>

        @foreach ($officialPublicationSummary['history'] ?? [] as $entry)
            <h3>Publicacao oficial: revisão {{ $entry['revision_number'] ?? '-' }}</h3>
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Situacao institucional</td>
                    <td>{{ !empty($entry['is_current']) ? 'Versão final vigente' : 'Versão final historica' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Publicada em</td>
                    <td>{{ filled($entry['published_at'] ?? null) ? \Carbon\Carbon::parse($entry['published_at'])->format('d/m/Y H:i') : 'Nao informado' }}
                    </td>
                </tr>
                <tr>
                    <td class="meta-label">Publicada por</td>
                    <td>{{ $entry['published_by_name'] ?: 'Nao informado' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Motivo formal</td>
                    <td>{{ $entry['publication_reason'] ?: 'Nao informado' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Assinatura institucional</td>
                    <td>
                        {{ trim(
                            implode(
                                ' · ',
                                array_filter([$entry['publication_signature_name'] ?? null, $entry['publication_signature_role'] ?? null]),
                            ),
                        ) ?:
                            'Nao informada' }}
                    </td>
                </tr>
                <tr>
                    <td class="meta-label">Substituida em</td>
                    <td>{{ filled($entry['superseded_at'] ?? null) ? \Carbon\Carbon::parse($entry['superseded_at'])->format('d/m/Y H:i') : 'Ainda vigente' }}
                    </td>
                </tr>
                <tr>
                    <td class="meta-label">Substituida pela revisão</td>
                    <td>{{ filled($entry['superseded_by_revision_number'] ?? null) ? 'Revisão ' . $entry['superseded_by_revision_number'] : 'Ainda vigente' }}
                    </td>
                </tr>
            </table>
        @endforeach
    @endif

    @if (($exportScope ?? 'working_copy') === 'published_revision' && filled($approvalAuditTrail))
        <h2>Governanca formal e auditoria</h2>
        <table class="meta-table">
            <tr>
                <td class="meta-label">Motivo formal da aprovacao</td>
                <td>{{ $approvalAuditTrail['approval_reason'] ?: 'Nao informado' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Aprovada em</td>
                <td>{{ filled($approvalAuditTrail['approved_at'] ?? null) ? \Carbon\Carbon::parse($approvalAuditTrail['approved_at'])->format('d/m/Y H:i') : 'Nao informado' }}
                </td>
            </tr>
            <tr>
                <td class="meta-label">Aprovada por</td>
                <td>{{ $approvalAuditTrail['approved_by_name'] ?: 'Nao informado' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Motivo formal da publicacao</td>
                <td>{{ $approvalAuditTrail['publication_reason'] ?: 'Nao informado' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Publicada em</td>
                <td>{{ filled($approvalAuditTrail['published_at'] ?? null) ? \Carbon\Carbon::parse($approvalAuditTrail['published_at'])->format('d/m/Y H:i') : 'Nao informado' }}
                </td>
            </tr>
            <tr>
                <td class="meta-label">Assinatura final de publicacao</td>
                <td>
                    {{ trim(
                        implode(
                            ' · ',
                            array_filter([
                                $approvalAuditTrail['publication_signature_name'] ?? null,
                                $approvalAuditTrail['publication_signature_role'] ?? null,
                            ]),
                        ),
                    ) ?:
                        'Nao informada' }}
                </td>
            </tr>
        </table>

        @foreach ($approvalAuditTrail['steps'] ?? [] as $step)
            <h3>Etapa: {{ $step['label'] ?? 'Aprovacao' }}</h3>
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Perfil exigido</td>
                    <td>{{ $step['required_profile_label'] ?: 'Nao informado' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Responsável designado</td>
                    <td>{{ $step['responsible_name'] ?: 'Nao informado' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Perfil do responsável</td>
                    <td>{{ $step['responsible_project_role_label'] ?: 'Nao informado' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Designado por</td>
                    <td>{{ trim(
                        implode(
                            ' · ',
                            array_filter([$step['responsible_assigned_by_name'] ?? null, $step['responsible_assigned_by_role'] ?? null]),
                        ),
                    ) ?:
                        'Nao informado' }}
                    </td>
                </tr>
                <tr>
                    <td class="meta-label">Designacao registrada em</td>
                    <td>{{ filled($step['responsible_assigned_at'] ?? null) ? \Carbon\Carbon::parse($step['responsible_assigned_at'])->format('d/m/Y H:i') : 'Nao informado' }}
                    </td>
                </tr>
                <tr>
                    <td class="meta-label">Status da etapa</td>
                    <td>{{ !empty($step['approved']) ? 'Concluida' : 'Pendente' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Assinatura da etapa</td>
                    <td>{{ trim(
                        implode(
                            ' · ',
                            array_filter([
                                $step['completed_by_signature_name'] ?? ($step['completed_by_name'] ?? null),
                                $step['completed_by_signature_role'] ?? ($step['completed_by_project_role_label'] ?? null),
                            ]),
                        ),
                    ) ?:
                        'Nao informada' }}
                    </td>
                </tr>
                <tr>
                    <td class="meta-label">Conclusao registrada em</td>
                    <td>{{ filled($step['approved_at'] ?? null) ? \Carbon\Carbon::parse($step['approved_at'])->format('d/m/Y H:i') : 'Nao informado' }}
                    </td>
                </tr>
            </table>
        @endforeach
    @endif

    @foreach ($sections as $section)
        <h2>{{ $section->section_order }}. {{ $section->title }}</h2>
        @if (filled($section->description))
            <div class="section-description">{{ $section->description }}</div>
        @endif
        <div class="section-content">{{ $section->content ?: 'Conteudo ainda não gerado.' }}</div>
    @endforeach

    @if ($activeCollaborators->isNotEmpty())
        <h2>Colaboradores ativos</h2>
        @foreach ($activeCollaborators as $collaborator)
            <div class="section-content">
                {{ $collaborator->user?->name ?? 'Usuario' }} -
                {{ $collaborator->permission === 'viewer' ? 'Visualizacao' : 'Edição' }}
            </div>
        @endforeach
    @endif
</body>

</html>
