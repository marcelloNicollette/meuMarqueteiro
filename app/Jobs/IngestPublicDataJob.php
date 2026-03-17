<?php

namespace App\Jobs;

use App\Models\Municipality;
use App\Services\DataIngestion\DataIngestionOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IngestPublicDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;   // 5 min por município
    public int $tries   = 2;

    public function __construct(
        public readonly Municipality $municipality
    ) {}

    public function handle(DataIngestionOrchestrator $orchestrator): void
    {
        Log::info("IngestPublicDataJob iniciado para {$this->municipality->name}");

        $report = $orchestrator->ingest($this->municipality);

        Log::info("IngestPublicDataJob concluído para {$this->municipality->name}", $report);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("IngestPublicDataJob falhou para {$this->municipality->name}: " . $e->getMessage());
    }
}
