# Analisis del Flujo de Avances de Indicadores

> Generado: 2026-03-15

## Maquina de Estados del Avance

```
                    ┌─────────────┐
                    │  EN_CAPTURA  │◄──────────────────────────────┐
                    │  (editable)  │                               │
                    └──────┬───────┘                               │
                           │                                       │
                   Operador: enviarRevision()                      │
                   (notifica planeadores)                          │
                           │                                       │
                           ▼                                       │
                    ┌──────────────┐                               │
                    │ EN_REVISION  │                               │
                    │(no editable) │                               │
                    └──┬───────┬───┘                               │
                       │       │                                   │
          Planeador:   │       │  Planeador:                      │
          aprobar()    │       │  observar(texto)                  │
                       │       │  (notifica operador)              │
                       ▼       ▼                                   │
              ┌──────────┐  ┌───────────┐     Operador:            │
              │ APROBADO │  │ OBSERVADO │──── corregir() ──────────┘
              │(congelado)│  └───────────┘
              └─────┬─────┘
                    │
           Operador: solicitar desbloqueo
           (si necesita corregir dato congelado)
                    │
                    ▼
              ┌───────────┐     Admin:
              │DESBLOQUEO │──── aprobar() → EN_CAPTURA (descongelado)
              │ pendiente │──── rechazar(motivo)
              └───────────┘

              ┌──────────┐
              │ VENCIDO  │  (automatico si fecha_cierre pasada y sin avance)
              │(terminal) │
              └──────────┘
```

## Transiciones Validas (AvanceEstadoService)

| Estado actual | Puede ir a |
|---------------|-----------|
| en_captura | en_revision, vencido |
| en_revision | observado, aprobado, vencido |
| observado | en_captura |
| aprobado | (terminal — solo desbloqueo excepcional) |
| vencido | (terminal) |

Fuente: `App\Services\Tracking\AvanceEstadoService::TRANSICIONES`

## Modelos del Flujo

### Avance (registro central)
| Campo | Tipo | Descripcion |
|-------|------|-------------|
| meta_periodo_id | FK | Periodo especifico que se reporta |
| indicador_id | FK | Indicador que se mide |
| resultado | decimal(12,4) | Valor calculado de la formula |
| semaforo_calculado | string | verde/amarillo/rojo (auto) |
| semaforo_ajustado | string | verde/amarillo/rojo (manual override) |
| justificacion_ia | text | Justificacion generada por IA |
| justificacion_final | text | Justificacion aprobada por usuario |
| estado | enum | en_captura/en_revision/observado/aprobado/vencido |
| historial_observaciones | jsonb | Historial append-only de transiciones |
| congelado_at | datetime | Se congela al aprobar |
| capturado_por | FK(User) | Operador que capturo |

### AvanceVariable (valores capturados por variable)
| Campo | Tipo | Descripcion |
|-------|------|-------------|
| avance_id | FK | Avance padre |
| indicador_variable_id | FK | Variable del indicador (A, B, C...) |
| valor | decimal(12,4) | Valor capturado para este periodo |
| valor_acumulado | decimal(12,4) | Valor acumulado (opcional) |

### AvanceEvidencia (archivos de soporte)
| Campo | Tipo | Descripcion |
|-------|------|-------------|
| avance_id | FK | Avance padre |
| nombre_archivo | string | Nombre original del archivo |
| ruta_archivo | string | Ruta en storage local |
| mime_type | string | pdf/xlsx/jpg/etc |
| tamano_bytes | int | Peso del archivo |
| hash_archivo | string | SHA-256 para integridad |
| nombre_documento | string | Nombre descriptivo |
| area_generadora | string | Quien genero el documento |
| fecha_documento | date | Fecha del documento fuente |
| subido_por | FK(User) | Quien subio el archivo |

Formatos aceptados: pdf, xlsx, xls, jpg, jpeg, png, doc, docx (max 10MB)

### Desbloqueo (solicitudes de excepcion)
| Campo | Tipo | Descripcion |
|-------|------|-------------|
| avance_id | FK | Avance congelado |
| motivo | string | Razon del desbloqueo |
| solicitado_por | FK(User) | Operador que solicita |
| resuelto_por | FK(User) | Admin que resuelve |
| estado | string | pendiente/aprobado/rechazado |
| resolucion | string | Motivo del rechazo |
| resuelto_at | datetime | Cuando se resolvio |

## Roles y Permisos por Accion

### OPERADOR (capturar_avance)

**Captura de avance** (CapturaAvance):
1. Ve la lista de variables del indicador con su formula
2. Ingresa valor numerico para cada variable (A, B, C...)
3. Presiona "Calcular":
   - FormulaEvaluatorService evalua la formula con las variables
   - SemaforoService calcula el semaforo (verde/amarillo/rojo)
   - Si semaforo es amarillo/rojo: genera justificacion IA automatica
4. Si semaforo es amarillo/rojo: DEBE escribir justificacion (min 10 chars)
5. Guarda: crea/actualiza AvanceVariable + Avance (estado permanece EN_CAPTURA)
6. Puede subir evidencias (EvidenciaAvance)
7. Envia a revision: transiciona a EN_REVISION, notifica planeadores del team

**Mis pendientes** (MisIndicadoresPendientes):
- Lista avances EN_CAPTURA filtrados por capturado_por = auth()->id()

**Solicitud de desbloqueo** (SolicitarDesbloqueo):
- Solo si avance.estaCongelado() = true
- Solo si no hay desbloqueo pendiente previo
- Crea Desbloqueo con estado=pendiente

### PLANEADOR (revisar_avance, aprobar_avance)

