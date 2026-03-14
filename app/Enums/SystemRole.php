<?php

namespace App\Enums;

enum SystemRole: string
{
    case ADMIN = 'admin';
    case PLANEADOR = 'planeador';
    case OPERADOR = 'operador';
    case ANALISTA_FINANCIERO = 'analista_financiero';
}
