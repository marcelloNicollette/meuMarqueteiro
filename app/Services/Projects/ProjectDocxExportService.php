<?php

namespace App\Services\Projects;

use App\Models\Project;
use App\Models\ProjectDocumentRevision;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class ProjectDocxExportService
{
    public function __construct(
        private ProjectExportService $exportService,
    ) {}

    public function save(Project $project, string $filePath, ?ProjectDocumentRevision $revision = null): void
    {
        $data = $revision
            ? $this->exportService->buildRevisionViewData($project, $revision)
            : $this->exportService->buildViewData($project);
        $exportProject = $data['project'];

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop' => 900,
            'marginBottom' => 900,
            'marginLeft' => 900,
            'marginRight' => 900,
        ]);

        $section->addTitle($exportProject->title, 1);
        $section->addText(
            'Projeto municipal estruturado na plataforma',
            ['italic' => true, 'color' => '4B5563'],
            ['spaceAfter' => 200]
        );

        $table = $section->addTable([
            'borderColor' => 'D1D5DB',
            'borderSize' => 6,
            'cellMargin' => 80,
        ]);

        $this->addTableRow($table, 'Municipio', $exportProject->municipality?->name ?? 'Nao informado');
        $this->addTableRow($table, 'Status', $exportProject->status_label);
        $this->addTableRow($table, 'Tipo', $exportProject->type_label);
        $this->addTableRow($table, 'Secretaria responsável', $exportProject->responsible_secretariat ?: 'A definir');
        $this->addTableRow($table, 'Versão do documento', (string) $exportProject->generated_document_version);
        $this->addTableRow($table, 'Ultima geração', $this->formatDateTime($data['generatedAt'] ?? null));
        $originLabel = match ($data['generatedSource']) {
            'ai' => 'IA',
            'published_revision' => 'Revisão final publicada',
            default => 'Fallback estruturado',
        };
        $this->addTableRow($table, 'Origem da geração', $originLabel);
        $this->addTableRow($table, 'Revisão atual', (string) ($data['latestRevisionNumber'] ?? 0));
        if (($data['exportScope'] ?? 'working_copy') === 'published_revision') {
            $this->addTableRow($table, 'Escopo da exportacao', 'Apenas revisão final publicada');
            if (!empty($data['exportRevision']?->publication_signature_name)) {
                $signature = $data['exportRevision']->publication_signature_name;
                if (!empty($data['exportRevision']->publication_signature_role)) {
                    $signature .= ' · ' . $data['exportRevision']->publication_signature_role;
                }

                $this->addTableRow($table, 'Assinatura final', $signature);
            }
        }

        $section->addTitle('Idéia inicial', 2);
        $this->addMultilineText($section, (string) $exportProject->initial_idea);

        if (collect($data['projectMetadata'] ?? [])->filter(fn($value) => filled($value))->isNotEmpty()) {
            $section->addTitle('Metadados estruturados', 2);
            $metadataTable = $section->addTable([
                'borderColor' => 'E5E7EB',
                'borderSize' => 6,
                'cellMargin' => 80,
            ]);

            $this->addTableRow($metadataTable, 'Resumo executivo', (string) ($data['projectMetadata']['executive_summary'] ?? 'Nao informado'));
            $this->addTableRow($metadataTable, 'Objetivo principal', (string) ($data['projectMetadata']['primary_goal'] ?? 'Nao informado'));
            $this->addTableRow($metadataTable, 'Público beneficiado', (string) ($data['projectMetadata']['target_audience'] ?? 'Nao informado'));
            $this->addTableRow($metadataTable, 'Abrangencia territorial', (string) ($data['projectMetadata']['territorial_scope'] ?? 'Nao informado'));
            $this->addTableRow($metadataTable, 'Prioridade', (string) ($data['projectMetadata']['priority'] ?? 'Nao informada'));
            $this->addTableRow($metadataTable, 'Orcamento estimado', filled($data['projectMetadata']['estimated_budget'] ?? null)
                ? 'R$ ' . number_format((float) $data['projectMetadata']['estimated_budget'], 2, ',', '.')
                : 'Nao informado');
            $this->addTableRow($metadataTable, 'Beneficiários estimados', (string) ($data['projectMetadata']['expected_beneficiaries'] ?? 'Nao informado'));
            $this->addTableRow($metadataTable, 'Previsão de início', (string) ($data['projectMetadata']['expected_start_date'] ?? 'Nao informada'));
            $this->addTableRow($metadataTable, 'Previsão de conclusao', (string) ($data['projectMetadata']['expected_end_date'] ?? 'Nao informada'));
            $this->addTableRow($metadataTable, 'Estrategia de financiamento', (string) ($data['projectMetadata']['funding_strategy'] ?? 'Nao informada'));
            $this->addTableRow($metadataTable, 'Notas de implementacao', (string) ($data['projectMetadata']['implementation_notes'] ?? 'Nao informadas'));
            $this->addTableRow($metadataTable, 'Riscos e cuidados', (string) ($data['projectMetadata']['risk_notes'] ?? 'Nao informados'));
        }

        if (($data['exportScope'] ?? 'working_copy') === 'published_revision' && !empty($data['officialPublicationSummary'])) {
            $this->addOfficialPublicationSummary($section, $data['officialPublicationSummary']);
        }

        if (($data['exportScope'] ?? 'working_copy') === 'published_revision' && !empty($data['approvalAuditTrail'])) {
            $this->addApprovalAuditTrail($section, $data['approvalAuditTrail']);
        }

        foreach ($data['sections'] as $projectSection) {
            $section->addTitle($projectSection->section_order . '. ' . $projectSection->title, 2);

            if (filled($projectSection->description)) {
                $section->addText((string) $projectSection->description, [
                    'italic' => true,
                    'color' => '6B7280',
                ], [
                    'spaceAfter' => 140,
                ]);
            }

            $this->addMultilineText($section, (string) ($projectSection->content ?: 'Conteudo ainda não gerado.'));
        }

        if ($data['activeCollaborators']->isNotEmpty()) {
            $section->addTitle('Colaboradores ativos', 2);

            foreach ($data['activeCollaborators'] as $collaborator) {
                $section->addListItem(
                    ($collaborator->user?->name ?? 'Usuario') . ' - ' . ($collaborator->permission === 'viewer' ? 'Visualizacao' : 'Edição'),
                    0,
                    [],
                    [],
                    Jc::START
                );
            }
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($filePath);
    }

    private function addTableRow($table, string $label, string $value): void
    {
        $table->addRow();
        $table->addCell(2800, ['bgColor' => 'F3F4F6'])->addText($label, ['bold' => true]);
        $table->addCell(6200)->addText($value !== '' ? $value : 'Nao informado');
    }

    private function addMultilineText($section, string $value): void
    {
        $lines = preg_split("/\r\n|\r|\n/", $value) ?: [$value];

        foreach ($lines as $line) {
            $section->addText($line !== '' ? $line : ' ');
        }
    }

    private function addApprovalAuditTrail($section, array $auditTrail): void
    {
        $section->addTitle('Governanca formal e auditoria', 2);

        $summaryTable = $section->addTable([
            'borderColor' => 'E5E7EB',
            'borderSize' => 6,
            'cellMargin' => 80,
        ]);

        $this->addTableRow($summaryTable, 'Motivo formal da aprovacao', (string) ($auditTrail['approval_reason'] ?? 'Nao informado'));
        $this->addTableRow($summaryTable, 'Aprovada em', $this->formatDateTime($auditTrail['approved_at'] ?? null));
        $this->addTableRow($summaryTable, 'Aprovada por', (string) ($auditTrail['approved_by_name'] ?? 'Nao informado'));
        $this->addTableRow($summaryTable, 'Motivo formal da publicacao', (string) ($auditTrail['publication_reason'] ?? 'Nao informado'));
        $this->addTableRow($summaryTable, 'Publicada em', $this->formatDateTime($auditTrail['published_at'] ?? null));

        $publicationSignature = trim(implode(' · ', array_filter([
            $auditTrail['publication_signature_name'] ?? null,
            $auditTrail['publication_signature_role'] ?? null,
        ])));
        $this->addTableRow($summaryTable, 'Assinatura final de publicacao', $publicationSignature !== '' ? $publicationSignature : 'Nao informada');

        foreach (($auditTrail['steps'] ?? []) as $step) {
            $section->addTitle('Etapa: ' . ($step['label'] ?? 'Aprovacao'), 3);

            $stepTable = $section->addTable([
                'borderColor' => 'E5E7EB',
                'borderSize' => 6,
                'cellMargin' => 80,
            ]);

            $this->addTableRow($stepTable, 'Perfil exigido', (string) ($step['required_profile_label'] ?? 'Nao informado'));
            $this->addTableRow($stepTable, 'Responsável designado', (string) ($step['responsible_name'] ?? 'Nao informado'));
            $this->addTableRow($stepTable, 'Perfil do responsável', (string) ($step['responsible_project_role_label'] ?? 'Nao informado'));
            $this->addTableRow($stepTable, 'Designado por', $this->formatSignatureLine(
                $step['responsible_assigned_by_name'] ?? null,
                $step['responsible_assigned_by_role'] ?? null
            ));
            $this->addTableRow($stepTable, 'Designacao registrada em', $this->formatDateTime($step['responsible_assigned_at'] ?? null));
            $this->addTableRow($stepTable, 'Status da etapa', !empty($step['approved']) ? 'Concluida' : 'Pendente');
            $this->addTableRow($stepTable, 'Assinatura da etapa', $this->formatSignatureLine(
                $step['completed_by_signature_name'] ?? $step['completed_by_name'] ?? null,
                $step['completed_by_signature_role'] ?? $step['completed_by_project_role_label'] ?? null
            ));
            $this->addTableRow($stepTable, 'Conclusao registrada em', $this->formatDateTime($step['approved_at'] ?? null));
        }
    }

    private function addOfficialPublicationSummary($section, array $summary): void
    {
        $section->addTitle('Registro oficial da versão final', 2);

        $table = $section->addTable([
            'borderColor' => 'E5E7EB',
            'borderSize' => 6,
            'cellMargin' => 80,
        ]);

        $this->addTableRow($table, 'Versão final vigente', filled($summary['current_revision_number'] ?? null)
            ? 'Revisão ' . $summary['current_revision_number']
            : 'Nao informada');
        $this->addTableRow($table, 'Publicacao vigente registrada em', $this->formatDateTime($summary['current_published_at'] ?? null));
        $this->addTableRow($table, 'Assinatura institucional vigente', $this->formatSignatureLine(
            $summary['current_publication_signature_name'] ?? null,
            $summary['current_publication_signature_role'] ?? null
        ));

        foreach (($summary['history'] ?? []) as $entry) {
            $section->addTitle('Publicacao oficial: revisão ' . ($entry['revision_number'] ?? '-'), 3);

            $historyTable = $section->addTable([
                'borderColor' => 'E5E7EB',
                'borderSize' => 6,
                'cellMargin' => 80,
            ]);

            $this->addTableRow($historyTable, 'Situacao institucional', !empty($entry['is_current']) ? 'Versão final vigente' : 'Versão final historica');
            $this->addTableRow($historyTable, 'Publicada em', $this->formatDateTime($entry['published_at'] ?? null));
            $this->addTableRow($historyTable, 'Publicada por', (string) ($entry['published_by_name'] ?? 'Nao informado'));
            $this->addTableRow($historyTable, 'Motivo formal', (string) ($entry['publication_reason'] ?? 'Nao informado'));
            $this->addTableRow($historyTable, 'Assinatura institucional', $this->formatSignatureLine(
                $entry['publication_signature_name'] ?? null,
                $entry['publication_signature_role'] ?? null
            ));
            $this->addTableRow($historyTable, 'Substituida em', $this->formatDateTime($entry['superseded_at'] ?? null));
            $this->addTableRow($historyTable, 'Substituida pela revisão', filled($entry['superseded_by_revision_number'] ?? null)
                ? 'Revisão ' . $entry['superseded_by_revision_number']
                : 'Ainda vigente');
        }
    }

    private function formatSignatureLine(?string $name, ?string $role): string
    {
        $value = trim(implode(' · ', array_filter([$name, $role])));

        return $value !== '' ? $value : 'Nao informado';
    }

    private function formatDateTime(mixed $value): string
    {
        if (blank($value)) {
            return 'Nao informado';
        }

        return (string) \Carbon\Carbon::parse($value)->format('d/m/Y H:i');
    }
}
