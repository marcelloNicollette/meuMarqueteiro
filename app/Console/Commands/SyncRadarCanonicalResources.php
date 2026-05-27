<?php

namespace App\Console\Commands;

use App\Models\FederalProgramAlert;
use App\Services\Radar\CanonicalResourceSyncService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class SyncRadarCanonicalResources extends Command
{
    protected $signature = 'marqueteiro:sync-radar-canonical
                                {--municipality= : ID do município para restringir o espelhamento}
                                {--limit=0       : Limite maximo de registros a processar}
                                {--dry-run       : Apenas simula o espelhamento sem persistir}
                                {--only-active   : Processa apenas registros ativos do radar}';

    protected $description = 'Espelha os registros legados do Radar de Recursos para a camada canonica da Fase 2';

    public function __construct(
        private readonly CanonicalResourceSyncService $canonicalSync,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!$this->canonicalSync->isEnabled()) {
            $this->error('A camada canonica ainda não esta disponível. Rode as migrations da Fase 2 antes do espelhamento.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $municipalityId = $this->option('municipality');
        $onlyActive = (bool) $this->option('only-active');

        $query = FederalProgramAlert::query()
            ->when($municipalityId, fn (Builder $builder) => $builder->where('municipality_id', $municipalityId))
            ->when($onlyActive, fn (Builder $builder) => $builder->whereIn('status', ['published', 'closing_soon', 'monitoring', 'reopened']))
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $alerts = $query->get();

        if ($alerts->isEmpty()) {
            $this->warn('Nenhum alerta legado encontrado para espelhamento.');
            return self::SUCCESS;
        }

        $processed = 0;
        $preview = [];

        foreach ($alerts as $alert) {
            if (count($preview) < 12) {
                $preview[] = [
                    $alert->id,
                    $alert->municipality_id,
                    $alert->program_name,
                    $alert->source_key ?? $alert->source_platform ?? 'sem_fonte',
                    $alert->status,
                ];
            }

            if (!$dryRun) {
                $this->canonicalSync->syncFromAlert($alert);
            }

            $processed++;
        }

        $this->info(sprintf(
            'Espelhamento canonico %s para %d alerta(s).',
            $dryRun ? 'simulado' : 'executado',
            $processed
        ));

        if ($preview !== []) {
            $this->newLine();
            $this->table(['ID', 'Municipio', 'Programa', 'Fonte', 'Status'], $preview);
        }

        return self::SUCCESS;
    }
}
