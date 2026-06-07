<?php

return [
    // Ventana normativa SHCP de captura: días después del cierre del periodo (trimestre/mes/semestre/año)
    'dias_ventana_captura' => env('TRACKING_DIAS_VENTANA_CAPTURA', 30),

    // Umbral de sobrecumplimiento (% sobre la meta) a partir del cual el semáforo
    // marca 'rojo_alto' en el path fallback por meta. Default 130%.
    'umbral_sobrecumplimiento' => env('TRACKING_UMBRAL_SOBRECUMPLIMIENTO', 130),
];
