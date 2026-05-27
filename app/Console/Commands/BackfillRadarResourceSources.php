<?php

namespace App\Console\Commands;

use App\Models\FederalProgramAlert;
use App\Models\ResourceSource;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class BackfillRadarResourceSources extends Command
{
    protected $signature = 'marqueteiro:backfill-radar-sources
                                {--municipality= : ID do município para restringir o backfill}
                                {--limit=0       : Limite maximo de registros a processar}
                                {--dry-run       : Apenas simula as alteracoes sem salvar}
                                {--force         : Recalcula mesmo quando source_key e resource_source_id ja existem}';

    protected $description = 'Normaliza source_key, source_platform e resource_source_id das oportunidades do Radar de Recursos';

    private ?array $resourceSourceCatalog = null;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $limit = max(0, (int) $this->option('limit'));
        $municipalityId = $this->option('municipality');

        $query = FederalProgramAlert::query()
            ->when($municipalityId, fn (Builder $builder) => $builder->where('municipality_id', $municipalityId))
            ->when(!$force, function (Builder $builder) {
                $builder->where(function (Builder $scoped) {
                    $scoped->whereNull('source_key')
                        ->orWhereNull('resource_source_id')
                        ->orWhereIn('source_platform', ['transparencia', 'ministerio']);
                });
            })
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $alerts = $query->get();

        if ($alerts->isEmpty()) {
            $this->warn('Nenhuma oportunidade encontrada para backfill com os filtros informados.');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Iniciando backfill do Radar de Recursos para %d oportunidade(s)%s.',
            $alerts->count(),
            $dryRun ? ' [DRY-RUN]' : ''
        ));

        $updated = 0;
        $skipped = 0;

        $headers = ['ID', 'Municipio', 'Antes', 'Depois', 'Fonte'];
        $previewRows = [];

        foreach ($alerts as $alert) {
            $before = [
                'source_platform' => $alert->source_platform,
                'source_key' => $alert->source_key,
                'resource_source_id' => $alert->resource_source_id,
            ];

            $sourceKey = $this->resolveSourceKey($alert);
            $source = $this->resourceSourceFor($sourceKey);
            $newValues = [
                'source_platform' => $sourceKey,
                'source_key' => $sourceKey,
                'resource_source_id' => $source['id'] ?? null,
                'capture_method' => $source['capture_method'] ?? $alert->capture_method,
                'resource_scope' => $source['resource_scope'] ?? $alert->resource_scope,
            ];

            if (
                $before['source_platform'] === $newValues['source_platform']
                && $before['source_key'] === $newValues['source_key']
                && (int) ($before['resource_source_id'] ?? 0) === (int) ($newValues['resource_source_id'] ?? 0)
            ) {
                $skipped++;
                continue;
            }

            if (count($previewRows) < 12) {
                $previewRows[] = [
                    $alert->id,
                    $alert->municipality_id,
                    ($before['source_key'] ?? $before['source_platform'] ?? 'null') . ' / ' . ($before['resource_source_id'] ?? 'null'),
                    $newValues['source_key'] . ' / ' . ($newValues['resource_source_id'] ?? 'null'),
                    $source['name'] ?? $sourceKey,
                ];
            }

            if (!$dryRun) {
                $alert->fill($newValues);
                $alert->save();
            }

            $updated++;
        }

        if ($previewRows !== []) {
            $this->newLine();
            $this->table($headers, $previewRows);
        }

        $this->newLine();
        $this->line("Atualizados: {$updated}");
        $this->line("Sem alteracao: {$skipped}");

        if ($dryRun) {
            $this->comment('Nenhuma alteracao foi persistida.');
        } else {
            $this->info('Backfill concluido com sucesso.');
        }

        return self::SUCCESS;
    }

    private function resolveSourceKey(FederalProgramAlert $alert): string
    {
        $currentKey = $this->normalizeSourceAlias($alert->source_key);

        if ($this->isCanonicalSourceKey($currentKey)) {
            return $currentKey;
        }

        $platform = $this->normalizeSourceAlias($alert->source_platform);
        if ($this->isCanonicalSourceKey($platform) && $platform !== 'portal_transparencia') {
            return $platform;
        }

        $ministry = mb_strtolower((string) ($alert->ministry ?? ''));
        $sourceUrl = mb_strtolower((string) ($alert->source_url ?? ''));
        $programName = mb_strtolower((string) ($alert->program_name ?? ''));
        $description = mb_strtolower((string) ($alert->description ?? ''));
        $fundingType = mb_strtolower((string) ($alert->funding_type ?? ''));

        if (
            str_contains($sourceUrl, 'portaldatransparencia')
            || $platform === 'portal_transparencia'
        ) {
            if (
                $fundingType === 'emenda'
                || str_contains($sourceUrl, '/emendas/')
                || str_contains($programName, 'emenda')
                || str_contains($description, 'emenda')
            ) {
                return 'emendas_parlamentares';
            }

            return 'portal_transparencia';
        }

        if (
            $platform === 'transferegov'
            || str_contains($sourceUrl, 'transferegov')
        ) {
            return 'transferegov';
        }

        if (
            $platform === 'fnde'
            || str_contains($ministry, 'fnde')
            || str_contains($sourceUrl, 'fnde')
        ) {
            return 'fnde';
        }

        if (
            $platform === 'caixa'
            || str_contains($ministry, 'caixa')
            || str_contains($sourceUrl, 'caixa.gov.br')
        ) {
            return 'caixa';
        }

        if (
            str_contains($ministry, 'fundo nacional de saude')
            || str_contains($programName, 'saude bucal')
            || str_contains($sourceUrl, 'saude.gov.br')
        ) {
            return 'fns';
        }

        if (
            str_contains($ministry, 'assistencia social')
            || str_contains($ministry, 'mds')
            || str_contains($sourceUrl, '/fnas')
        ) {
            return 'fnas';
        }

        if (str_contains($sourceUrl, 'funasa')) {
            return 'funasa';
        }

        if (str_contains($sourceUrl, 'bndes')) {
            return 'bndes';
        }

        if (str_contains($sourceUrl, 'bb.com.br')) {
            return 'banco_brasil';
        }

        if (str_contains($sourceUrl, 'finep')) {
            return 'finep';
        }

        if (str_contains($sourceUrl, 'in.gov.br')) {
            return 'diario_oficial_uniao';
        }

        return 'portal_transparencia';
    }

    private function normalizeSourceAlias(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            '' => '',
            'portal', 'transparencia', 'portal_da_transparencia', 'portal_transparencia_federal' => 'portal_transparencia',
            'emenda_parlamentar', 'emendas', 'emendas_parlamentar' => 'emendas_parlamentares',
            'dou', 'diario_oficial' => 'diario_oficial_uniao',
            default => preg_replace('/[^a-z0-9_]+/', '_', $normalized) ?: '',
        };
    }

    private function isCanonicalSourceKey(string $key): bool
    {
        return array_key_exists($key, $this->resourceSourceCatalog());
    }

    private function resourceSourceFor(string $key): ?array
    {
        $catalog = $this->resourceSourceCatalog();

        return $catalog[$key] ?? null;
    }

    private function resourceSourceCatalog(): array
    {
        if ($this->resourceSourceCatalog !== null) {
            return $this->resourceSourceCatalog;
        }

        $this->resourceSourceCatalog = ResourceSource::query()
            ->get(['id', 'key', 'name', 'capture_method', 'resource_scope'])
            ->mapWithKeys(fn (ResourceSource $source) => [
                $source->key => [
                    'id' => $source->id,
                    'name' => $source->name,
                    'capture_method' => $source->capture_method,
                    'resource_scope' => $source->resource_scope,
                ],
            ])
            ->all();

        return $this->resourceSourceCatalog;
    }
}
