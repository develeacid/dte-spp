<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Glosario de terminos MML (Metodologia de Marco Logico)
    |--------------------------------------------------------------------------
    |
    | Definiciones extraidas de la normatividad SHCP/CONEVAL y los prompts
    | de validacion del sistema. Se usan para tooltips de ayuda contextual.
    |
    */

    // ── Niveles MIR ──────────────────────────────────────────────────

    'fin' => 'Nivel superior de la MIR. Describe la contribucion del programa a un objetivo de desarrollo superior. '
        .'Sintaxis SHCP: "Contribuir a [impacto esperado] mediante [solucion principal]". '
        .'Debe incluir un impacto claro y medible, y ser una sola oracion.',

    'proposito' => 'Resultado directo esperado sobre la poblacion objetivo. '
        .'Sintaxis SHCP: "[Poblacion objetivo] + [verbo en presente/participio] + [condicion o resultado esperado]". '
        .'Describe el cambio en la poblacion, no actividades ni productos.',

    'componente' => 'Bienes o servicios que produce el programa. '
        .'Sintaxis SHCP: "[Bien o servicio] + [participio pasado (-ado/-ido)]". '
        .'Ejemplos: "Becas otorgadas", "Talleres de capacitacion realizados". '
        .'Es un producto terminado, no una accion en proceso.',

    'actividad' => 'Acciones necesarias para producir cada Componente. '
        .'Sintaxis SHCP: "[Sustantivo deverbal] + [complemento]". '
        .'Debe iniciar con un sustantivo deverbal (Elaboracion, Diseno, Distribucion), '
        .'NO con un verbo en infinitivo.',

    // ── Columnas MIR ─────────────────────────────────────────────────

    'resumen_narrativo' => 'Descripcion del objetivo de cada nivel de la MIR. '
        .'Cada nivel tiene una formula sintactica obligatoria definida por la SHCP. '
        .'El resumen debe ser una sola oracion clara y concreta.',

    'supuestos' => 'Condiciones externas que deben cumplirse para que la logica causal funcione. '
        .'Son factores fuera del control del programa que, de no cumplirse, '
        .'impedirian alcanzar el objetivo del nivel correspondiente.',

    'indicador' => 'Expresion cuantitativa que mide el logro del objetivo de cada nivel. '
        .'Puede ser de tipo estrategico (mide Fin y Proposito) '
        .'o de gestion (mide Componentes y Actividades). '
        .'Debe definir nombre, formula, tipo, dimension y frecuencia.',

    'medios_verificacion' => 'Fuentes de informacion que permiten verificar el valor del indicador. '
        .'Deben poder proporcionar los datos necesarios para calcular la formula del indicador. '
        .'Su frecuencia debe ser compatible con la frecuencia del indicador.',

    // ── CREMAA ───────────────────────────────────────────────────────

    'cremaa' => 'Criterios de calidad para indicadores segun CONEVAL: '
        .'Claro, Relevante, Economico, Monitoreable, Adecuado y Aportante.',

    'cremaa_claro' => 'El indicador es facil de entender. Su nombre y formula son comprensibles sin ambiguedad.',

    'cremaa_relevante' => 'El indicador refleja adecuadamente el objetivo del nivel al que pertenece.',

    'cremaa_economico' => 'El indicador se puede medir sin costo excesivo. Los datos estan disponibles.',

    'cremaa_monitoreable' => 'El indicador es sujeto de verificacion independiente. Se puede auditar.',

    'cremaa_adecuado' => 'El indicador es proporcional al objetivo medido. No es ni muy amplio ni muy estrecho.',

    'cremaa_aportante' => 'El indicador provee informacion util para la toma de decisiones. '
        .'Tambien llamado "Aportacion marginal".',

    // ── Logica MIR ───────────────────────────────────────────────────

    'logica_vertical' => 'Coherencia de la cadena causal entre niveles de la MIR: '
        .'las Actividades deben ser suficientes y necesarias para producir sus Componentes; '
        .'los Componentes deben ser suficientes y necesarios para lograr el Proposito; '
        .'el Proposito debe contribuir directamente al Fin. '
        .'No debe haber saltos logicos entre niveles.',

    'logica_horizontal' => 'Consistencia dentro de cada fila de la MIR: '
        .'el indicador debe medir lo descrito en el Resumen Narrativo; '
        .'la dimension del indicador debe ser apropiada para el nivel; '
        .'el medio de verificacion debe proporcionar los datos para calcular el indicador; '
        .'la frecuencia del medio debe ser compatible con la del indicador.',

    // ── Indicador: atributos ─────────────────────────────────────────

    'tipo_indicador_estrategico' => 'Indicador de tipo estrategico: mide los niveles de Fin y Proposito. '
        .'Evalua el logro de resultados e impactos.',

    'tipo_indicador_gestion' => 'Indicador de tipo gestion: mide los niveles de Componente y Actividad. '
        .'Evalua procesos, productos y servicios entregados.',

    'sentido_ascendente' => 'Un valor mayor del indicador refleja un mejor desempeno. '
        .'Ejemplo: tasa de cobertura, porcentaje de aprobacion.',

    'sentido_descendente' => 'Un valor menor del indicador refleja un mejor desempeno. '
        .'Ejemplo: tasa de desercion, indice de mortalidad.',

    'sentido_regular' => 'El desempeno optimo se alcanza cuando el indicador se mantiene '
        .'en un valor o rango especifico, sin que mas o menos sea mejor.',

    // ── Arbol de problemas / objetivos ───────────────────────────────

    'problema_central' => 'Situacion no deseada que el programa busca atender. '
        .'Debe ser una condicion negativa verificable, no la ausencia de una solucion. '
        .'No debe contener verbos que impliquen soluciones (implementar, crear, mejorar).',

    'causa_directa' => 'Factor que contribuye directamente a producir el problema central.',

    'causa_indirecta' => 'Factor que contribuye indirectamente, a traves de una causa directa.',

    'efecto_directo' => 'Consecuencia inmediata del problema central.',

    'efecto_indirecto' => 'Consecuencia de segundo orden, derivada de un efecto directo.',

    'formula_indicador' => 'Expresion matematica que define como se calcula el indicador. '
        .'Usa variables con simbolos (A, B, C...). Ejemplo: "(A / B) x 100".',

];
