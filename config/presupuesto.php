<?php

return [
    // Umbrales de semaforización financiera
    'semaforo' => [
        'verde_min' => 0.85,
        'verde_max' => 1.15,
        'amarillo_min' => 0.60,
        'amarillo_max' => 1.30,
    ],

    // Umbral de alerta de subejercicio (% del presupuesto no ejercido)
    'alerta_subejercicio_umbral' => 0.20,

    // Ejercicio fiscal por defecto (año actual)
    'ejercicio_default' => (int) date('Y'),
];
