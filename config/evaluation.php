<?php

return [
    'pesos' => [
        'fin' => (float) env('EVAL_PESO_FIN', 0.40),
        'proposito' => (float) env('EVAL_PESO_PROPOSITO', 0.30),
        'componente' => (float) env('EVAL_PESO_COMPONENTE', 0.20),
        'actividad' => (float) env('EVAL_PESO_ACTIVIDAD', 0.10),
    ],
];
