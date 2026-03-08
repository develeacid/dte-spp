<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración del Servicio de Embeddings
    |--------------------------------------------------------------------------
    |
    | Este archivo centraliza toda la configuración relacionada con la generación
    | y búsqueda de embeddings vectoriales.
    |
    */

    // ============================================
    // API Configuration
    // ============================================

    'api_key' => env('EMBEDDING_API_KEY'),
    'api_url' => env('EMBEDDING_API_URL', 'https://api.openai.com/v1/embeddings'),
    'model' => env('EMBEDDING_MODEL', 'text-embedding-ada-002'),
    'dimension' => env('EMBEDDING_DIMENSION', 1536),

    // ============================================
    // Rate Limiting
    // ============================================

    // Máximo número de requests por minuto al API
    'rate_limit' => env('EMBEDDING_RATE_LIMIT', 60),

    // Timeout en segundos para requests
    'timeout' => env('EMBEDDING_TIMEOUT', 30),

    // ============================================
    // Queue Configuration
    // ============================================

    // Cola específica para jobs de embeddings
    'queue' => env('EMBEDDING_QUEUE', 'embeddings'),

    // Número de reintentos antes de marcar como failed
    'tries' => env('EMBEDDING_JOB_TRIES', 3),

    // Backoff exponencial entre reintentos (segundos)
    'backoff' => [10, 60, 300],

    // Tiempo máximo de ejecución del job
    'timeout_job' => env('EMBEDDING_JOB_TIMEOUT', 60),

    // ============================================
    // Chunking
    // ============================================

    // Máximo de tokens por request (aproximación: 4 chars = 1 token)
    'max_tokens' => env('EMBEDDING_MAX_TOKENS', 8000),

    // ============================================
    // Observers
    // ============================================

    // Habilitar/deshabilitar observers globalmente (útil para tests)
    'observers_enabled' => env('EMBEDDING_OBSERVERS_ENABLED', true),
];
