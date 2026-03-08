<?php

return [
    'api_key' => env('LLM_API_KEY', ''),
    'api_url' => env('LLM_API_URL', 'https://api.openai.com/v1/chat/completions'),
    'model' => env('LLM_MODEL', 'gpt-4o-mini'),
    'max_tokens' => (int) env('LLM_MAX_TOKENS', 2000),
    'temperature' => (float) env('LLM_TEMPERATURE', 0.7),
    'timeout' => (int) env('LLM_TIMEOUT', 60),

    'rate_limit' => [
        'max_per_minute' => (int) env('LLM_RATE_LIMIT', 30),
    ],

    'queue' => [
        'name' => env('LLM_QUEUE', 'llm'),
        'tries' => (int) env('LLM_JOB_TRIES', 3),
        'backoff' => [10, 60, 300],
        'timeout' => (int) env('LLM_JOB_TIMEOUT', 120),
    ],

    'logging' => [
        'enabled' => env('LLM_LOGGING_ENABLED', true),
        'channel' => env('LLM_LOG_CHANNEL', 'stack'),
    ],

    'cache' => [
        'enabled' => env('LLM_CACHE_ENABLED', true),
        'ttl' => (int) env('LLM_CACHE_TTL', 3600),
        'store' => env('LLM_CACHE_STORE', null),
    ],

    'fallback' => [
        'enabled' => env('LLM_FALLBACK_ENABLED', true),
        'message' => 'Análisis de IA no disponible temporalmente.',
    ],

    'prompts' => [
        'manifest_path' => resource_path('views/prompts/manifest.json'),
    ],
];
