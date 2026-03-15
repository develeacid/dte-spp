# Analisis de Roles y Permisos — DTE-SPP 2026

> Generado: 2026-03-15

## 5 Roles del Sistema

| Rol | Dominio principal | Concepto |
|-----|-------------------|----------|
| `admin` | Global | Superusuario, bypass en Gate |
| `planeador` | MIR / Tracking | Diseña programas, revisa/aprueba avances |
| `operador` | Tracking | Captura avances de indicadores |
| `analista_financiero` | Presupuesto | Gestiona partidas, captura avance financiero |
| `analista_juridico` | Juridico | Gestiona sustento legal, valida, sube ROPs |

## 18 Permisos — Matriz Rol x Permiso

| Permiso | admin | planeador | operador | a_financiero | a_juridico |
|---------|:-----:|:---------:|:--------:|:------------:|:----------:|
| **--- Core/MIR ---** | | | | | |
| `gestionar_catalogos` | x | x | | | |
| `crear_programa` | x | x | | | |
| `editar_mir` | x | x | | | |
| `capturar_avance` | x | | x | | |
| `revisar_avance` | x | x | | | |
| `aprobar_avance` | x | x | | | |
| `administrar_usuarios` | x | | | | |
| `invitar_usuarios` | x | | | | |
| **--- Reportes ---** | | | | | |
| `exportar_reportes` | x | x | x | | |
| `ver_sabana_captura` | x | x | x | | |
| `ver_concentrado_captura` | x | x | x | | |
| **--- Presupuesto ---** | | | | | |
| `gestionar_presupuesto` | x | | | x | |
| `capturar_avance_financiero` | x | | | x | |
| `ver_datos_financieros` | x | x | | x | x |
| `exportar_cuenta_publica` | x | x | | x | |
| **--- Juridico ---** | | | | | |
| `gestionar_sustento_legal` | x | | | | x |
| `validar_sustento_legal` | x | | | | x |
| `ver_sustento_legal` | x | x | | x | x |
| `gestionar_reglas_operacion` | x | | | | x |

## Permisos Puente (cruzados entre dominios)

1. **`ver_datos_financieros`** — planeador, a_financiero, a_juridico (lectura financiera transversal)
2. **`ver_sustento_legal`** — planeador, a_financiero, a_juridico (lectura marco juridico)
3. **`exportar_cuenta_publica`** — planeador y a_financiero (rendicion de cuentas)

## Cobertura Actual en Seeders de Prueba

| Rol | DesarrolloSeeder | QaTestingSeeder | Presupuesto/JuridicoTestSeeder |
|-----|-----------------|-----------------|-------------------------------|
| `admin` | 1 | 1 | 0 |
| `planeador` | 4 | 4 | 0 |
| `operador` | 4 | 3 | 0 |
| `analista_financiero` | **0** | **0** | **0** |
| `analista_juridico` | **0** | **0** | **0** |

## Brechas Identificadas

1. **No existen usuarios QA para `analista_financiero` ni `analista_juridico`**
   - `PresupuestoTestSeeder` y `JuridicoTestSeeder` usan `User::first()` como fallback
2. **`PresupuestoPermissionsSeeder` y `JuridicoPermissionsSeeder` no estan en `DatabaseSeeder`**
   - Sin ellos los roles nuevos no se crean al hacer `migrate:fresh --seed`
3. **No hay convencion de `team_role` (Jetstream) para los analistas**
   - Solo existen `planeador` y `operador` como roles de equipo en el pivot
4. **Tests cubren 7 casos para 5x18 combinaciones**
   - Falta seedear `JuridicoPermissionsSeeder` en setUp del test
   - Falta testear `analista_juridico`
   - Falta testear boundaries negativas inter-dominio
   - Falta testear exclusion mutua (a_financiero no puede gestionar_sustento_legal, etc.)
