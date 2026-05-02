<?php

namespace App\Enums;

enum EtapaMml: string
{
    case DEFINICION_PROBLEMA = 'definicion_problema';
    case ARBOL_PROBLEMA = 'arbol_problema';
    case ARBOL_OBJETIVOS = 'arbol_objetivos';
    case SELECCION_ALTERNATIVAS = 'seleccion_alternativas';
    case ESTRUCTURA_ANALITICA = 'estructura_analitica';
    case MIR = 'mir';
}
