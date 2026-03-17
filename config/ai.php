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
        ],

        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model'   => env('ANTHROPIC_MODEL', 'claude-opus-4-6'),
        ],

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model'   => env('GEMINI_MODEL', 'gemini-1.5-pro'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de RAG (pgvector)
    |--------------------------------------------------------------------------
    */
    'rag' => [
        'dimensions'           => (int) (env('VECTOR_DIMENSIONS') ?: 1536),
        'similarity_threshold' => (float) env('VECTOR_SIMILARITY_THRESHOLD', 0.75),
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
