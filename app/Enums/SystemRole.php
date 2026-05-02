<?php

namespace App\Enums;

enum SystemRole: string
{
    case ADMIN = 'admin';
    case PLANEADOR = 'planeador';
    case OPERADOR = 'operador';
    case ANALISTA_FINANCIERO = 'analista_financiero';
    case ANALISTA_JURIDICO = 'analista_juridico';
    case RESPONSABLE_DATOS_ABIERTOS = 'responsable_datos_abiertos';
}
