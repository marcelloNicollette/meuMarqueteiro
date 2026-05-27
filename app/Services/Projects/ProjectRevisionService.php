<?php

namespace App\Services\Projects;

use App\Models\Project;
use App\Models\ProjectDocumentRevision;
use App\Models\ProjectSection;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectRevisionService
{
    public function currentPublishedRevision(Project $project): ?ProjectDocumentRevision
    {
        return $project->documentRevisions()
            ->where('status', 'published')
            ->latest('revision_number')
            ->first();
    }

    public function currentWorkingRevision(Project $project): ?ProjectDocumentRevision
    {
        $currentPublishedRevision = $this->currentPublishedRevision($project);

        $query = $project->documentRevisions()
            ->where('status', '!=', 'published');

        if ($currentPublishedRevision) {
            $query->where('revision_number', '>', $currentPublishedRevision->revision_number);
        }

        return $query->latest('revision_number')->first();
    }

    public function isCurrentWorkingRevision(ProjectDocumentRevision $revision): bool
    {
        $current = $this->currentWorkingRevision($revision->project);

        return $current !== null && $current->id === $revision->id;
    }

    public function isCurrentPublishedRevision(ProjectDocumentRevision $revision): bool
    {
        $current = $this->currentPublishedRevision($revision->project);

        return $current !== null && $current->id === $revision->id;
    }

    public function eligibleUsersForApprovalStep(Project $project, array|string $step, iterable $users)
    {
        $normalizedStep = is_array($step)
            ? collect($this->normalizeApprovalSteps([$step]))->firstWhere('key', $step['key'] ?? null)
            : collect($this->defaultApprovalSteps())->firstWhere('key', $step);

        if (!$normalizedStep) {
            return collect();
        }

        $requiredRoles = $this->normalizeProjectRoles($normalizedStep['required_project_roles'] ?? []);
        $currentResponsibleId = (int) ($normalizedStep['responsible_user_id'] ?? 0);

        return collect($users)
            ->filter(function ($user) use ($project, $requiredRoles, $currentResponsibleId) {
                if (!$user instanceof User) {
                    return false;
                }

                if ($currentResponsibleId > 0 && $user->id === $currentResponsibleId) {
                    return true;
                }

                $role = $this->projectAccessRole($project, $user);

                return empty($requiredRoles) || in_array($role, $requiredRoles, true);
            })
            ->values();
    }

    public function eligibleUsersByApprovalStep(Project $project, ?array $steps, iterable $users): array
    {
        return collect($this->normalizeApprovalSteps($steps))
            ->mapWithKeys(fn(array $step) => [
                $step['key'] => $this->eligibleUsersForApprovalStep($project, $step, $users),
            ])
            ->all();
    }

    public function canUserCompleteApprovalStep(ProjectDocumentRevision $revision, array|string $step, User $user): bool
    {
        $normalizedStep = is_array($step)
            ? collect($this->normalizeApprovalSteps([$step]))->firstWhere('key', $step['key'] ?? null)
            : collect($this->normalizeApprovalSteps($revision->approval_steps))->firstWhere('key', $step);

        if (!$normalizedStep || $revision->status === 'published') {
            return false;
        }

        $responsibleId = (int) ($normalizedStep['responsible_user_id'] ?? 0);
        if ($responsibleId <= 0 || $responsibleId !== $user->id) {
            return false;
        }

        $allowedUsers = $this->eligibleUsersForApprovalStep($revision->project, $normalizedStep, [$user]);

        return $allowedUsers->contains(fn($allowedUser) => $allowedUser instanceof User && $allowedUser->id === $user->id);
    }

    public function createRevision(Project $project, ?User $user, string $triggerAction, array $context = []): ProjectDocumentRevision
    {
        $project->loadMissing(['sections', 'documentRevisions']);

        $activeRevision = $this->activeWorkingRevision($project);
        if ($activeRevision) {
            return $this->refreshWorkingRevision($project, $activeRevision, $user, $triggerAction, $context);
        }

        $previousRevision = $this->resolvePreviousRevision($project, $context);

        $snapshot = $this->buildSnapshot($project);
        $comparison = $this->compareSnapshots($previousRevision?->snapshot, $snapshot);
        $revisionNumber = ($previousRevision?->revision_number ?? 0) + 1;

        $revision = $project->documentRevisions()->create([
            'previous_revision_id' => $previousRevision?->id,
            'user_id' => $user?->id,
            'revision_number' => $revisionNumber,
            'trigger_action' => $triggerAction,
            'summary' => $context['summary'] ?? $this->defaultSummary($triggerAction, $context),
            'status' => 'draft',
            'restored_from_revision_id' => $context['restored_from_revision_id'] ?? null,
            'approval_steps' => $this->applyDefaultResponsibles($project, $this->nextApprovalSteps($previousRevision)),
            'snapshot' => $snapshot,
            'comparison_summary' => $comparison,
        ]);

        if ($user) {
            $project->editHistory()->create([
                'user_id' => $user->id,
                'action' => 'project_revision_created',
                'field_name' => 'revision',
                'new_content' => 'Revisão ' . $revisionNumber,
                'metadata' => [
                    'revision_id' => $revision->id,
                    'revision_number' => $revisionNumber,
                    'trigger_action' => $triggerAction,
                    'summary' => $revision->summary,
                    'revision_status' => $revision->status,
                    'changed_sections' => data_get($comparison, 'counts.sections', 0),
                    'changed_core_fields' => data_get($comparison, 'counts.core', 0),
                    'changed_structured_fields' => data_get($comparison, 'counts.structured_metadata', 0),
                ],
            ]);
        }

        return $revision;
    }

    public function approveRevision(ProjectDocumentRevision $revision, User $user, string $reason): ProjectDocumentRevision
    {
        if ($revision->status === 'published') {
            return $revision;
        }

        if (!$this->allApprovalStepsCompleted($revision)) {
            throw new \RuntimeException('Conclua todas as etapas formais de aprovacao antes de aprovar a revisão.');
        }

        $revision->forceFill([
            'status' => 'approved',
            'approved_by_user_id' => $user->id,
            'approved_at' => now(),
            'approval_reason' => trim($reason),
        ])->save();

        $revision->project->editHistory()->create([
            'user_id' => $user->id,
            'action' => 'project_revision_approved',
            'field_name' => 'revision',
            'new_content' => 'Revisão ' . $revision->revision_number,
            'metadata' => [
                'revision_id' => $revision->id,
                'revision_number' => $revision->revision_number,
                'reason' => trim($reason),
            ],
        ]);

        return $revision->fresh([
            'user:id,name',
            'approvedBy:id,name',
            'publishedBy:id,name',
            'previousRevision:id,project_id,revision_number',
            'restoredFromRevision:id,project_id,revision_number',
        ]);
    }

    public function publishRevision(ProjectDocumentRevision $revision, User $user, string $reason): ProjectDocumentRevision
    {
        return DB::transaction(function () use ($revision, $user, $reason) {
            $project = $revision->project;

            if (!$this->allApprovalStepsCompleted($revision)) {
                throw new \RuntimeException('Conclua todas as etapas formais de aprovacao antes de publicar a versão final.');
            }

            if ($revision->status !== 'approved') {
                throw new \RuntimeException('A revisão precisa estar aprovada antes da publicacao final.');
            }

            $previousPublishedRevisions = $project->documentRevisions()
                ->where('status', 'published')
                ->where('id', '!=', $revision->id)
                ->get();

            foreach ($previousPublishedRevisions as $previousPublishedRevision) {
                $project->editHistory()->create([
                    'user_id' => $user->id,
                    'action' => 'project_revision_superseded',
                    'field_name' => 'revision',
                    'new_content' => 'Revisão ' . $previousPublishedRevision->revision_number,
                    'metadata' => [
                        'revision_id' => $previousPublishedRevision->id,
                        'revision_number' => $previousPublishedRevision->revision_number,
                        'superseded_by_revision_id' => $revision->id,
                        'superseded_by_revision_number' => $revision->revision_number,
                        'previous_publication_reason' => $previousPublishedRevision->publication_reason,
                        'previous_publication_signature_name' => $previousPublishedRevision->publication_signature_name,
                        'previous_publication_signature_role' => $previousPublishedRevision->publication_signature_role,
                        'previous_published_at' => optional($previousPublishedRevision->published_at)?->toIso8601String(),
                    ],
                ]);
            }

            $project->documentRevisions()
                ->whereIn('id', $previousPublishedRevisions->pluck('id'))
                ->update([
                    'status' => 'approved',
                    'published_at' => null,
                    'published_by_user_id' => null,
                ]);

            $revision->forceFill([
                'status' => 'published',
                'published_by_user_id' => $user->id,
                'published_at' => now(),
                'publication_reason' => trim($reason),
                'publication_signature_name' => $user->name,
                'publication_signature_role' => $this->signatureRole($project, $user),
            ])->save();

            $project->editHistory()->create([
                'user_id' => $user->id,
                'action' => 'project_revision_published',
                'field_name' => 'revision',
                'new_content' => 'Revisão ' . $revision->revision_number,
                'metadata' => [
                    'revision_id' => $revision->id,
                    'revision_number' => $revision->revision_number,
                    'reason' => trim($reason),
                    'signature_name' => $user->name,
                    'signature_role' => $this->signatureRole($project, $user),
                ],
            ]);

            return $revision->fresh([
                'user:id,name',
                'approvedBy:id,name',
                'publishedBy:id,name',
                'previousRevision:id,project_id,revision_number',
                'restoredFromRevision:id,project_id,revision_number',
            ]);
        });
    }

    public function assignApprovalStepResponsible(
        ProjectDocumentRevision $revision,
        string $stepKey,
        User $responsibleUser,
        User $actor
    ): ProjectDocumentRevision {
        $steps = collect($this->normalizeApprovalSteps($revision->approval_steps));
        $steps = collect($this->applyDefaultResponsibles($revision->project, $steps->all()));
        $step = $steps->firstWhere('key', $stepKey);

        if (!$step) {
            throw new \InvalidArgumentException('Etapa de aprovacao não encontrada.');
        }

        $responsibleProjectRole = $this->projectAccessRole($revision->project, $responsibleUser);
        $this->ensureAllowedForApprovalStep($step, $responsibleProjectRole, 'assumir');

        $duplicateResponsible = $steps->contains(function (array $step) use ($stepKey, $responsibleUser) {
            return $step['key'] !== $stepKey && (int) ($step['responsible_user_id'] ?? 0) === $responsibleUser->id;
        });

        if ($duplicateResponsible) {
            throw new \InvalidArgumentException('Cada etapa deve ter um responsável distinto nesta revisão.');
        }

        $actorProjectRole = $this->projectAccessRole($revision->project, $actor);

        $workflowWasReset = false;

        $steps = $steps->map(function (array $step) use (
            $stepKey,
            $responsibleUser,
            $responsibleProjectRole,
            $actor,
            $actorProjectRole,
            &$workflowWasReset
        ) {
            if ($step['key'] !== $stepKey) {
                return $step;
            }

            $shouldResetApproval = (int) ($step['responsible_user_id'] ?? 0) !== $responsibleUser->id;
            $step['responsible_user_id'] = $responsibleUser->id;
            $step['responsible_name'] = $responsibleUser->name;
            $step['responsible_project_role'] = $responsibleProjectRole;
            $step['responsible_project_role_label'] = $this->projectAccessRoleLabel($responsibleProjectRole);
            $step['responsible_assigned_by_user_id'] = $actor->id;
            $step['responsible_assigned_by_name'] = $actor->name;
            $step['responsible_assigned_by_role'] = $this->projectAccessRoleLabel($actorProjectRole);
            $step['responsible_assigned_at'] = now()->toIso8601String();

            if ($shouldResetApproval) {
                $workflowWasReset = true;
                $step['approved'] = false;
                $step['completed_by_user_id'] = null;
                $step['completed_by_name'] = null;
                $step['completed_by_project_role'] = null;
                $step['completed_by_project_role_label'] = null;
                $step['completed_by_signature_name'] = null;
                $step['completed_by_signature_role'] = null;
                $step['approved_at'] = null;
            }

            return $step;
        })->values()->all();

        $revisionAttributes = [
            'approval_steps' => $steps,
        ];

        if ($workflowWasReset && $revision->status !== 'draft') {
            $revisionAttributes = array_merge($revisionAttributes, $this->draftWorkflowState());
        }

        $revision->forceFill($revisionAttributes)->save();

        $step = collect($steps)->firstWhere('key', $stepKey);

        $revision->project->editHistory()->create([
            'user_id' => $actor->id,
            'action' => 'project_revision_step_responsible_assigned',
            'field_name' => 'revision_step',
            'new_content' => $step['label'] ?? $stepKey,
            'metadata' => [
                'revision_id' => $revision->id,
                'revision_number' => $revision->revision_number,
                'step_key' => $stepKey,
                'step_label' => $step['label'] ?? $stepKey,
                'responsible_user_id' => $responsibleUser->id,
                'responsible_name' => $responsibleUser->name,
                'responsible_project_role' => $step['responsible_project_role'] ?? null,
                'responsible_project_role_label' => $step['responsible_project_role_label'] ?? null,
                'required_profile_label' => $step['required_profile_label'] ?? null,
                'assigned_by_name' => $actor->name,
                'assigned_by_role' => $step['responsible_assigned_by_role'] ?? null,
                'assigned_at' => $step['responsible_assigned_at'] ?? null,
            ],
        ]);

        return $revision->fresh([
            'user:id,name',
            'approvedBy:id,name',
            'publishedBy:id,name',
            'previousRevision:id,project_id,revision_number',
            'restoredFromRevision:id,project_id,revision_number',
        ]);
    }

    public function completeApprovalStep(ProjectDocumentRevision $revision, string $stepKey, User $user): ProjectDocumentRevision
    {
        $userProjectRole = $this->projectAccessRole($revision->project, $user);
        $steps = collect($this->normalizeApprovalSteps($revision->approval_steps))
            ->pipe(fn($steps) => collect($this->applyDefaultResponsibles($revision->project, $steps->all())))
            ->map(function (array $step) use ($stepKey, $user, $userProjectRole, $revision) {
                if ($step['key'] !== $stepKey) {
                    return $step;
                }

                $responsibleId = (int) ($step['responsible_user_id'] ?? 0);
                if ($responsibleId <= 0) {
                    throw new \RuntimeException('Defina um responsável para esta etapa antes de conclui-la.');
                }

                if ($responsibleId > 0 && $responsibleId !== $user->id) {
                    throw new \RuntimeException('Esta etapa possui outro responsável designado.');
                }

                $this->ensureAllowedForApprovalStep($step, $userProjectRole, 'concluir');
                $step['approved'] = true;
                $step['completed_by_user_id'] = $user->id;
                $step['completed_by_name'] = $user->name;
                $step['completed_by_project_role'] = $userProjectRole;
                $step['completed_by_project_role_label'] = $this->projectAccessRoleLabel($userProjectRole);
                $step['completed_by_signature_name'] = $user->name;
                $step['completed_by_signature_role'] = $this->signatureRole($revision->project, $user);
                $step['approved_at'] = now()->toIso8601String();

                return $step;
            })
            ->values()
            ->all();

        if (!collect($steps)->contains(fn(array $step) => $step['key'] === $stepKey)) {
            throw new \InvalidArgumentException('Etapa de aprovacao não encontrada.');
        }

        $revision->forceFill([
            'approval_steps' => $steps,
        ])->save();

        $step = collect($steps)->firstWhere('key', $stepKey);

        $revision->project->editHistory()->create([
            'user_id' => $user->id,
            'action' => 'project_revision_step_approved',
            'field_name' => 'revision_step',
            'new_content' => $step['label'] ?? $stepKey,
            'metadata' => [
                'revision_id' => $revision->id,
                'revision_number' => $revision->revision_number,
                'step_key' => $stepKey,
                'step_label' => $step['label'] ?? $stepKey,
                'responsible_name' => $step['responsible_name'] ?? null,
                'required_profile_label' => $step['required_profile_label'] ?? null,
                'completed_by_signature_name' => $step['completed_by_signature_name'] ?? null,
                'completed_by_signature_role' => $step['completed_by_signature_role'] ?? null,
                'completed_by_project_role_label' => $step['completed_by_project_role_label'] ?? null,
                'approved_at' => $step['approved_at'] ?? null,
            ],
        ]);

        return $revision->fresh([
            'user:id,name',
            'approvedBy:id,name',
            'publishedBy:id,name',
            'previousRevision:id,project_id,revision_number',
            'restoredFromRevision:id,project_id,revision_number',
        ]);
    }

    public function openWorkingDraftFromPublished(
        Project $project,
        ProjectDocumentRevision $publishedRevision,
        User $user
    ): ProjectDocumentRevision {
        $project->loadMissing(['sections', 'documentRevisions']);

        $activeRevision = $this->activeWorkingRevision($project);
        if ($activeRevision) {
            return $activeRevision->fresh([
                'user:id,name',
                'approvedBy:id,name',
                'publishedBy:id,name',
                'previousRevision:id,project_id,revision_number',
                'restoredFromRevision:id,project_id,revision_number',
            ]);
        }

        $draft = $this->createRevision($project, $user, 'open_working_draft', [
            'summary' => 'Novo rascunho aberto a partir da revisão publicada ' . $publishedRevision->revision_number,
            'base_revision_id' => $publishedRevision->id,
        ]);

        $project->editHistory()->create([
            'user_id' => $user->id,
            'action' => 'project_working_draft_opened',
            'field_name' => 'revision',
            'new_content' => 'Revisão ' . $draft->revision_number,
            'metadata' => [
                'revision_id' => $draft->id,
                'revision_number' => $draft->revision_number,
                'source_revision_number' => $publishedRevision->revision_number,
            ],
        ]);

        return $draft;
    }

    public function restoreRevision(Project $project, ProjectDocumentRevision $revision, User $user): ProjectDocumentRevision
    {
        $project->loadMissing(['sections', 'documentRevisions']);
        $snapshot = $revision->snapshot ?? [];

        DB::transaction(function () use ($project, $snapshot, $user, $revision) {
            $projectData = data_get($snapshot, 'project', []);
            $currentVersion = (int) $project->generated_document_version;

            foreach (['title', 'initial_idea', 'project_type', 'status', 'responsible_secretariat', 'current_phase'] as $field) {
                if (array_key_exists($field, $projectData)) {
                    $project->{$field} = $projectData[$field];
                }
            }

            $metadata = is_array($project->metadata) ? $project->metadata : [];
            foreach (array_keys($this->structuredMetadataLabels()) as $field) {
                data_set($metadata, $field, data_get($snapshot, "structured_metadata.{$field}"));
            }

            $metadata['generation_status'] = 'completed';
            $metadata['restored_from_revision'] = $revision->revision_number;
            $metadata['last_restored_at'] = now()->toIso8601String();

            $project->forceFill([
                'metadata' => $metadata,
                'generated_document_version' => max($currentVersion + 1, ((int) data_get($projectData, 'generated_document_version', 0)) + 1),
                'last_edited_by_user_id' => $user->id,
                'last_edited_at' => now(),
                'current_phase' => 'documento_em_revisão',
            ])->save();

            $sectionsByKey = $project->sections->keyBy('section_key');
            foreach (data_get($snapshot, 'sections', []) as $sectionSnapshot) {
                /** @var ProjectSection|null $section */
                $section = $sectionsByKey->get($sectionSnapshot['section_key'] ?? null);
                if (!$section) {
                    continue;
                }

                $section->forceFill([
                    'content' => (string) ($sectionSnapshot['content'] ?? ''),
                    'needs_review' => (bool) ($sectionSnapshot['needs_review'] ?? false),
                    'metadata' => array_merge(is_array($section->metadata) ? $section->metadata : [], [
                        'restored_at' => now()->toIso8601String(),
                        'restored_by' => $user->id,
                        'restored_from_revision' => $revision->id,
                    ]),
                ])->save();
            }

            $project->editHistory()->create([
                'user_id' => $user->id,
                'action' => 'project_revision_restored',
                'field_name' => 'revision',
                'new_content' => 'Revisão ' . $revision->revision_number,
                'metadata' => [
                    'revision_id' => $revision->id,
                    'revision_number' => $revision->revision_number,
                ],
            ]);
        });

        $project->refresh()->loadMissing(['sections', 'documentRevisions']);

        return $this->createRevision($project, $user, 'restore_revision', [
            'summary' => 'Conteudo restaurado a partir da revisão ' . $revision->revision_number,
            'restored_from_revision_id' => $revision->id,
        ]);
    }

    public function buildSnapshot(Project $project): array
    {
        $project->loadMissing('sections');
        $metadata = is_array($project->metadata) ? $project->metadata : [];

        return [
            'project' => [
                'title' => $project->title,
                'initial_idea' => $project->initial_idea,
                'project_type' => $project->project_type,
                'status' => $project->status,
                'responsible_secretariat' => $project->responsible_secretariat,
                'current_phase' => $project->current_phase,
                'generated_document_version' => $project->generated_document_version,
            ],
            'structured_metadata' => $this->extractStructuredMetadata($metadata),
            'sections' => $project->sections
                ->sortBy('section_order')
                ->map(fn($section) => [
                    'section_key' => $section->section_key,
                    'section_order' => $section->section_order,
                    'title' => $section->title,
                    'needs_review' => (bool) $section->needs_review,
                    'content' => (string) ($section->content ?? ''),
                ])
                ->values()
                ->all(),
        ];
    }

    public function compareSnapshots(?array $previous, array $current): array
    {
        if (empty($previous)) {
            return [
                'counts' => [
                    'core' => 0,
                    'structured_metadata' => 0,
                    'sections' => 0,
                ],
                'core_fields' => [],
                'structured_metadata_fields' => [],
                'sections' => [],
            ];
        }

        $coreFields = [];
        foreach ($this->projectFieldLabels() as $field => $label) {
            $oldValue = data_get($previous, "project.{$field}");
            $newValue = data_get($current, "project.{$field}");

            if ($this->stringValue($oldValue) === $this->stringValue($newValue)) {
                continue;
            }

            $coreFields[] = [
                'field' => $field,
                'label' => $label,
                'old' => $this->stringValue($oldValue),
                'new' => $this->stringValue($newValue),
                'change_type' => $this->changeType($oldValue, $newValue),
            ];
        }

        $structuredFields = [];
        foreach ($this->structuredMetadataLabels() as $field => $label) {
            $oldValue = data_get($previous, "structured_metadata.{$field}");
            $newValue = data_get($current, "structured_metadata.{$field}");

            if ($this->stringValue($oldValue) === $this->stringValue($newValue)) {
                continue;
            }

            $structuredFields[] = [
                'field' => $field,
                'label' => $label,
                'old' => $this->stringValue($oldValue),
                'new' => $this->stringValue($newValue),
                'change_type' => $this->changeType($oldValue, $newValue),
            ];
        }

        $previousSections = collect(data_get($previous, 'sections', []))
            ->keyBy('section_key');

        $sectionChanges = [];
        foreach (data_get($current, 'sections', []) as $section) {
            $oldSection = $previousSections->get($section['section_key']);
            $oldContent = $this->stringValue($oldSection['content'] ?? null);
            $newContent = $this->stringValue($section['content'] ?? null);
            $oldReview = (bool) ($oldSection['needs_review'] ?? false);
            $newReview = (bool) ($section['needs_review'] ?? false);

            if ($oldContent === $newContent && $oldReview === $newReview) {
                continue;
            }

            $sectionChanges[] = [
                'section_key' => $section['section_key'],
                'section_title' => $section['title'],
                'change_type' => $this->sectionChangeType($oldContent, $newContent, $oldReview, $newReview),
                'old_excerpt' => $this->excerpt($oldContent),
                'new_excerpt' => $this->excerpt($newContent),
                'old_review' => $oldReview,
                'new_review' => $newReview,
                'old_words' => str_word_count($oldContent),
                'new_words' => str_word_count($newContent),
            ];
        }

        return [
            'counts' => [
                'core' => count($coreFields),
                'structured_metadata' => count($structuredFields),
                'sections' => count($sectionChanges),
            ],
            'core_fields' => $coreFields,
            'structured_metadata_fields' => $structuredFields,
            'sections' => $sectionChanges,
        ];
    }

    public function normalizeApprovalSteps(?array $steps): array
    {
        $defaults = collect($this->defaultApprovalSteps())->keyBy('key');
        $incoming = collect($steps ?? [])->keyBy('key');

        return $defaults->map(function (array $default, string $key) use ($incoming) {
            $step = $incoming->get($key, []);

            return [
                'key' => $key,
                'label' => $default['label'],
                'description' => $default['description'],
                'required_project_roles' => $this->normalizeProjectRoles($step['required_project_roles'] ?? $default['required_project_roles']),
                'required_profile_label' => $default['required_profile_label'],
                'responsible_user_id' => $step['responsible_user_id'] ?? null,
                'responsible_name' => $step['responsible_name'] ?? null,
                'responsible_project_role' => $step['responsible_project_role'] ?? null,
                'responsible_project_role_label' => $step['responsible_project_role_label']
                    ?? $this->projectAccessRoleLabel($step['responsible_project_role'] ?? null),
                'responsible_assigned_by_user_id' => $step['responsible_assigned_by_user_id'] ?? null,
                'responsible_assigned_by_name' => $step['responsible_assigned_by_name'] ?? null,
                'responsible_assigned_by_role' => $step['responsible_assigned_by_role'] ?? null,
                'responsible_assigned_at' => $step['responsible_assigned_at'] ?? null,
                'approved' => (bool) ($step['approved'] ?? false),
                'completed_by_user_id' => $step['completed_by_user_id'] ?? null,
                'completed_by_name' => $step['completed_by_name'] ?? null,
                'completed_by_project_role' => $step['completed_by_project_role'] ?? null,
                'completed_by_project_role_label' => $step['completed_by_project_role_label']
                    ?? $this->projectAccessRoleLabel($step['completed_by_project_role'] ?? null),
                'completed_by_signature_name' => $step['completed_by_signature_name'] ?? null,
                'completed_by_signature_role' => $step['completed_by_signature_role'] ?? null,
                'approved_at' => $step['approved_at'] ?? null,
            ];
        })->values()->all();
    }

    public function allApprovalStepsCompleted(ProjectDocumentRevision $revision): bool
    {
        return collect($this->normalizeApprovalSteps($revision->approval_steps))
            ->every(fn(array $step) => !empty($step['approved']));
    }

    public function approvalAuditTrail(ProjectDocumentRevision $revision): array
    {
        $steps = collect($this->normalizeApprovalSteps($revision->approval_steps))
            ->map(fn(array $step) => [
                'key' => $step['key'],
                'label' => $step['label'],
                'description' => $step['description'],
                'required_profile_label' => $step['required_profile_label'],
                'responsible_name' => $step['responsible_name'] ?? null,
                'responsible_project_role_label' => $step['responsible_project_role_label'] ?? null,
                'responsible_assigned_by_name' => $step['responsible_assigned_by_name'] ?? null,
                'responsible_assigned_by_role' => $step['responsible_assigned_by_role'] ?? null,
                'responsible_assigned_at' => $step['responsible_assigned_at'] ?? null,
                'approved' => (bool) ($step['approved'] ?? false),
                'completed_by_name' => $step['completed_by_name'] ?? null,
                'completed_by_project_role_label' => $step['completed_by_project_role_label'] ?? null,
                'completed_by_signature_name' => $step['completed_by_signature_name'] ?? null,
                'completed_by_signature_role' => $step['completed_by_signature_role'] ?? null,
                'approved_at' => $step['approved_at'] ?? null,
            ])
            ->values()
            ->all();

        return [
            'steps' => $steps,
            'approval_reason' => trim((string) ($revision->approval_reason ?? '')) ?: null,
            'approved_at' => optional($revision->approved_at)?->toIso8601String(),
            'approved_by_name' => $revision->approvedBy?->name,
            'publication_reason' => trim((string) ($revision->publication_reason ?? '')) ?: null,
            'published_at' => optional($revision->published_at)?->toIso8601String(),
            'published_by_name' => $revision->publishedBy?->name,
            'publication_signature_name' => $revision->publication_signature_name,
            'publication_signature_role' => $revision->publication_signature_role,
        ];
    }

    public function officialPublicationSummary(Project $project): array
    {
        $currentPublishedRevision = $this->currentPublishedRevision($project);
        $publicationEntries = $project->editHistory()
            ->whereIn('action', ['project_revision_published', 'project_revision_superseded'])
            ->latest('created_at')
            ->get();

        $supersededEntriesByRevision = $publicationEntries
            ->where('action', 'project_revision_superseded')
            ->keyBy(fn($entry) => (int) data_get($entry->metadata, 'revision_number', 0));

        $history = $publicationEntries
            ->where('action', 'project_revision_published')
            ->map(function ($entry) use ($currentPublishedRevision, $supersededEntriesByRevision) {
                $revisionNumber = (int) data_get($entry->metadata, 'revision_number', 0);
                $supersededEntry = $supersededEntriesByRevision->get($revisionNumber);

                return [
                    'revision_number' => $revisionNumber,
                    'published_at' => optional($entry->created_at)?->toIso8601String(),
                    'published_by_name' => $entry->user?->name,
                    'publication_reason' => data_get($entry->metadata, 'reason'),
                    'publication_signature_name' => data_get($entry->metadata, 'signature_name'),
                    'publication_signature_role' => data_get($entry->metadata, 'signature_role'),
                    'is_current' => $currentPublishedRevision?->revision_number === $revisionNumber,
                    'superseded_at' => optional($supersededEntry?->created_at)?->toIso8601String(),
                    'superseded_by_revision_number' => data_get($supersededEntry?->metadata, 'superseded_by_revision_number'),
                ];
            })
            ->values()
            ->all();

        return [
            'current_revision_number' => $currentPublishedRevision?->revision_number,
            'current_published_at' => optional($currentPublishedRevision?->published_at)?->toIso8601String(),
            'current_publication_signature_name' => $currentPublishedRevision?->publication_signature_name,
            'current_publication_signature_role' => $currentPublishedRevision?->publication_signature_role,
            'history' => $history,
        ];
    }

    public function repairLegacyWorkingRevision(Project $project): ?ProjectDocumentRevision
    {
        $activeRevision = $this->activeWorkingRevision($project);
        if (!$activeRevision) {
            return null;
        }

        $originalSteps = $this->normalizeApprovalSteps($activeRevision->approval_steps);
        $activeSteps = $this->applyDefaultResponsibles($project, $originalSteps);
        if ($activeSteps !== $originalSteps) {
            $activeRevision->forceFill([
                'approval_steps' => $activeSteps,
            ])->save();
            $activeRevision->refresh();
        }

        $hasAssignedResponsible = collect($activeSteps)->contains(
            fn(array $step) => (int) ($step['responsible_user_id'] ?? 0) > 0
        );

        if ($hasAssignedResponsible) {
            return $activeRevision;
        }

        $fallbackRevision = $project->documentRevisions()
            ->where('status', '!=', 'published')
            ->where('id', '!=', $activeRevision->id)
            ->latest('revision_number')
            ->get()
            ->first(function (ProjectDocumentRevision $revision) {
                return collect($this->normalizeApprovalSteps($revision->approval_steps))
                    ->contains(fn(array $step) => (int) ($step['responsible_user_id'] ?? 0) > 0);
            });

        if (!$fallbackRevision) {
            return $activeRevision;
        }

        $activeRevision->forceFill(array_merge([
            'approval_steps' => $this->resetApprovalSteps(
                $this->applyDefaultResponsibles($project, $this->normalizeApprovalSteps($fallbackRevision->approval_steps))
            ),
        ], $this->draftWorkflowState()))->save();

        return $activeRevision->fresh([
            'user:id,name',
            'approvedBy:id,name',
            'publishedBy:id,name',
            'previousRevision:id,project_id,revision_number',
            'restoredFromRevision:id,project_id,revision_number',
        ]);
    }

    private function activeWorkingRevision(Project $project): ?ProjectDocumentRevision
    {
        return $this->currentWorkingRevision($project);
    }

    private function resolvePreviousRevision(Project $project, array $context): ?ProjectDocumentRevision
    {
        $baseRevisionId = (int) ($context['base_revision_id'] ?? 0);
        if ($baseRevisionId > 0) {
            return $project->documentRevisions()
                ->whereKey($baseRevisionId)
                ->first();
        }

        return $project->documentRevisions()
            ->latest('revision_number')
            ->first();
    }

    private function refreshWorkingRevision(
        Project $project,
        ProjectDocumentRevision $revision,
        ?User $user,
        string $triggerAction,
        array $context
    ): ProjectDocumentRevision {
        $revision->loadMissing('previousRevision');

        $snapshot = $this->buildSnapshot($project);
        $comparison = $this->compareSnapshots($revision->previousRevision?->snapshot, $snapshot);
        $steps = $this->resetApprovalSteps($this->normalizeApprovalSteps($revision->approval_steps));
        $steps = $this->applyDefaultResponsibles($project, $steps);

        $revision->forceFill(array_merge([
            'user_id' => $user?->id,
            'trigger_action' => $triggerAction,
            'summary' => $context['summary'] ?? $this->defaultSummary($triggerAction, $context),
            'status' => 'draft',
            'restored_from_revision_id' => array_key_exists('restored_from_revision_id', $context)
                ? $context['restored_from_revision_id']
                : $revision->restored_from_revision_id,
            'approval_steps' => $steps,
            'snapshot' => $snapshot,
            'comparison_summary' => $comparison,
        ], $this->draftWorkflowState()))->save();

        return $revision->fresh([
            'user:id,name',
            'approvedBy:id,name',
            'publishedBy:id,name',
            'previousRevision:id,project_id,revision_number',
            'restoredFromRevision:id,project_id,revision_number',
        ]);
    }

    private function resetApprovalSteps(array $steps): array
    {
        return collect($steps)
            ->map(function (array $step) {
                $step['approved'] = false;
                $step['completed_by_user_id'] = null;
                $step['completed_by_name'] = null;
                $step['completed_by_project_role'] = null;
                $step['completed_by_project_role_label'] = null;
                $step['completed_by_signature_name'] = null;
                $step['completed_by_signature_role'] = null;
                $step['approved_at'] = null;

                return $step;
            })
            ->values()
            ->all();
    }

    private function nextApprovalSteps(?ProjectDocumentRevision $previousRevision): array
    {
        if (!$previousRevision) {
            return $this->defaultApprovalSteps();
        }

        $previousSteps = $this->normalizeApprovalSteps($previousRevision->approval_steps);
        $hasAssignedResponsible = collect($previousSteps)->contains(
            fn(array $step) => (int) ($step['responsible_user_id'] ?? 0) > 0
        );

        if (!$hasAssignedResponsible) {
            return $this->defaultApprovalSteps();
        }

        return $this->resetApprovalSteps($previousSteps);
    }

    private function applyDefaultResponsibles(Project $project, array $steps): array
    {
        return collect($steps)
            ->map(function (array $step) use ($project) {
                if (($step['key'] ?? null) !== 'governance') {
                    return $step;
                }

                if ((int) ($step['responsible_user_id'] ?? 0) > 0) {
                    return $step;
                }

                $owner = $project->owner()->first(['id', 'name']);
                if (!$owner) {
                    return $step;
                }

                $step['responsible_user_id'] = $owner->id;
                $step['responsible_name'] = $owner->name;
                $step['responsible_project_role'] = 'owner';
                $step['responsible_project_role_label'] = $this->projectAccessRoleLabel('owner');

                return $step;
            })
            ->values()
            ->all();
    }

    private function draftWorkflowState(): array
    {
        return [
            'status' => 'draft',
            'approved_by_user_id' => null,
            'approved_at' => null,
            'approval_reason' => null,
            'published_by_user_id' => null,
            'published_at' => null,
            'publication_reason' => null,
            'publication_signature_name' => null,
            'publication_signature_role' => null,
        ];
    }

    private function extractStructuredMetadata(array $metadata): array
    {
        $structured = [];
        foreach (array_keys($this->structuredMetadataLabels()) as $field) {
            $structured[$field] = data_get($metadata, $field);
        }

        return $structured;
    }

    private function defaultSummary(string $triggerAction, array $context): string
    {
        return match ($triggerAction) {
            'document_generated' => 'Documento consolidado gerado ou regenerado',
            'section_updated' => 'Secao revisada: ' . ($context['section_title'] ?? 'documento'),
            'metadata_updated' => 'Metadados do projeto atualizados',
            'open_working_draft' => 'Novo rascunho aberto a partir da versão final publicada',
            'restore_revision' => 'Conteudo restaurado a partir de uma revisão anterior',
            default => 'Nova revisão registrada no projeto',
        };
    }

    private function defaultApprovalSteps(): array
    {
        return [
            [
                'key' => 'content',
                'label' => 'Conteudo',
                'description' => 'Valida coerencia das seções, clareza do texto e aderência ao objetivo do projeto.',
                'required_project_roles' => ['owner', 'editor'],
                'required_profile_label' => 'Editor ou proprietario do projeto',
                'responsible_user_id' => null,
                'responsible_name' => null,
                'responsible_project_role' => null,
                'responsible_project_role_label' => null,
                'responsible_assigned_by_user_id' => null,
                'responsible_assigned_by_name' => null,
                'responsible_assigned_by_role' => null,
                'responsible_assigned_at' => null,
                'approved' => false,
                'completed_by_user_id' => null,
                'completed_by_name' => null,
                'completed_by_project_role' => null,
                'completed_by_project_role_label' => null,
                'completed_by_signature_name' => null,
                'completed_by_signature_role' => null,
                'approved_at' => null,
            ],
            [
                'key' => 'technical',
                'label' => 'Consistencia tecnica',
                'description' => 'Confirma dados, cronograma, financiamento e alinhamento tecnico do documento.',
                'required_project_roles' => ['owner', 'editor'],
                'required_profile_label' => 'Editor ou proprietario do projeto',
                'responsible_user_id' => null,
                'responsible_name' => null,
                'responsible_project_role' => null,
                'responsible_project_role_label' => null,
                'responsible_assigned_by_user_id' => null,
                'responsible_assigned_by_name' => null,
                'responsible_assigned_by_role' => null,
                'responsible_assigned_at' => null,
                'approved' => false,
                'completed_by_user_id' => null,
                'completed_by_name' => null,
                'completed_by_project_role' => null,
                'completed_by_project_role_label' => null,
                'completed_by_signature_name' => null,
                'completed_by_signature_role' => null,
                'approved_at' => null,
            ],
            [
                'key' => 'governance',
                'label' => 'Governanca',
                'description' => 'Registra a conferencia final para liberar a publicacao oficial da revisão.',
                'required_project_roles' => ['owner'],
                'required_profile_label' => 'Proprietario do projeto',
                'responsible_user_id' => null,
                'responsible_name' => null,
                'responsible_project_role' => null,
                'responsible_project_role_label' => null,
                'responsible_assigned_by_user_id' => null,
                'responsible_assigned_by_name' => null,
                'responsible_assigned_by_role' => null,
                'responsible_assigned_at' => null,
                'approved' => false,
                'completed_by_user_id' => null,
                'completed_by_name' => null,
                'completed_by_project_role' => null,
                'completed_by_project_role_label' => null,
                'completed_by_signature_name' => null,
                'completed_by_signature_role' => null,
                'approved_at' => null,
            ],
        ];
    }

    private function signatureRole(Project $project, User $user): string
    {
        if ($user->isAdmin()) {
            return 'Administrador';
        }

        if ($project->owner_user_id === $user->id) {
            return 'Proprietario do projeto';
        }

        return 'Responsável autorizado';
    }

    private function ensureAllowedForApprovalStep(array $step, ?string $projectRole, string $action): void
    {
        $allowedRoles = $this->normalizeProjectRoles($step['required_project_roles'] ?? []);
        if (empty($allowedRoles) || in_array($projectRole, $allowedRoles, true)) {
            return;
        }

        $requiredProfile = $step['required_profile_label'] ?? 'perfil autorizado';
        throw new \RuntimeException("Esta etapa exige {$requiredProfile} para {$action} a responsabilidade formal.");
    }

    private function projectAccessRole(Project $project, User $user): string
    {
        if ($user->isAdmin()) {
            return 'admin';
        }

        if ($project->owner_user_id === $user->id) {
            return 'owner';
        }

        $collaborator = $project->collaborators()
            ->where('user_id', $user->id)
            ->whereNotNull('accepted_at')
            ->first();

        if (!$collaborator) {
            return 'guest';
        }

        return $collaborator->permission === 'editor' ? 'editor' : 'viewer';
    }

    private function projectAccessRoleLabel(?string $role): ?string
    {
        return match ($role) {
            'admin' => 'Administrador',
            'owner' => 'Proprietario do projeto',
            'editor' => 'Editor do projeto',
            'viewer' => 'Viewer do projeto',
            default => null,
        };
    }

    private function normalizeProjectRoles(mixed $roles): array
    {
        if (!is_array($roles)) {
            return [];
        }

        return collect($roles)
            ->filter(fn($role) => is_string($role) && $role !== '')
            ->values()
            ->all();
    }

    private function projectFieldLabels(): array
    {
        return [
            'title' => 'Título',
            'initial_idea' => 'Idéia inicial',
            'project_type' => 'Tipo',
            'status' => 'Status',
            'responsible_secretariat' => 'Secretaria responsável',
            'current_phase' => 'Fase atual',
            'generated_document_version' => 'Versão do documento',
        ];
    }

    private function structuredMetadataLabels(): array
    {
        return [
            'executive_summary' => 'Resumo executivo',
            'primary_goal' => 'Objetivo principal',
            'target_audience' => 'Público beneficiado',
            'territorial_scope' => 'Abrangencia territorial',
            'funding_strategy' => 'Estrategia de financiamento',
            'implementation_notes' => 'Notas de implementacao',
            'risk_notes' => 'Riscos e cuidados',
            'priority' => 'Prioridade',
            'expected_beneficiaries' => 'Beneficiários estimados',
            'estimated_budget' => 'Orcamento estimado',
            'expected_start_date' => 'Previsão de início',
            'expected_end_date' => 'Previsão de conclusao',
        ];
    }

    private function stringValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function excerpt(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return 'Sem conteudo';
        }

        return mb_strimwidth(preg_replace('/\s+/', ' ', $value) ?? $value, 0, 180, '...');
    }

    private function changeType(mixed $oldValue, mixed $newValue): string
    {
        $old = $this->stringValue($oldValue);
        $new = $this->stringValue($newValue);

        if ($old === '' && $new !== '') {
            return 'added';
        }

        if ($old !== '' && $new === '') {
            return 'removed';
        }

        return 'updated';
    }

    private function sectionChangeType(string $oldContent, string $newContent, bool $oldReview, bool $newReview): string
    {
        if ($oldContent === '' && $newContent !== '') {
            return 'added';
        }

        if ($oldContent !== '' && $newContent === '') {
            return 'removed';
        }

        if ($oldReview !== $newReview && $oldContent === $newContent) {
            return 'review_state';
        }

        return 'updated';
    }
}
