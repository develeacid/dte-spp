# Usuarios QA — Seeders Fase 0

> Generado: 2026-03-15
> Password para todos: `password`

## 17 Usuarios (5 roles x 4 URs + 1 admin)

| Email | Rol | UR | Permisos clave |
|-------|-----|-----|---------------|
| admin@sistema.test | admin | - (todas) | Todos (19 permisos) |
| planeador.se@sistema.test | planeador | SE-001 Educacion | crear_programa, editar_mir, revisar_avance, aprobar_avance, ver_datos_financieros, ver_sustento_legal |
| operador.se@sistema.test | operador | SE-001 Educacion | capturar_avance, exportar_reportes, ver_sabana_captura |
| financiero.se@sistema.test | analista_financiero | SE-001 Educacion | gestionar_presupuesto, capturar_avance_financiero, ver_datos_financieros, exportar_cuenta_publica, ver_sustento_legal |
| juridico.se@sistema.test | analista_juridico | SE-001 Educacion | gestionar_sustento_legal, validar_sustento_legal, ver_sustento_legal, gestionar_reglas_operacion, ver_datos_financieros |
| planeador.ss@sistema.test | planeador | SS-002 Salud | (mismos que planeador.se) |
| operador.ss@sistema.test | operador | SS-002 Salud | (mismos que operador.se) |
| financiero.ss@sistema.test | analista_financiero | SS-002 Salud | (mismos que financiero.se) |
| juridico.ss@sistema.test | analista_juridico | SS-002 Salud | (mismos que juridico.se) |
| planeador.seg@sistema.test | planeador | SEG-003 Seguridad | (mismos que planeador.se) |
| operador.seg@sistema.test | operador | SEG-003 Seguridad | (mismos que operador.se) |
| financiero.seg@sistema.test | analista_financiero | SEG-003 Seguridad | (mismos que financiero.se) |
| juridico.seg@sistema.test | analista_juridico | SEG-003 Seguridad | (mismos que juridico.se) |
| planeador.sectur@sistema.test | planeador | SECTUR-004 Turismo | (mismos que planeador.se) |
| operador.sectur@sistema.test | operador | SECTUR-004 Turismo | (mismos que operador.se) |
| financiero.sectur@sistema.test | analista_financiero | SECTUR-004 Turismo | (mismos que financiero.se) |
| juridico.sectur@sistema.test | analista_juridico | SECTUR-004 Turismo | (mismos que juridico.se) |

## Unidades Responsables

| Clave | Nombre | Tipo | Programas |
|-------|--------|------|-----------|
| SE-001 | Secretaria de Educacion | SUSTANTIVA | ISM-001, EDU-002, EDU-003, EDU-004 |
| SS-002 | Secretaria de Salud | APOYO | PEC-001, SAL-002, SAL-003, SAL-004 |
| SEG-003 | Secretaria de Seguridad | SUSTANTIVA | FSP-001, SEG-002, SEG-003P, SEG-004 |
| SECTUR-004 | Secretaria de Turismo | SUSTANTIVA | DDT-001, TUR-002, TUR-003, TUR-004 |

## Programa Transversal

ISM-001 (Impulso al Sector Mezcalero):
- Coordinadora: SE-001 (planeador.se, operador.se)
- Coadyuvante: SECTUR-004 en C2, A2.1, A2.2 (planeador.sectur, operador.sectur)
