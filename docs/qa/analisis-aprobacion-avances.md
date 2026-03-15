# Analisis: Ruta de Aprobacion de Avances — Coordinadora vs Coadyuvante

> Generado: 2026-03-15

## 1. Maquina de Estados (recordatorio)

```
EN_CAPTURA ──► EN_REVISION ──► APROBADO (congelado)
                    │
                    └──► OBSERVADO ──► EN_CAPTURA (ciclo)

VENCIDO (terminal, automatico si fecha_cierre pasa sin avance)
APROBADO (terminal, solo desbloqueo excepcional por Admin)
```

## 2. Ruta Completa de un Avance — UR Coordinadora

Ejemplo: ISM-001, Componente 1 ("Subsidios equipamiento"), equipo SE-001.

```
  ┌──────────────────────────────────────────────────────────────┐
  │ PASO 0: Apertura automatica (cron mir:abrir-periodos)       │
  │                                                              │
  │ MetaPeriodo del indicador "% subsidios otorgados"           │
  │ fecha_apertura = 2025-04-01 → hoy >= fecha_apertura         │
  │                                                              │
  │ team_id = mir_nivel.team_id ?? programa.team_id              │
  │         = null ?? SE-001 = SE-001                            │
  │                                                              │
  │ Busca: User::permission('capturar_avance')                   │
  │          ->whereHas('teams', team_id = SE-001)               │
  │        → ele.operador@gmail.com                              │
  │                                                              │
  │ Crea: Avance(EN_CAPTURA, capturado_por = ele.operador)      │
  │ Notifica: PeriodoAbiertoNotification → ele.operador          │
  └───────────────────────┬──────────────────────────────────────┘
                          │
  ┌───────────────────────▼──────────────────────────────────────┐
  │ PASO 1: Captura (Operador SE-001)                           │
  │ Ruta: /seguimiento/captura/{avance}                          │
  │ Componente: CapturaAvance                                    │
  │ Permiso: capturar_avance                                     │
  │                                                              │
  │ 1. Ve formula: "(A / B) x 100"                              │
  │ 2. Ingresa: A = 170 subsidios entregados                    │
  │             B = 200 solicitudes aprobadas                    │
  │ 3. Calcula: resultado = 85.0                                │
  │ 4. Semaforo: 85/meta(21.25*4) → segun SemaforoService       │
  │ 5. Si amarillo/rojo: justificacion obligatoria               │
  │ 6. Guarda: AvanceVariable + Avance (estado = EN_CAPTURA)    │
  │ 7. Sube evidencias (opcional): PDF, Excel, fotos             │
  │                                                              │
  │ Verificaciones:                                              │
  │ - avance.estado.esEditable() → true (solo EN_CAPTURA)       │
  │ - avance.estaCongelado() → false                             │
  └───────────────────────┬──────────────────────────────────────┘
                          │
  ┌───────────────────────▼──────────────────────────────────────┐
  │ PASO 2: Enviar a revision (Operador SE-001)                 │
  │ Ruta: /seguimiento/flujo/{avance}                            │
  │ Componente: FlujosAvance::enviarRevision()                   │
  │                                                              │
  │ Transicion: EN_CAPTURA → EN_REVISION                         │
  │ Historial: [{accion: en_revision, usuario: ele.operador}]   │
  │                                                              │
  │ Notificacion: AvanceEnRevisionNotification                   │
  │ Destinatarios: User::permission('revisar_avance')            │
  │   ->whereHas('teams', team_id = mir_nivel.team_id)           │
  │   → Planeadores de SE-001                                    │
  │   → ele.planeador@gmail.com, ele.planeador2@gmail.com       │
  └───────────────────────┬──────────────────────────────────────┘
                          │
  ┌───────────────────────▼──────────────────────────────────────┐
  │ PASO 3a: Aprobacion (Planeador SE-001)                      │
  │ Ruta: /seguimiento/flujo/{avance}                            │
  │ Componente: FlujosAvance::aprobar()                          │
  │ Permiso: revisar_avance                                      │
  │                                                              │
  │ Transicion: EN_REVISION → APROBADO                           │
  │ congelado_at = now()                                         │
  │ Historial: [{accion: aprobado, usuario: ele.planeador}]     │
  │                                                              │
  │ El avance queda CONGELADO. No se puede editar.              │
  │ Para modificar: solicitud de desbloqueo → Admin.             │
  └──────────────────────────────────────────────────────────────┘

  ┌───────────────────────▼──────────────────────────────────────┐
  │ PASO 3b: Observacion (alternativa a 3a)                     │
  │ Componente: FlujosAvance::observar(texto)                    │
  │ Permiso: revisar_avance                                      │
  │                                                              │
  │ Transicion: EN_REVISION → OBSERVADO                          │
  │ Historial: [{accion: observado, observacion: "Falta..."}]   │
  │                                                              │
  │ Notificacion: AvanceObservadoNotification                    │
  │ Destinatario: avance.capturador (ele.operador)               │
  │                                                              │
  │ → Operador corrige (PASO 1 de nuevo)                        │
  │ → FlujosAvance::corregir() → OBSERVADO → EN_CAPTURA        │
  │ → Vuelve a enviar a revision (PASO 2)                       │
  └──────────────────────────────────────────────────────────────┘
```

