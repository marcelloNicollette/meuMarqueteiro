<?php

namespace App\Console\Commands;

use App\Models\Municipality;
use App\Services\Social\SocialMonitorService;
use Illuminate\Console\Command;

class MonitorSocialMentions extends Command
{
    protected $signature = 'marqueteiro:monitor-mentions
                                {--municipality= : ID do município (omitir = todos)}
                                {--analyze-only  : Só analisar sentimento das pendentes, não  buscar novas}';

    protected $description = 'Monitora menções nas redes sociais e notícias para todos os municípios';

    public function __construct(private SocialMonitorService $monitor)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = Municipality::where('subscription_active', true);

        if ($id = $this->option('municipality')) {
            $query->where('id', $id);
        }

        $municipalities = $query->get();

        if ($municipalities->isEmpty()) {
            $this->warn('Nenhum município ativo.');
            return 0;
        }

        $totalNew = 0;

        foreach ($municipalities as $municipality) {
            $this->line("→ Monitorando {$municipality->name}...");

            if ($this->option('analyze-only')) {
                $this->monitor->analyzeSentimentBatch($municipality);
                $this->line("  ✓ Sentimentos analisados");
                continue;
            }

            $result = $this->monitor->monitor($municipality);

            $this->line("  ✓ {$result['found']} menções encontradas, {$result['new']} novas");

            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $err) {
                    $this->warn("  ⚠ {$err}");
                }
            }

            $totalNew += $result['new'];
        }

        $this->info("Concluído. {$totalNew} novas menções no total.");
        return 0;
    }
}
