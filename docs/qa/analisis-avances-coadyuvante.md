# Analisis: Como las UR Coadyuvantes Agregan Avances a Indicadores

> Generado: 2026-03-15

## 1. Mecanismo de Asignacion Automatica de Avances

El comando `mir:abrir-periodos` (AbrirPeriodosCaptura) es el motor que conecta
indicadores con operadores, incluyendo los de URs coadyuvantes.

### Flujo del comando

```
mir:abrir-periodos (cron diario)
    │
    ├── Busca MetaPeriodos donde:
    │   - fecha_apertura <= hoy
    │   - activo = true
    │   - NO tiene avance creado
    │
    └── Para cada MetaPeriodo:
        │
        ├── 1. Determinar team_id del indicador:
        │   team_id = mir_nivel.team_id       ← UR coadyuvante (si existe)
        │             ?? programa.team_id      ← UR coordinadora (fallback)
        │
        ├── 2. Buscar operador en ese team:
        │   User::permission('capturar_avance')
        │     ->whereHas('teams', fn($q) => $q->where('teams.id', $teamId))
        │     ->first()
        │
        ├── 3. Crear Avance:
        │   estado = EN_CAPTURA
        │   capturado_por = operador encontrado (o null)
        │
        └── 4. Notificar operadores del team:
            PeriodoAbiertoNotification → todos con permiso capturar_avance en el team
```

### La logica clave (lineas 31-33 del comando):

```php
$teamId = $meta->indicador->mirNivel->team_id        // UR coadyuvante
    ?? $meta->indicador->mirNivel->programa?->team_id  // UR coordinadora
    ?? null;
```

**Esto significa**: Si el nivel MIR tiene `team_id` (asignado como coadyuvante),
el avance se asigna al operador de ESA UR. Si no, se asigna al operador de la
UR coordinadora.

## 2. Quien Captura Que — Por Tipo de Indicador y Nivel

### Programa NO transversal (1 sola UR)

Todos los `mir_niveles.team_id = null`, por lo tanto todos los avances se asignan
a operadores de la UR coordinadora (`programa.team_id`).

```
FIN ──────────── team_id: null → Operador de UR coordinadora
PROPOSITO ────── team_id: null → Operador de UR coordinadora
COMPONENTE 1 ─── team_id: null → Operador de UR coordinadora
  ACT 1.1 ────── team_id: null → Operador de UR coordinadora
  ACT 1.2 ────── team_id: null → Operador de UR coordinadora
```

### Programa TRANSVERSAL (coordinadora + coadyuvante)

Ejemplo: ISM-001 (SE-001 coordina, SECTUR-004 coadyuva)

```
FIN ──────────── team_id: null → Operador SE-001 captura
  Indicador: "Tasa crecimiento PIB agroindustrial" (estrategico, eficacia, bianual)

PROPOSITO ────── team_id: null → Operador SE-001 captura
  Indicador: "% variacion ventas productores" (estrategico, eficacia, anual)

COMP 1 ────────── team_id: null → Operador SE-001 captura
  Indicador: "% subsidios otorgados" (gestion, eficacia, trimestral)
  ACT 1.1 ──────── team_id: null → Operador SE-001 captura
    Indicador: "% solicitudes evaluadas en plazo" (gestion, eficiencia, trimestral)
  ACT 1.2 ──────── team_id: null → Operador SE-001 captura
    Indicador: "Num verificaciones instalacion" (gestion, eficacia, mensual)

COMP 2 ────────── team_id: SECTUR-004 → Operador SECTUR captura
  Indicador: "Num palenques certificados" (gestion, eficacia, semestral)
  ACT 2.1 ──────── team_id: SECTUR-004 → Operador SECTUR captura
    Indicador: "Num inspecciones palenques" (gestion, eficacia, trimestral)
  ACT 2.2 ──────── team_id: SECTUR-004 → Operador SECTUR captura
    Indicador: "Indice satisfaccion productores" (gestion, calidad, trimestral)
```

### Regla general