## 3. Ruta Completa — UR Coadyuvante

Ejemplo: ISM-001, Componente 2 ("Certificaciones Ruta del Mezcal"), equipo SECTUR-004.

```
  ┌──────────────────────────────────────────────────────────────┐
  │ PASO 0: Apertura automatica                                  │
  │                                                              │
  │ team_id = mir_nivel.team_id ?? programa.team_id              │
  │         = SECTUR-004 ?? SE-001 = SECTUR-004  ← COADYUVANTE  │
  │                                                              │
  │ Busca: User::permission('capturar_avance')                   │
  │          ->whereHas('teams', team_id = SECTUR-004)           │
  │        → ele.operador.sectur@gmail.com                       │
  │                                                              │
  │ Crea: Avance(EN_CAPTURA, capturado_por = ele.operador.sectur)│
  │ Notifica: PeriodoAbiertoNotification → operadores SECTUR     │
  └───────────────────────┬──────────────────────────────────────┘
                          │
  ┌───────────────────────▼──────────────────────────────────────┐
  │ PASO 1: Captura (Operador SECTUR-004)                       │
  │ Mismo flujo que coordinadora                                 │
  │ ele.operador.sectur captura variables y calcula resultado    │
  └───────────────────────┬──────────────────────────────────────┘
                          │
  ┌───────────────────────▼──────────────────────────────────────┐
  │ PASO 2: Enviar a revision (Operador SECTUR-004)             │
  │ FlujosAvance::enviarRevision()                               │
  │                                                              │
  │ ★ AQUI ESTA LA DIFERENCIA CLAVE ★                          │
  │                                                              │
  │ notificarPlaneadores() usa:                                  │
  │   $teamId = avance.indicador.mirNivel.team_id               │
  │           = SECTUR-004                                       │
  │                                                              │
  │ Busca: User::permission('revisar_avance')                    │
  │          ->whereHas('teams', team_id = SECTUR-004)           │
  │        → ele.planeador.sectur@gmail.com                      │
  │                                                              │
  │ Notifica al planeador de la COADYUVANTE, no al coordinador. │
  └───────────────────────┬──────────────────────────────────────┘
                          │
  ┌───────────────────────▼──────────────────────────────────────┐
  │ PASO 3: Aprobacion (Planeador SECTUR-004)                   │
  │ FlujosAvance::aprobar()                                      │
  │ Permiso: revisar_avance (el planeador SECTUR lo tiene)       │
  │                                                              │
  │ El planeador SECTUR aprueba los avances de SU componente.   │
  │ El planeador SE-001 NO interviene en esta aprobacion.        │
  │                                                              │
  │ Transicion: EN_REVISION → APROBADO, congelado.              │
  └──────────────────────────────────────────────────────────────┘
```

## 4. Diferencias Clave: Coordinadora vs Coadyuvante

| Aspecto | UR Coordinadora | UR Coadyuvante |
|---------|----------------|----------------|
| **Apertura** | team_id = programa.team_id | team_id = mir_nivel.team_id |
| **Operador asignado** | Del team coordinador | Del team coadyuvante |
| **Notificacion apertura** | Operadores del team coordinador | Operadores del team coadyuvante |
| **Planeador notificado** | Planeadores del team coordinador | Planeadores del team coadyuvante |
| **Quien aprueba** | Planeador coordinadora | Planeador coadyuvante |
| **Quien observa** | Planeador coordinadora | Planeador coadyuvante |
| **Notificacion observacion** | Al operador coordinadora | Al operador coadyuvante |
| **Desbloqueo** | Admin (global) | Admin (global) |
| **Visibilidad Panel** | Ve todo el programa | Solo ve sus niveles |

### La autonomia de la coadyuvante

La coadyuvante tiene **autonomia completa** en el ciclo de aprobacion de sus
niveles. El planeador coordinador NO aprueba los avances de la coadyuvante.

Esto es correcto institucionalmente: cada UR es responsable de reportar sus
propios indicadores, incluso cuando participan en un programa transversal.

## 5. Hallazgo: Bug en notificarPlaneadores()

```php
private function notificarPlaneadores($notification): void
{
    $teamId = $this->avance->indicador->mirNivel->team_id ?? null;

    if (! $teamId) {
        return;  // ← BUG: Si team_id es null, NO notifica a nadie
    }
    // ...
}
```

