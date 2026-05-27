<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Provider padrão de IA
    |--------------------------------------------------------------------------
    | openai | anthropic | gemini
    */
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | Configurações por provider
    |--------------------------------------------------------------------------
    */
    'providers' => [

        'openai' => [
            'api_key'         => env('OPENAI_API_KEY'),
            'organization'    => env('OPENAI_ORGANIZATION'),
            'model'           => env('OPENAI_MODEL', 'gpt-4o'),
            'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
            'timeout'         => (int) env('OPENAI_TIMEOUT', 15),
            'connect_timeout' => (int) env('OPENAI_CONNECT_TIMEOUT', 5),
            'retry_attempts'  => (int) env('OPENAI_RETRY_ATTEMPTS', 1),
        ],

        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model'   => env('ANTHROPIC_MODEL', 'claude-opus-4-6'),
            'timeout' => (int) env('ANTHROPIC_TIMEOUT', 45),
            'connect_timeout' => (int) env('ANTHROPIC_CONNECT_TIMEOUT', 5),
            'retry_attempts' => (int) env('ANTHROPIC_RETRY_ATTEMPTS', 2),
        ],

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model'   => env('GEMINI_MODEL', 'gemini-1.5-pro'),
            'timeout' => (int) env('GEMINI_TIMEOUT', 15),
            'connect_timeout' => (int) env('GEMINI_CONNECT_TIMEOUT', 5),
            'retry_attempts' => (int) env('GEMINI_RETRY_ATTEMPTS', 1),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Audio do chat
    |--------------------------------------------------------------------------
    */
    'audio' => [
        'openai' => [
            'transcription_model' => env('OPENAI_AUDIO_TRANSCRIPTION_MODEL', 'gpt-4o-mini-transcribe'),
            'speech_model' => env('OPENAI_AUDIO_SPEECH_MODEL', 'gpt-4o-mini-tts'),
            'voice' => env('OPENAI_AUDIO_VOICE', 'alloy'),
        ],
        'cache' => [
            'disk' => env('CHAT_AUDIO_CACHE_DISK', 'local'),
            'prefix' => env('CHAT_AUDIO_CACHE_PREFIX', 'chat-audio-temp'),
            'ttl_minutes' => (int) env('CHAT_AUDIO_CACHE_TTL_MINUTES', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ferramentas externas (paths configuráveis)
    |--------------------------------------------------------------------------
    */
    'tools' => [
        // Configure via .env: PDFTOTEXT_BIN=/var/www/meuMarqueteiro/bin/pdftotext
        'pdftotext_bin' => env('PDFTOTEXT_BIN', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de RAG (pgvector)
    |--------------------------------------------------------------------------
    */
    'rag' => [
        'dimensions'           => (int) (env('VECTOR_DIMENSIONS') ?: 1536),
        'similarity_threshold' => (float) env('VECTOR_SIMILARITY_THRESHOLD', 0.45),
        'fallback_similarity_threshold' => (float) env('VECTOR_FALLBACK_SIMILARITY_THRESHOLD', 0.35),
        'max_results'          => (int) env('VECTOR_MAX_RESULTS', 10),
        'chunk_size'           => 800,    // palavras por chunk
        'chunk_overlap'        => 100,    // palavras de sobreposição
    ],

    /*
    |--------------------------------------------------------------------------
    | Briefing Matinal
    |--------------------------------------------------------------------------
    */
    'morning_briefing' => [
        'hour'   => (int) env('MORNING_BRIEFING_HOUR', 6),
        'minute' => (int) env('MORNING_BRIEFING_MINUTE', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Limites de uso por tier
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'essencial' => [
            'daily_messages'    => 20,
            'monthly_tokens'    => 500_000,
            'content_per_month' => 30,
        ],
        'estrategico' => [
            'daily_messages'    => 50,
            'monthly_tokens'    => 1_500_000,
            'content_per_month' => 100,
        ],
        'parceiro' => [
            'daily_messages'    => -1,   // ilimitado
            'monthly_tokens'    => -1,
            'content_per_month' => -1,
        ],
    ],

];
