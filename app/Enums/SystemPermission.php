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
    case VER_SABANA_CAPTURA = 'ver_sabana_captura';
    case VER_CONCENTRADO_CAPTURA = 'ver_concentrado_captura';

    // Presupuesto
    case GESTIONAR_PRESUPUESTO = 'gestionar_presupuesto';
    case CAPTURAR_AVANCE_FINANCIERO = 'capturar_avance_financiero';
    case VER_DATOS_FINANCIEROS = 'ver_datos_financieros';
    case EXPORTAR_CUENTA_PUBLICA = 'exportar_cuenta_publica';

    // Jurídico
    case GESTIONAR_SUSTENTO_LEGAL = 'gestionar_sustento_legal';
    case VALIDAR_SUSTENTO_LEGAL = 'validar_sustento_legal';
    case VER_SUSTENTO_LEGAL = 'ver_sustento_legal';
    case GESTIONAR_REGLAS_OPERACION = 'gestionar_reglas_operacion';
}
