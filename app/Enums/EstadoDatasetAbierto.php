<?php

namespace App\Enums;

enum EstadoDatasetAbierto: string
{
    case BORRADOR = 'borrador';
    case REVISION = 'revision';
    case APROBADO = 'aprobado';
    case PUBLICADO = 'publicado';
    case RETIRADO = 'retirado';
}
