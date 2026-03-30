<?php

namespace App\Console\Commands;

use App\Models\MonitoredUrl;
use App\Services\RAG\UrlIndexerService;
use Illuminate\Console\Command;

class IndexMonitoredUrls extends Command
{
    protected $signature = 'marqueteiro:index-urls
                                {--municipality= : ID do município (omitir = todos)}
                                {--id=           : ID de uma URL específica}
                                {--force         : Re-indexar mesmo as já indexadas}
                                {--all           : Indexar todas, não só as que precisam de refresh}';

    protected $description = 'Indexa URLs monitoradas no RAG (fetch + embeddings)';

    public function __construct(private UrlIndexerService $indexer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = MonitoredUrl::where('is_active', true);

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        } elseif ($mun = $this->option('municipality')) {
            $query->where('municipality_id', $mun);
        }

        $urls = $query->get();

        // Filtrar apenas as que precisam de refresh (a menos que --force ou --all)
        if (!$this->option('force') && !$this->option('all') && !$this->option('id')) {
            $urls = $urls->filter(fn($u) => $u->needsRefresh());
        }

        if ($urls->isEmpty()) {
            $this->info('Nenhuma URL para indexar agora.');
            return 0;
        }

        $this->info("Indexando {$urls->count()} URL(s)...");
        $bar = $this->output->createProgressBar($urls->count());

        $ok = 0; $fail = 0;

        foreach ($urls as $url) {
            $result = $this->indexer->index($url);

            if ($result['ok']) {
                $this->newLine();
                $this->line("  ✓ {$url->display_title} — {$result['chunks']} chunks");
                $ok++;
            } else {
                $this->newLine();
                $this->error("  ✗ {$url->url}: {$result['error']}");
                $fail++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Concluído: {$ok} OK, {$fail} falhas.");

        return $fail > 0 ? 1 : 0;
    }
}