| Nivel MIR | team_id | Quien captura avance | Quien revisa |
|-----------|---------|---------------------|-------------|
| FIN | null (siempre) | Operador coordinadora | Planeador coordinadora |
| PROPOSITO | null (siempre) | Operador coordinadora | Planeador coordinadora |
| COMPONENTE | null o coadyuvante | Operador del team_id | Planeador del team_id |
| ACTIVIDAD | null o coadyuvante | Operador del team_id | Planeador del team_id |

**Fin y Proposito NUNCA pueden ser de coadyuvante** — el MirEditor lo impide:

```php
// MirEditor::asignarUrCoadyuvante()
if (!in_array($nivel->tipo_nivel, [TipoNivelMir::COMPONENTE, TipoNivelMir::ACTIVIDAD])) {
    return; // No hace nada para FIN/PROPOSITO
}
```

## 3. Indicadores por Dimension y Quien los Alimenta

### EFICACIA — Conteo de resultados

| Nivel | Fuente de datos | Quien captura |
|-------|----------------|---------------|
| FIN/PROPOSITO (estrategico) | INEGI, CONEVAL, evaluaciones externas | Operador coordinadora |
| COMPONENTE (gestion/estrategico) | Padron operativo, bienes entregados | Operador del team_id del componente |
| ACTIVIDAD (gestion) | BD transaccional, conteo directo | Operador del team_id de la actividad |

En transversal: el operador SECTUR reporta los palenques certificados (su comp),
el operador SE reporta los subsidios entregados (su comp).

### EFICIENCIA — Costo por resultado

| Nivel | Fuente de datos | Quien captura |
|-------|----------------|---------------|
| PROPOSITO | Evaluacion costo-beneficio global | Operador coordinadora |
| COMPONENTE | Gasto componente / bienes entregados | Operador del team_id |
| ACTIVIDAD | Gasto actividad / acciones realizadas | Operador del team_id |

**Caso coadyuvante**: Si SECTUR tiene el componente de certificaciones, su
operador necesita:
- Variable A: gasto ejercido en el componente (dato financiero de SECTUR)
- Variable B: numero de certificaciones emitidas (dato operativo de SECTUR)

El operador SECTUR obtiene ambos datos de su propia UR.

### ECONOMIA — Manejo del dinero

| Nivel | Fuente de datos | Quien captura |
|-------|----------------|---------------|
| ACTIVIDAD (unico nivel permitido) | Sistema financiero, presupuesto | Operador del team_id |

**Caso coadyuvante**: Si una actividad de economia esta asignada a SECTUR,
el operador SECTUR reporta la ejecucion financiera de ESA actividad.
Los datos vienen del modulo presupuestal que gestiona el analista financiero
de SECTUR (para sus propias partidas).

### CALIDAD — Satisfaccion y estndares

| Nivel | Fuente de datos | Quien captura |
|-------|----------------|---------------|
| COMPONENTE | Encuestas, quejas, tiempos | Operador del team_id |
| ACTIVIDAD | Tiempos de proceso, errores | Operador del team_id |

**Caso coadyuvante**: SECTUR reporta la satisfaccion de productores en SU
componente (certificaciones turisticas), SE reporta tiempos de evaluacion
de solicitudes en SU componente (subsidios).

## 4. Visibilidad por Rol (PanelSeguimiento)

```php
// PanelSeguimiento::render()
MirNivel::where(function ($q) use ($teamId) {
    $q->whereHas('programa', fn ($p) => $p->where('team_id', $teamId))
      ->orWhere('team_id', $teamId);  // ← Niveles coadyuvantes
})
```

| Rol/UR | Ve indicadores de | Ejemplo ISM-001 |
|--------|-------------------|-----------------|
| Planeador SE-001 | Todo el programa (es coordinadora) | FIN, PROP, C1, C2, todas Act |
| Planeador SECTUR | Solo niveles donde team_id=SECTUR | C2, A2.1, A2.2 |
| Operador SE-001 | Solo sus avances (capturado_por) | Avances de C1, A1.1, A1.2 |
| Operador SECTUR | Solo sus avances (capturado_por) | Avances de C2, A2.1, A2.2 |

