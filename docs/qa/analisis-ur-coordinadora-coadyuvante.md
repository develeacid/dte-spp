# Analisis: UR Coordinadora vs Coadyuvante + Frecuencias Multi-Ejercicio

> Generado: 2026-03-15

## 1. Modelo de Datos del Programa Transversal

### Tabla `programa_team` (pivot)

```sql
programa_presupuestario_id  FK
team_id                     FK
rol                         string(20) default 'coadyuvante'
-- valores: 'coordinadora' | 'coadyuvante'
-- unique: [programa_presupuestario_id, team_id]
```

### Tabla `mir_niveles` — campo `team_id`

```sql
team_id   FK nullable → teams
-- Si null: el nivel pertenece a la UR coordinadora (team_id del programa)
-- Si valor: el nivel esta asignado a esa UR coadyuvante
-- Solo aplica a COMPONENTE y ACTIVIDAD (Fin y Proposito siempre son de la coordinadora)
```

## 2. Diferencias Coordinadora vs Coadyuvante

### UR Coordinadora

| Aspecto | Detalle |
|---------|---------|
| **Relacion con programa** | `ProgramaPresupuestario.team_id` = su team |
| **Rol en pivot** | `programa_team.rol = 'coordinadora'` |
| **Crea el programa** | Si (permiso `crear_programa`) |
| **Navega wizard E1-E7** | Si, completo |
| **Edita Fin y Proposito** | Si (exclusivo) |
| **Edita todos los Componentes/Actividades** | Si |
| **Asigna UR coadyuvante a niveles** | Si (MirEditor::asignarUrCoadyuvante) |
| **Ve todos los indicadores del programa** | Si |
| **Revisa avances de todo el programa** | Si |
| **Presupuesto del programa** | Si (su analista financiero lo gestiona) |
| **Sustento legal del programa** | Si (su analista juridico lo gestiona) |

### UR Coadyuvante

| Aspecto | Detalle |
|---------|---------|
| **Relacion con programa** | Solo a traves de `programa_team.rol = 'coadyuvante'` |
| **Crea el programa** | No |
| **Navega wizard** | No (solo accede via MirEditor si tiene niveles asignados) |
| **Edita Fin y Proposito** | No (son de la coordinadora) |
| **Edita Componentes/Actividades** | Solo los que tienen `mir_niveles.team_id = su team` |
| **Asigna UR coadyuvante** | No (solo la coordinadora asigna) |
| **Ve indicadores** | Solo los de sus niveles asignados |
| **Captura avances** | Solo de indicadores en sus niveles |
| **Presupuesto** | Gestiona sus propias partidas (su team) |
| **Sustento legal** | Gestiona su propio sustento (su team) |

### Middleware AislamientoMultiUR

```
1. Si es Admin → pasa siempre
2. Si la ruta tiene {programa}:
   - Busca en programa_team donde team_id = currentTeam del usuario
   - Si no existe relacion → 403
   - Si existe → inyecta el rol en request: 'ur_rol_en_programa'
3. Si la ruta no tiene {programa} → ignora (no aplica)
```

**Nota importante**: El middleware inyecta `ur_rol_en_programa` en el request
pero actualmente NO se usa en ningun controlador/livewire para diferenciar
comportamiento. La diferenciacion se hace a nivel de `mir_niveles.team_id`.

### PanelSeguimiento — Visibilidad coadyuvante

El `PanelSeguimiento` usa esta query para incluir niveles de la coadyuvante:

```php
MirNivel::where(function ($q) use ($teamId) {
    // Programas propios (coordinadora)
    $q->whereHas('programa', fn ($p) => $p->where('team_id', $teamId))
    // O niveles asignados como coadyuvante
    ->orWhere('team_id', $teamId);
})
```

Esto significa que un planeador de la UR coadyuvante ve en su panel de
seguimiento tanto los programas donde es coordinadora como los niveles
de programas ajenos donde es coadyuvante.

## 3. Ciclo de Asignacion Coadyuvante

```
Planeador Coordinadora (MirEditor)
    │
    ├── Crea Componente C2 ("Certificaciones Ruta del Mezcal")
    │
    ├── asignarUrCoadyuvante(C2.id, SECTUR.id)
    │   ├── C2.team_id = SECTUR.id
    │   └── programa_team: SECTUR → coadyuvante (syncWithoutDetaching)
    │
    ├── Crea Actividad A2.1 bajo C2
    │   └── A2.1.team_id = SECTUR.id (hereda del componente? NO, se asigna manual)
    │
    └── Si quita SECTUR de C2:
        ├── C2.team_id = null
        └── Verifica si SECTUR tiene otros niveles:
            ├── Si → mantiene en programa_team
            └── No → elimina de programa_team
```

