<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>{{ $project->title }} - PDF</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: #111827;
            background: #ffffff;
        }

        .page {
            padding: 32px 36px 40px;
            box-sizing: border-box;
        }

        h1,
        h2 {
            margin: 0 0 10px;
        }

        h1 {
            font-size: 28px;
        }

        h2 {
            font-size: 18px;
            margin-top: 28px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .lead {
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 18px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .meta-item {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 14px;
            background: #f9fafb;
        }

        .meta-item strong {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .meta-item span {
            font-size: 14px;
            color: #111827;
        }

        .section-description {
            color: #6b7280;
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .section-content {
            white-space: pre-wrap;
            line-height: 1.7;
            font-size: 14px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .meta-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            vertical-align: top;
            font-size: 13px;
            line-height: 1.55;
        }

        .meta-table .meta-label {
            width: 180px;
            font-weight: bold;
            background: #f9fafb;
        }

        .audit-step {
            margin-top: 18px;
        }
    </style>
</head>

<body>
    <div class="page">
        <h1>{{ $project->title }}</h1>
        <p class="lead">{{ $project->initial_idea }}</p>

        <div class="meta-grid">
            <div class="meta-item">
                <strong>Municipio</strong>
                <span>{{ $project->municipality?->name ?? 'Nao informado' }}</span>
            </div>
            <div class="meta-item">
                <strong>Status</strong>
                <span>{{ $project->status_label }}</span>
            </div>
            <div class="meta-item">
                <strong>Tipo</strong>
                <span>{{ $project->type_label }}</span>
            </div>
            <div class="meta-item">
                <strong>Versão</strong>
                <span>{{ $project->generated_document_version }}</span>
            </div>
            <div class="meta-item">
                <strong>Origem</strong>
                <span>
                    {{ match ($generatedSource) {
                        'ai' => 'IA',
                        'published_revision' => 'Revisão final publicada',
                        default => 'Fallback estruturado',
                    } }}
                </span>
            </div>
            <div class="meta-item">
                <strong>Ultima geração</strong>
                <span>{{ $generatedAt ? \Carbon\Carbon::parse($generatedAt)->format('d/m/Y H:i') : 'Nao registrada' }}</span>
            </div>
            <div class="meta-item">
                <strong>Revisão atual</strong>
                <span>{{ $latestRevisionNumber ?: 'Sem revisão registrada' }}</span>
            </div>
            @if (($exportScope ?? 'working_copy') === 'published_revision')
                <div class="meta-item">
                    <strong>Escopo</strong>
                    <span>Somente versão final publicada</span>
                </div>
                @if (!empty($exportRevision?->publication_signature_name))
                    <div class="meta-item">
                        <strong>Assinatura final</strong>
                        <span>
                            {{ $exportRevision->publication_signature_name }}
                            @if (!empty($exportRevision->publication_signature_role))
                                · {{ $exportRevision->publication_signature_role }}
                            @endif
                        </span>
                    </div>
                @endif
            @endif
        </div>

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
                    <td>{{ $projectMetadata['territorial_scope'] ?: 'Nao informada' }}</td>
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
                    <td class="meta-label">Cronograma previsto</td>
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
                <div class="audit-step">
                    <h2>Publicacao oficial: revisão {{ $entry['revision_number'] ?? '-' }}</h2>
                    <table class="meta-table">
                        <tr>
                            <td class="meta-label">Situacao institucional</td>
                            <td>{{ !empty($entry['is_current']) ? 'Versão final vigente' : 'Versão final historica' }}
                            </td>
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
                </div>
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
                <div class="audit-step">
                    <h2>Etapa: {{ $step['label'] ?? 'Aprovacao' }}</h2>
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
                </div>
            @endforeach
        @endif

        @foreach ($sections as $section)
            <h2>{{ $section->section_order }}. {{ $section->title }}</h2>
            @if (filled($section->description))
                <div class="section-description">{{ $section->description }}</div>
            @endif
            <div class="section-content">{{ $section->content ?: 'Conteudo ainda não gerado.' }}</div>
        @endforeach
    </div>
</body>

</html>