**Revision** (FlujosAvance):
- Ve avance EN_REVISION
- Opcion 1: aprobar() → estado APROBADO, congelado_at = now()
- Opcion 2: observar(texto) → estado OBSERVADO, notifica operador capturador
  - Texto observacion obligatorio (min 10 chars)
  - Se agrega al historial_observaciones

**Correccion post-observacion** (FlujosAvance):
- Desde OBSERVADO: corregir() → devuelve a EN_CAPTURA

**Vistas de supervision**:
- SabanaCaptura (can:ver_sabana_captura): matriz de todos los avances
- ConcentradoCaptura (can:ver_concentrado_captura): resumen estadistico
- IndicadoresVencidos (can:revisar_avance): metas sin avance cuya fecha_cierre ya paso
- DashboardIndicadores: vista jerarquica de MIR con avances por indicador

### ADMIN (administrar_usuarios)

**Gestion de desbloqueos** (GestionarDesbloqueos):
- Ve todas las solicitudes de desbloqueo pendientes
- aprobar(desbloqueoId): bypass de maquina de estados
  - APROBADO → EN_CAPTURA, congelado_at = null
  - Agrega entrada en historial: "desbloqueo_aprobado"
- rechazar(desbloqueoId, resolucion): marca como rechazado
  - Texto resolucion obligatorio (min 5 chars)

## Calculo del Semaforo (SemaforoService)

Dos modos de calculo:

### Modo 1: Con rangos explicitos (indicador tiene rango_verde_min/max)

| Sentido | Verde | Amarillo | Rojo |
|---------|-------|----------|------|
| Ascendente | resultado >= verde_min | resultado >= amarillo_min | resto |
| Descendente | resultado <= verde_max | resultado <= amarillo_max | resto |
| Regular | verde_min <= resultado <= verde_max | amarillo_min <= resultado <= amarillo_max | resto |

### Modo 2: Con meta del periodo (sin rangos explicitos)

| Sentido | Verde | Amarillo | Rojo |
|---------|-------|----------|------|
| Ascendente | resultado/meta >= 90% | resultado/meta >= 70% | < 70% |
| Descendente | resultado <= meta | resultado <= meta * 1.3 | > 130% |
| Regular | abs(resultado-meta) <= 10% meta | abs(resultado-meta) <= 30% meta | > 30% |

Fuente: `App\Services\Tracking\SemaforoService`

## Calculo de la Formula (FormulaEvaluatorService)

- Usa `symfony/expression-language`
- Normaliza notacion MIR: "x" → "*"
- Limpia caracteres no permitidos
- Evalua con variables {A: valor, B: valor, ...}
- Redondea a 4 decimales

## Frecuencia de Captura (por nivel MIR)

La frecuencia del indicador determina cada cuanto se debe capturar:

| Nivel MIR | Frecuencias posibles | Periodos por anio | Quien captura |
|-----------|---------------------|-------------------|---------------|
| ACTIVIDAD | mensual, trimestral | 12 o 4 | Operador UR |
| COMPONENTE | trimestral, semestral | 4 o 2 | Operador UR |
| PROPOSITO | semestral, anual | 2 o 1 | Operador UR |
| FIN | anual, bianual, sexenal | 1 o menos | Operador UR |

Cada MetaPeriodo tiene fecha_apertura y fecha_cierre que determina la ventana
de captura. Si fecha_cierre pasa sin avance registrado → VENCIDO.

## Notificaciones del Flujo

| Evento | Notificacion | Destinatario |
|--------|-------------|-------------|
| Operador envia a revision | AvanceEnRevisionNotification | Planeadores del team del indicador |
| Planeador observa | AvanceObservadoNotification | Operador capturador |
| Cambio en validacion tripartita | ValidacionTripartitaNotification | Roles involucrados |

Canal: database (notificaciones in-app)

## Brechas del QaTestingSeeder vs Flujo Real

| Aspecto del flujo | Seedeado | Estado |
|-------------------|:---:|--------|
| Avances con resultado y semaforo | Si | 4 programas, multiples periodos |
| AvanceVariable (valores A, B, C) | **No** | El seeder pone resultado directo sin variables |
| AvanceEvidencia | **No** | Sin archivos de evidencia |
| Historial de observaciones (JSONB) | Parcial | Solo observaciones de texto, sin transiciones completas |
| Desbloqueos | **No** | Sin solicitudes de desbloqueo |
| Justificacion IA | **No** | Sin justificacion_ia ni justificacion_final |
| Avances OBSERVADOS con ciclo completo | **No** | Se crean con estado final, no pasan por maquina de estados |
| Notificaciones in-app | **No** | No se generan notificaciones |
| Estados VENCIDO | Parcial | DDT-004 Q3-25 sin avance (vencido implicito, no marcado) |
| formula_texto en indicadores | **No** | Los indicadores no tienen formula |
| Semaforo con rangos explicitos | Parcial | Solo 1 indicador tiene rangos |

### Problema principal
El QaTestingSeeder crea avances con `Avance::updateOrCreate()` poniendo
resultado y semaforo directamente, sin pasar por:
1. Las variables de formula (AvanceVariable)
2. El calculo de formula (FormulaEvaluatorService)
3. La maquina de estados (AvanceEstadoService)
4. Las notificaciones

Esto significa que los datos de prueba no reflejan el flujo real de captura.
Para seeders mas realistas, cada avance deberia tener:
- Variables capturadas que, al evaluarse con la formula, den el resultado
- Historial de transiciones (en_captura → en_revision → aprobado/observado)
- Al menos algunos con justificacion cuando semaforo es amarillo/rojo
- Al menos 1 desbloqueo para probar ese flujo