## 4. Ejemplo con los 4 Programas del Seeder

### ISM-001 (Impulso al Sector Mezcalero) — TRANSVERSAL

```
Coordinadora: SE-001 (Educacion)
Coadyuvante: SECTUR-004 (Turismo)

FIN ────────────────── team_id: null (SE-001 implicito)
PROPOSITO ──────────── team_id: null (SE-001 implicito)
COMPONENTE 1 ───────── team_id: null (SE-001) → "Subsidios equipamiento"
  ACTIVIDAD 1.1 ────── team_id: null (SE-001) → "Recepcion solicitudes"
  ACTIVIDAD 1.2 ────── team_id: null (SE-001) → "Entrega equipos"
COMPONENTE 2 ───────── team_id: SECTUR-004 → "Certificaciones Ruta del Mezcal"
  ACTIVIDAD 2.1 ────── team_id: SECTUR-004 → "Inspeccion palenques"
  ACTIVIDAD 2.2 ────── team_id: SECTUR-004 → "Capacitacion productores"
```

**Impacto en roles**:
- Planeador SE-001: edita todo, revisa avances de todo
- Planeador SECTUR-004: edita C2, A2.1, A2.2 y sus indicadores
- Operador SE-001: captura avances de C1, A1.1, A1.2
- Operador SECTUR-004: captura avances de C2, A2.1, A2.2
- Analista Financiero SE-001: gestiona partidas del programa completo
- Analista Juridico SE-001: gestiona sustento legal del programa completo

### PEC-002, FSP-003, DDT-004 — NO TRANSVERSALES

```
Solo Coordinadora (SS-002, SEG-003, SECTUR-004 respectivamente)
Todos los mir_niveles.team_id = null
No hay entrada en programa_team con rol coadyuvante
```

## 5. Frecuencias Bianual y Sexenal (entre ejercicios)

### CalendarizacionService

Para bianual y sexenal, el servicio genera **1 periodo por ejercicio**:

```php
FrecuenciaMedicion::BIANUAL => 1,  // 1 periodo
FrecuenciaMedicion::SEXENAL => 1,  // 1 periodo
```

El QaTestingSeeder ya maneja esto:
- Bianual: solo genera MetaPeriodo si `$year % 2 === 0`
- Sexenal: genera 1 MetaPeriodo por ejercicio

### CalendarioService (ventanas de captura)

Para anual, bianual y sexenal, la ventana de captura se abre en enero
del siguiente ejercicio (mes 13 → enero del anio+1):

```php
FrecuenciaMedicion::ANUAL,
FrecuenciaMedicion::BIANUAL,
FrecuenciaMedicion::SEXENAL => 13,  // mes 13 = enero siguiente
```

La ventana dura 14 dias (fecha_apertura + 14 dias).

### Implicacion para seeders

Los indicadores bianual/sexenal (tipicamente de nivel FIN):
- Se miden 1 vez por ejercicio fiscal
- La captura se hace en enero del ejercicio siguiente
- Meta periodo = meta completa del indicador (no se divide)
- El QaTestingSeeder ya crea MetaPeriodos para 2025 y 2026

## 6. Brechas en Seeders Actuales

### Transversalidad

| Aspecto | Estado en QaTestingSeeder |
|---------|--------------------------|
| ISM-001 como transversal (SE+SECTUR) | SI, pero parcial |
| programa_team con coordinadora/coadyuvante | SI |
| mir_niveles.team_id para C2 y actividades SECTUR | SI (SECTUR-004) |
| Usuarios coadyuvantes con roles correctos | PARCIAL — hay planeador y operador SECTUR |
| Avances capturados por operador de la coadyuvante | SI (operadorSectur captura DDT-004, NO los de ISM-001 C2) |

**Brecha clave**: Los avances de ISM-001 Componente 2 (SECTUR) deberian ser
capturados por `ele.operador.sectur` pero actualmente son capturados por
`ele.operador` (SE-001). El seeder no respeta que cada UR captura los avances
de sus propios niveles.

### Roles nuevos en programas transversales

No hay analista financiero ni juridico seedeado para ninguna UR.
En un programa transversal:
- El analista financiero de la coordinadora gestiona el presupuesto del programa
- El analista juridico de la coordinadora gestiona el sustento legal
- Los analistas de la coadyuvante NO participan en ese programa (salvo lectura)

Esto deberia reflejarse en los seeders: los datos financieros y juridicos de
ISM-001 deberian ser creados por usuarios de SE-001, no por User::first().
