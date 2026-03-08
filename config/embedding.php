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
    // Batch Processing
    // ============================================

    'batch' => [
        // Default chunk size for batch processing
        'chunk_size' => env('EMBEDDING_BATCH_CHUNK_SIZE', 50),

        // Delay between chunks in milliseconds
        'delay_ms' => env('EMBEDDING_BATCH_DELAY_MS', 1000),

        // Max retries per record on API failure
        'max_retries' => env('EMBEDDING_BATCH_MAX_RETRIES', 3),

        // Base backoff in seconds (doubles each retry: 1s, 2s, 4s)
        'backoff_base' => env('EMBEDDING_BATCH_BACKOFF_BASE', 1),
    ],

    // ============================================
    // Observers
    // ============================================

    // Habilitar/deshabilitar observers globalmente (útil para tests)
    'observers_enabled' => env('EMBEDDING_OBSERVERS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Semantic Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para el servicio de búsqueda semántica.
    |
    */

    // Umbral mínimo de similitud para considerar un resultado relevante (0-1)
    'similarity_threshold' => env('EMBEDDING_SIMILARITY_THRESHOLD', 0.7),

    // Número máximo de resultados por defecto
    'max_results' => env('EMBEDDING_MAX_RESULTS', 5),

    /*
    |--------------------------------------------------------------------------
    | HNSW Index Configuration
    |--------------------------------------------------------------------------
    |
    | Parámetros para índices Hierarchical Navigable Small World (HNSW).
    | Optimizados para búsquedas vectoriales de alta velocidad.
    |
    */

    // Parámetro M: número de conexiones bidireccionales por nodo
    // Valores más altos = mejor recall, más memoria
    'hnsw_m' => env('EMBEDDING_HNSW_M', 16),

    // Parámetro ef_construction: tamaño de la lista de candidatos dinámicos
    // Valores más altos = mejor calidad de índice, construcción más lenta
    'hnsw_ef_construction' => env('EMBEDDING_HNSW_EF_CONSTRUCTION', 64),

    // Parámetro ef_search: tamaño de la lista de candidatos durante búsqueda
    // Valores más altos = mejor recall, búsqueda más lenta
    'hnsw_ef_search' => env('EMBEDDING_HNSW_EF_SEARCH', 40),
];
