<?php

namespace App\Services\AI;

readonly class AIResponse
{
    public function __construct(
        public string $content,
        public string $provider,
        public string $model,
        public int    $tokensUsed,
        public string $finishReason = 'stop',
    ) {}
}
