<?php

namespace App\Enums;

enum SystemPermission: string
{
    case GESTIONAR_CATALOGOS = 'gestionar_catalogos';
    case CREAR_PROGRAMA = 'crear_programa';
    case EDITAR_MIR = 'editar_mir';
    case CAPTURAR_AVANCE = 'capturar_avance';
    case REVISAR_AVANCE = 'revisar_avance';
    case APROBAR_AVANCE = 'aprobar_avance';
    case EXPORTAR_REPORTES = 'exportar_reportes';
    case ADMINISTRAR_USUARIOS = 'administrar_usuarios';
    case INVITAR_USUARIOS = 'invitar_usuarios';
}
