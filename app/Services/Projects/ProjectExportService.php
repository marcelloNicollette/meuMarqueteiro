<?php

namespace App\Services\Projects;

use App\Models\Project;
use App\Models\ProjectDocumentRevision;
use App\Models\ProjectSection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProjectExportService
{
    public function __construct(
        private ProjectRevisionService $revisionService,
    ) {}

    public function buildViewData(Project $project): array
    {
        $sections = $project->sections
            ->sortBy('section_order')
            ->values();

        $activeCollaborators = $project->collaborators
            ->whereNotNull('accepted_at')
            ->values();

        return [
            'project' => $project,
            'sections' => $sections,
            'activeCollaborators' => $activeCollaborators,
            'latestRevisionNumber' => optional($project->documentRevisions()->latest('revision_number')->first())->revision_number ?? 0,
            'exportRevision' => null,
            'exportScope' => 'working_copy',
            'projectMetadata' => [
                'executive_summary' => data_get($project->metadata, 'executive_summary'),
                'primary_goal' => data_get($project->metadata, 'primary_goal'),
                'target_audience' => data_get($project->metadata, 'target_audience'),
                'territorial_scope' => data_get($project->metadata, 'territorial_scope'),
                'funding_strategy' => data_get($project->metadata, 'funding_strategy'),
                'implementation_notes' => data_get($project->metadata, 'implementation_notes'),
                'risk_notes' => data_get($project->metadata, 'risk_notes'),
                'priority' => data_get($project->metadata, 'priority'),
                'expected_beneficiaries' => data_get($project->metadata, 'expected_beneficiaries'),
                'estimated_budget' => data_get($project->metadata, 'estimated_budget'),
                'expected_start_date' => data_get($project->metadata, 'expected_start_date'),
                'expected_end_date' => data_get($project->metadata, 'expected_end_date'),
            ],
            'generatedAt' => data_get($project->metadata, 'last_generated_at'),
            'generatedSource' => data_get($project->metadata, 'generated_source', 'fallback'),
            'overlapAnalysis' => data_get($project->metadata, 'overlap_analysis'),
            'fundingAnalysis' => data_get($project->metadata, 'funding_analysis'),
            'approvalAuditTrail' => null,
            'officialPublicationSummary' => $this->revisionService->officialPublicationSummary($project),
        ];
    }

    public function buildRevisionViewData(Project $project, ProjectDocumentRevision $revision): array
    {
        $project->loadMissing([
            'municipality',
            'owner',
            'lastEditedBy',
            'sections',
            'collaborators.user',
            'documentRevisions',
        ]);

        $revision->loadMissing([
            'approvedBy:id,name',
            'publishedBy:id,name',
        ]);

        $snapshot = $revision->snapshot ?? [];
        $projectData = data_get($snapshot, 'project', []);
        $metadata = data_get($snapshot, 'structured_metadata', []);

        $exportProject = clone $project;
        foreach (['title', 'initial_idea', 'project_type', 'status', 'responsible_secretariat', 'current_phase'] as $field) {
            if (array_key_exists($field, $projectData)) {
                $exportProject->{$field} = $projectData[$field];
            }
        }
        $exportProject->generated_document_version = data_get(
            $projectData,
            'generated_document_version',
            $project->generated_document_version
        );

        $sections = $this->hydrateSnapshotSections($project, data_get($snapshot, 'sections', []));
        $activeCollaborators = $project->collaborators
            ->whereNotNull('accepted_at')
            ->values();

        return [
            'project' => $exportProject,
            'sections' => $sections,
            'activeCollaborators' => $activeCollaborators,
            'latestRevisionNumber' => $revision->revision_number,
            'exportRevision' => $revision,
            'exportScope' => 'published_revision',
            'projectMetadata' => [
                'executive_summary' => data_get($metadata, 'executive_summary'),
                'primary_goal' => data_get($metadata, 'primary_goal'),
                'target_audience' => data_get($metadata, 'target_audience'),
                'territorial_scope' => data_get($metadata, 'territorial_scope'),
                'funding_strategy' => data_get($metadata, 'funding_strategy'),
                'implementation_notes' => data_get($metadata, 'implementation_notes'),
                'risk_notes' => data_get($metadata, 'risk_notes'),
                'priority' => data_get($metadata, 'priority'),
                'expected_beneficiaries' => data_get($metadata, 'expected_beneficiaries'),
                'estimated_budget' => data_get($metadata, 'estimated_budget'),
                'expected_start_date' => data_get($metadata, 'expected_start_date'),
                'expected_end_date' => data_get($metadata, 'expected_end_date'),
            ],
            'generatedAt' => $revision->published_at ?: $revision->created_at,
            'generatedSource' => 'published_revision',
            'overlapAnalysis' => data_get($project->metadata, 'overlap_analysis'),
            'fundingAnalysis' => data_get($project->metadata, 'funding_analysis'),
            'approvalAuditTrail' => $this->revisionService->approvalAuditTrail($revision),
            'officialPublicationSummary' => $this->revisionService->officialPublicationSummary($project),
        ];
    }

    public function filename(Project $project, string $extension, bool $publishedOnly = false): string
    {
        $slug = Str::slug($project->title ?: 'projeto');
        $suffix = $publishedOnly ? '-versão-final' : '';

        return "{$slug}{$suffix}.{$extension}";
    }

    private function hydrateSnapshotSections(Project $project, array $snapshotSections): Collection
    {
        $templates = $project->sections->keyBy('section_key');

        return collect($snapshotSections)
            ->map(function (array $snapshot) use ($templates) {
                /** @var ProjectSection|null $template */
                $template = $templates->get($snapshot['section_key'] ?? null);
                $section = $template ? clone $template : new ProjectSection();

                $section->section_key = $snapshot['section_key'] ?? $section->section_key;
                $section->section_order = $snapshot['section_order'] ?? $section->section_order;
                $section->title = $snapshot['title'] ?? $section->title;
                $section->content = $snapshot['content'] ?? '';
                $section->needs_review = (bool) ($snapshot['needs_review'] ?? false);

                return $section;
            })
            ->sortBy('section_order')
            ->values();
    }
}