**Problema**: Si el nivel MIR tiene `team_id = null` (UR coordinadora), el
metodo retorna sin notificar. Los planeadores de la coordinadora NUNCA reciben
notificacion de avances en revision.

**Deberia ser**:
```php
$teamId = $this->avance->indicador->mirNivel->team_id
    ?? $this->avance->indicador->mirNivel->programa?->team_id;
```

Mismo patron que `AbrirPeriodosCaptura` lineas 31-33.

## 6. Tabla de Notificaciones por Paso

| Paso | Evento | Notificacion | Destinatario Coordinadora | Destinatario Coadyuvante |
|------|--------|-------------|--------------------------|-------------------------|
| 0 | Periodo abierto | PeriodoAbiertoNotification | Operadores del team coord | Operadores del team coadyuvante |
| 2 | Enviado a revision | AvanceEnRevisionNotification | ★ NADIE (bug) | Planeadores del team coadyuvante |
| 3b | Observado | AvanceObservadoNotification | Operador capturador (correcto) | Operador capturador (correcto) |
| - | Aprobado | (ninguna) | - | - |

## 7. Historial de Observaciones (JSONB append-only)

Cada transicion agrega una entrada al arreglo:

```json
[
  {
    "fecha": "2025-04-15T10:30:00Z",
    "usuario_id": 5,
    "usuario_nombre": "QA Operador SECTUR",
    "accion": "en_revision",
    "estado_anterior": "en_captura",
    "estado_nuevo": "en_revision",
    "observacion": null
  },
  {
    "fecha": "2025-04-16T09:00:00Z",
    "usuario_id": 8,
    "usuario_nombre": "QA Planeador SECTUR",
    "accion": "observado",
    "estado_anterior": "en_revision",
    "estado_nuevo": "observado",
    "observacion": "Falta evidencia del acta de inspeccion del palenque"
  },
  {
    "fecha": "2025-04-17T14:20:00Z",
    "usuario_id": 5,
    "usuario_nombre": "QA Operador SECTUR",
    "accion": "en_captura",
    "estado_anterior": "observado",
    "estado_nuevo": "en_captura",
    "observacion": null
  },
  {
    "fecha": "2025-04-18T11:00:00Z",
    "usuario_id": 5,
    "usuario_nombre": "QA Operador SECTUR",
    "accion": "en_revision",
    "estado_anterior": "en_captura",
    "estado_nuevo": "en_revision",
    "observacion": null
  },
  {
    "fecha": "2025-04-19T08:45:00Z",
    "usuario_id": 8,
    "usuario_nombre": "QA Planeador SECTUR",
    "accion": "aprobado",
    "estado_anterior": "en_revision",
    "estado_nuevo": "aprobado",
    "observacion": null
  }
]
```

## 8. Escenarios para Seeders

Para cubrir el flujo completo de aprobacion, los seeders deberian incluir
avances en TODOS los estados posibles, con historial realista:

### UR Coordinadora (SE-001 en ISM-001)

| Indicador | Estado final | Historial | capturado_por |
|-----------|-------------|-----------|---------------|
| % subsidios otorgados Q1 | APROBADO | captura→revision→aprobado | ele.operador |
| % subsidios otorgados Q2 | APROBADO | captura→revision→aprobado | ele.operador |
| % subsidios otorgados Q3 | OBSERVADO | captura→revision→observado | ele.operador |
| % subsidios otorgados Q4 | EN_REVISION | captura→revision | ele.operador |
| % subsidios otorgados Q1-2026 | EN_CAPTURA | (recien abierto) | ele.operador |

### UR Coadyuvante (SECTUR-004 en ISM-001)

| Indicador | Estado final | Historial | capturado_por |
|-----------|-------------|-----------|---------------|
| Num palenques certificados S1 | APROBADO | captura→revision→aprobado | ele.operador.sectur |
| Num palenques certificados S2 | APROBADO | captura→revision→observado→captura→revision→aprobado | ele.operador.sectur |
| Num inspecciones Q1 | APROBADO | captura→revision→aprobado | ele.operador.sectur |
| Num inspecciones Q2 | OBSERVADO | captura→revision→observado (ciclo correccion) | ele.operador.sectur |
| Satisfaccion productores Q1 | EN_CAPTURA | (abierto, sin llenar) | ele.operador.sectur |

### Historiales del planeador

Los historiales deben reflejar:
- **Coordinadora**: planeador SE-001 aprueba/observa indicadores de C1
- **Coadyuvante**: planeador SECTUR aprueba/observa indicadores de C2
- **Nunca**: planeador SE-001 aprobando indicadores de C2 (seria inconsistente)

### Al menos 1 desbloqueo

Un avance APROBADO que necesita correccion:
- Operador SECTUR solicita desbloqueo (motivo: "Error en variable A")
- Admin aprueba → APROBADO → EN_CAPTURA (descongelado)
- Historial incluye entrada "desbloqueo_aprobado"
