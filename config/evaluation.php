<?php

return [
    'pesos' => [
        'fin' => (float) env('EVAL_PESO_FIN', 0.40),
        'proposito' => (float) env('EVAL_PESO_PROPOSITO', 0.30),
        'componente' => (float) env('EVAL_PESO_COMPONENTE', 0.20),
        'actividad' => (float) env('EVAL_PESO_ACTIVIDAD', 0.10),
    ],

    'exports' => [
        'storage_disk' => 'local',
        'storage_path' => 'reportes',
        'ttl_hours' => 24,
        'encabezado' => [
            'institucion' => env('REPORT_INSTITUCION', 'Gobierno del Estado'),
            'dependencia' => env('REPORT_DEPENDENCIA', 'Secretaría de Planeación'),
            'ejercicio' => env('APP_EJERCICIO_FISCAL', date('Y')),
        ],
    ],
];
