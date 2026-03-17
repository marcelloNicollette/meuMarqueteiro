<?php

namespace App\Services\FederalPrograms;

use App\Models\FederalProgramAlert;
use App\Models\Municipality;
use App\Models\SyncLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\OutputInterface;

class FederalProgramSyncService
{
    // ── Mapeamento de áreas ──────────────────────────────────────────────
    private const AREA_MAP = [
        'saude'          => ['saude', 'saúde', 'sus', 'ubs', 'atenção básica', 'hospitalar', 'vigilância sanitária'],
        'educacao'       => ['educação', 'educacao', 'escola', 'ensino', 'fnde', 'creche', 'alfabetização'],
        'infraestrutura' => ['infraestrutura', 'pavimentação', 'pavimentacao', 'estrada', 'ponte', 'iluminação', 'mobilidade'],
        'saneamento'     => ['saneamento', 'esgoto', 'água', 'agua', 'abastecimento', 'resíduos', 'residuos'],
        'habitacao'      => ['habitação', 'habitacao', 'moradia', 'casa', 'reassentamento'],
        'social'         => ['social', 'assistência', 'assistencia', 'cras', 'creas', 'vulnerabilidade', 'criança', 'idoso'],
        'meio_ambiente'  => ['ambiental', 'meio ambiente', 'floresta', 'clima', 'resíduos sólidos'],
        'economia'       => ['desenvolvimento econômico', 'emprego', 'turismo', 'agropecuária', 'bndes'],
    ];

    // ── Constructor ─────────────────────────────────────────────────────
    public function __construct(
        private TransferegovClient  $transferegov,
        private TransparenciaClient $transparencia,
        private ClaudeMatchingService $claude,
    ) {}

    // ── Ponto de entrada principal ───────────────────────────────────────
    public function sync(
        Municipality    $municipality,
        bool            $force   = false,
        bool            $dryRun  = false,
        ?OutputInterface $output = null,
    ): array {
        $result = [
            'novos'         => 0,
            'atualizados'   => 0,
            'descartados'   => 0,
            'transferegov'  => 0,
            'transparencia' => 0,
        ];

        // 1. Coletar programas brutos das duas APIs
        $raw = [];

        $log = fn(string $msg) => $output?->writeln("    <fg=yellow>…</> {$msg}");

        $log('Buscando Transferegov...');
        try {
            $tg = $this->transferegov->fetchByMunicipality($municipality->ibge_code);
            $result['transferegov'] = count($tg);
            $raw = array_merge($raw, $tg);
        } catch (\Exception $e) {
            Log::warning("Transferegov fetch error: " . $e->getMessage());
            $log("<fg=red>Transferegov indisponível: {$e->getMessage()}</>");
        }

        $log('Buscando Portal da Transparência...');
        try {
            $tp = $this->transparencia->fetchTransfers($municipality->ibge_code);
            $result['transparencia'] = count($tp);
            $raw = array_merge($raw, $tp);
        } catch (\Exception $e) {
            Log::warning("Transparencia fetch error: " . $e->getMessage());
            $log("<fg=red>Transparência indisponível: {$e->getMessage()}</>");
        }

        if (empty($raw)) {
            Log::info("sync-federal [{$municipality->id}]: sem programas brutos para processar.");
            return $result;
        }

        $log("Total bruto: " . count($raw) . " programas. Enviando ao Claude para análise...");

        // 2. Claude avalia elegibilidade em lote
        $evaluated = $this->claude->evaluateBatch($municipality, $raw);

        // 3. Salvar / atualizar
        foreach ($evaluated as $item) {
            if (($item['match_score'] ?? 0) < 0.30) {
                $result['descartados']++;
                continue;
            }

            if ($dryRun) {
                $output?->writeln("    [DRY-RUN] {$item['program_name']} — score: {$item['match_score']}");
                $result['novos']++;
                continue;
            }

            $existing = FederalProgramAlert::where('municipality_id', $municipality->id)
                ->where('program_code', $item['program_code'])
                ->first();

            if ($existing) {
                if ($force || $existing->status !== 'closed') {
                    $existing->update($item);
                    $result['atualizados']++;
                }
            } else {
                FederalProgramAlert::create(array_merge($item, [
                    'municipality_id' => $municipality->id,
                    'ai_matched'      => true,
                ]));
                $result['novos']++;
            }
        }

        // Atualizar timestamp do município
        if (!$dryRun) {
            $municipality->update(['data_last_synced_at' => now()]);
        }

        // Registrar no sync_log se a tabela existir
        $this->writeSyncLog($municipality, $result, $dryRun);

        return $result;
    }

    // ── Inferir área a partir do texto ───────────────────────────────────
    public static function inferArea(string $text): string
    {
        $lower = mb_strtolower($text);
        foreach (self::AREA_MAP as $area => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($lower, $kw)) return $area;
            }
        }
        return 'outros';
    }

    private function writeSyncLog(Municipality $municipality, array $result, bool $dryRun): void
    {
        try {
            if (\Schema::hasTable('sync_logs')) {
                \DB::table('sync_logs')->insert([
                    'municipality_id' => $municipality->id,
                    'type'            => 'federal_programs',
                    'status'          => 'success',
                    'details'         => json_encode(array_merge($result, ['dry_run' => $dryRun])),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        } catch (\Exception $e) {
            // Tabela pode não existir ainda — não é crítico
        }
    }
}
