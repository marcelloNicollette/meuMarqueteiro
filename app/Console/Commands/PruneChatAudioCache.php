<?php

namespace App\Console\Commands;

use App\Services\AI\ChatAudioService;
use Illuminate\Console\Command;

class PruneChatAudioCache extends Command
{
    protected $signature = 'marqueteiro:prune-chat-audio';

    protected $description = 'Remove arquivos temporarios expirados do fallback de audio do chat';

    public function __construct(private ChatAudioService $chatAudio)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->chatAudio->pruneTemporaryAudio();

        $this->info('Limpeza do audio do chat concluida.');
        $this->line('Arquivos removidos: ' . $result['deleted_files']);
        $this->line('Disk: ' . $result['disk']);
        $this->line('Prefixo: ' . $result['prefix']);
        $this->line('TTL (min): ' . $result['ttl_minutes']);

        return self::SUCCESS;
    }
}
