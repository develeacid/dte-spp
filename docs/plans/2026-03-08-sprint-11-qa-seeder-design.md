# Sprint 11: QA Testing Seeder — Documento de Diseño

## Objetivo

Seeder idempotente (`QaTestingSeeder`) que genera datos realistas para pruebas manuales de QA pre-beta, cubriendo todos los módulos: MIR, calendarización, captura de avances, semaforización, validación IA, y dashboard.

## Usuarios (6)

| Email | Rol | UR | Password |
|-------|-----|-----|----------|
| `ele.admin@gmail.com` | admin | Todas | `LseRdlP0P` |
| `ele.planeador@gmail.com` | planeador | SE-001 | `LseRdlP0P` |
| `ele.operador@gmail.com` | operador | SE-001 | `LseRdlP0P` |
| `ele.planeador2@gmail.com` | planeador | SE-001 | `LseRdlP0P` |
| `ele.revisor@gmail.com` | planeador | SS-002 | `LseRdlP0P` |
| `ele.operador2@gmail.com` | operador | SS-002 | `LseRdlP0P` |

Idempotencia: `firstOrCreate` por email. Cada usuario se une al team de su UR con `$team->users()->syncWithoutDetaching()`.

### Escenarios cubiertos por usuario

- **admin**: Ve todo el sistema, aprueba avances de todas las URs
- **planeador + planeador2**: Dos planeadores en la misma UR (SE-001) — prueba MIR con 2 involucrados
- **operador**: Captura avances en SE-001 — tiene pendientes, en_revision, observados
- **revisor**: Planeador en SS-002 — prueba aislamiento entre URs
- **operador2**: Captura en SS-002 — prueba aislamiento del operador

## Programas y MIRs (3)

### Programa 1 — "Fomento Económico Regional" (SE-001, MIR bien estructurada)

- Clave: `FER-001`
- Niveles: FIN → PROPOSITO → 2 COMPONENTES → 2 ACTIVIDADES por componente
- Narrativas correctas estilo MIR
- 4 indicadores: 2 trimestrales, 2 semestrales
- Sentidos: 2 ascendentes, 1 descendente, 1 regular
- Criterios CREMAA completos

### Programa 2 — "Desarrollo Productivo" (SE-001, MIR con defectos para IA)

- Clave: `DP-002`
- Misma estructura de niveles con defectos intencionales:
  - **suggestNarrativeSyntax**: Narrativas vagas ("Hacer que mejore la economía")
  - **validateCremaa**: Indicador sin CREMAA ("Porcentaje de cosas")
  - **validateVerticalLogic**: Actividad incoherente (capacitación bajo infraestructura)
  - **validateHorizontalLogic**: Medios de verificación inconsistentes
  - **generateJustification**: Meta=100, Resultado=15 → semáforo rojo
- 3 indicadores: 2 trimestrales, 1 anual

### Programa 3 — "Salud Preventiva" (SS-002, MIR bien estructurada)

- Clave: `SP-003`
- Estructura: FIN → PROPOSITO → 1 COMPONENTE → 2 ACTIVIDADES
- 2 indicadores trimestrales
- Prueba aislamiento entre URs

## Calendarización

| Frecuencia | Periodos generados | Con datos |
|------------|-------------------|-----------|
| Trimestral | 4 (P1-P4) | Solo P1 |
| Semestral | 2 (P1-P2) | Ninguno |
| Anual | 1 (P1) | Ninguno |

## Avances y Semáforos (P1 trimestral)

| Programa | Indicador | Capturado por | Estado | Resultado | Meta | Semáforo |
|----------|-----------|---------------|--------|-----------|------|----------|
| Prog1 | Ind1 (ascendente) | operador | aprobado | 95 | 80 | verde |
| Prog1 | Ind2 (descendente) | operador | en_revision | 45 | 30 | amarillo |
| Prog2 | Ind1 (ascendente) | operador | observado | 15 | 100 | rojo |
| Prog2 | Ind2 (ascendente) | operador | en_captura | — | — | — |
| Prog3 | Ind1 (ascendente) | operador2 | aprobado | 90 | 80 | verde |
| Prog2 | Ind-vencido | operador | — | — | — | vencido |

### Cobertura de estados

- `en_captura`: Prog2-Ind2 (sin enviar)
- `en_revision`: Prog1-Ind2 (enviado, pendiente aprobación)
- `observado`: Prog2-Ind1 (devuelto con observaciones)
- `aprobado`: Prog1-Ind1, Prog3-Ind1
- `vencido`: Prog2 meta con fecha_cierre pasada

### Cobertura de semáforos

- Verde: resultado ≥ meta (95/80, 90/80)
- Amarillo: resultado intermedio (45/30 descendente)
- Rojo: resultado muy bajo (15/100)

## Resultados esperados

Se generará `docs/qa/expected-results.md` con valores calculados para comparar contra el sistema:

- Semáforo esperado por avance
- Dashboard admin SE-001: programas=2, indicadores=7, avancePromedio, vencidos=1
- Dashboard admin SS-002: programas=1, indicadores=2
- Dashboard operador (ele.operador): pendientes, capturadosMes
- Distribución semáforo global por UR

## Arquitectura del Seeder

```
QaTestingSeeder (idempotente)
├── Verifica URs existentes (SE-001, SS-002 del DesarrolloSeeder)
├── Crea/actualiza 6 usuarios (firstOrCreate por email)
├── Asigna roles y teams (syncWithoutDetaching)
├── Crea 3 programas (firstOrCreate por clave+team)
├── Crea niveles MIR (firstOrCreate por programa+tipo+orden)
├── Crea indicadores (firstOrCreate por mir_nivel+nombre)
├── Genera MetaPeriodos (firstOrCreate por indicador+periodo+ejercicio)
├── Crea Avances (updateOrCreate por meta_periodo+indicador)
└── Genera docs/qa/expected-results.md
```

## Decisiones técnicas

- **Idempotencia**: Todo con `firstOrCreate` / `updateOrCreate`. Ejecutar múltiples veces no duplica datos.
- **Independencia**: No modifica `DesarrolloSeeder`. Depende de que existan las URs (SE-001, SS-002).
- **Datos realistas**: Narrativas, indicadores y metas con valores plausibles del dominio público mexicano.
- **Verificación**: Archivo de resultados esperados para comparación manual y futura automatización.