### MisIndicadoresPendientes (vista del operador)

```php
Avance::where('capturado_por', auth()->id())
      ->where('estado', EstadoAvance::EN_CAPTURA)
```

El operador SOLO ve los avances que le fueron asignados automaticamente
por `mir:abrir-periodos`. No puede ver ni capturar avances de otro operador.

### IndicadoresVencidos (vista del planeador)

```php
Avance::where('estado', EstadoAvance::VENCIDO)
      ->whereHas('indicador.mirNivel', fn ($q) => $q->where('team_id', $teamId))
```

El planeador ve vencidos de su team — incluyendo los niveles coadyuvantes
asignados a su UR.

## 5. Flujo Completo: Avance de Indicador Coadyuvante

```
                          MirEditor (Planeador SE-001)
                                    │
                    asignarUrCoadyuvante(C2, SECTUR)
                                    │
                            C2.team_id = SECTUR
                            programa_team: SECTUR→coadyuvante
                                    │
                          ┌─────────┴─────────┐
                          │  mir:abrir-periodos │  (cron diario)
                          └─────────┬─────────┘
                                    │
                    MetaPeriodo de C2 → fecha_apertura llegó
                    team_id = C2.team_id = SECTUR
                                    │
                    Busca User con capturar_avance en SECTUR
                    → ele.operador.sectur
                                    │
                    Crea Avance(EN_CAPTURA, capturado_por=operador.sectur)
                    Notifica: PeriodoAbiertoNotification → operadores SECTUR
                                    │
                          ┌─────────┴─────────┐
                          │  Operador SECTUR   │
                          └─────────┬─────────┘
                                    │
                    /seguimiento/pendientes → ve SU avance
                    /seguimiento/captura/{avance}
                    Ingresa variables A, B → formula → resultado → semaforo
                    Si amarillo/rojo: justificacion obligatoria
                    Guarda → estado sigue EN_CAPTURA
                    Sube evidencias
                    enviarRevision() → EN_REVISION
                    Notifica planeadores SECTUR
                                    │
                          ┌─────────┴─────────┐
                          │ Planeador SECTUR   │  (revisar_avance)
                          └─────────┬─────────┘
                                    │
                    /seguimiento/panel → ve indicadores de SECTUR
                    /seguimiento/flujo/{avance}
                    aprobar() → APROBADO + congelado_at
                    u observar(texto) → OBSERVADO → notifica operador
```

## 6. Brechas en QaTestingSeeder

| Aspecto | Estado actual | Lo que deberia ser |
|---------|:---:|---|
| Avances ISM-001 C2 (SECTUR) | capturado_por = ele.operador (SE) | capturado_por = ele.operador.sectur |
| Avances ISM-001 C1 (SE) | capturado_por = ele.operador (SE) | Correcto |
| Notificaciones a coadyuvante | No generadas | Deberian existir para operadores SECTUR |
| Revisiones de coadyuvante | No diferenciadas | Planeador SECTUR deberia revisar C2 |
| Avances por comando cron | No simulados | Seeder deberia crear avances con la misma logica de team_id |
| Indicadores de Economia en coadyuvante | No existen | Al menos 1 actividad SECTUR con indicador economia |
| Indicadores de Eficiencia en coadyuvante | No existen | Al menos 1 componente SECTUR con eficiencia |

### Lo que falta para un dataset transversal realista

1. **ISM-001 Componente 2**: Los avances de "Num palenques certificados",
   "Num inspecciones", "Indice satisfaccion" deberian tener
   `capturado_por = ele.operador.sectur`

2. **ISM-001**: Deberia tener al menos 1 indicador de Eficiencia en C2
   (ej: "Costo por certificacion") donde el operador SECTUR cruce datos
   de gasto (de su presupuesto) con produccion (certificaciones emitidas)

3. **Historial de revisiones**: Los avances de C2 deberian tener en
   `historial_observaciones` entradas del planeador SECTUR, no del SE

4. **Al menos 1 avance OBSERVADO en coadyuvante**: Para probar el flujo
   completo de observacion-correccion entre planeador SECTUR y operador SECTUR
