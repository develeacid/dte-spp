# Guía del Planeador — DTE-SPP 2026

> El Planeador es el rol más amplio. Diseña programas presupuestarios, construye la MIR paso a paso, revisa y aprueba avances, gestiona catálogos y exporta reportes.

---

## Tu rol en el sistema

Eres el arquitecto de los programas presupuestarios. Defines el problema, construyes los árboles de problemas y objetivos, seleccionas alternativas, alineas con el PED/PND/ODS, y construyes la MIR completa. También revisas los avances que capturan los Operadores y generas los reportes institucionales.

---

## Módulo: Planeación (MML)

### Lista de Programas (`/mml/programas`)
Tu punto de entrada. Lista todos los programas de tu UR con su estado de planeación.

### Wizard de 7 etapas
Cada programa se construye paso a paso:

| Etapa | Ruta | Qué haces |
|-------|------|-----------|
| 1. Definición del problema | `/mml/{id}/etapa/1` | Describes el problema central que atiende el programa |
| 2. Árbol de problemas | `/mml/{id}/etapa/2` | Construyes causa → efecto del problema |
| 3. Árbol de objetivos | `/mml/{id}/etapa/3` | Transformas problemas en objetivos (medio → fin) |
| 4. Selección de alternativa | `/mml/{id}/etapa/4` | Eliges qué conjunto de medios implementar |
| 5. Población objetivo | `/mml/{id}/etapa/5` | Defines el embudo poblacional (potencial → objetivo → atendida) |
| 6. Alineación estratégica | `/mml/{id}/etapa/6` | Vinculas con PED → PND → ODS. Aquí también ves el estado jurídico del programa |
| 7. MIR | `/mml/{id}/etapa/7/mir` | Matriz de Indicadores para Resultados — Fin, Propósito, Componentes, Actividades con indicadores |

### Importaciones (`/mml/importar`)
Carga masiva de programas desde archivos Excel/CSV cuando hay muchos programas por registrar.

---

## Módulo: Seguimiento

### Panel de Seguimiento (`/seguimiento`)
Vista de supervisión. Muestra todos los programas con:
- Indicadores por programa y su estado de captura
- Distribución de semáforos (verde/amarillo/rojo)
- Avances pendientes de revisión

### Vencidos (`/seguimiento/vencidos`)
Indicadores cuyo periodo de captura ya cerró sin que se registrara avance. Requieren atención.

### Flujo de aprobación (`/seguimiento/flujo/{avance}`)
Revisas el avance capturado por un Operador:
- Ves el resultado calculado y el semáforo
- Revisas la justificación (si aplica) y las evidencias
- **Apruebas** o **Devuelves con observaciones**

### Desbloqueos (`/seguimiento/desbloqueos`)
Solicitudes de Operadores para reabrir avances ya cerrados. Puedes aprobar o rechazar.

### Sábana de Captura / Concentrado
Reportes tabulares con todos los datos de seguimiento.

---

## Módulo: Catálogos

### Plan Estatal de Desarrollo (`/cascade/ped`)
CRUD del PED: ejes, temas, objetivos estratégicos, estrategias, líneas de acción.

### Programas Derivados (`/cascade/programas-derivados`)
Catálogo de programas derivados del PED.

### Matriz de Alineación (`/cascade/alineacion`)
Vinculación cruzada: Líneas de acción PED ↔ Objetivos PND ↔ Metas ODS.

---

## Módulo: Reportes

### Transversal (`/evaluacion/transversal`)
Reporte de desempeño con datos cruzados de todos los programas. Exportable a PDF y Excel.

### Datos Abiertos (`/evaluacion/datos-abiertos/diccionario`)
Diccionario de datos y exportación en formatos abiertos (CSV, JSON, ZIP).

---

## Consulta: Presupuesto y Jurídico

| Módulo | Qué ves | Acceso |
|--------|---------|--------|
| Presupuesto — Panel | KPIs financieros, avance por programa | Solo lectura |
| Presupuesto — Cuenta Pública | Reporte cruzado | Puede exportar PDF/Excel |
| Jurídico — Panel | Estado jurídico de programas | Solo lectura |
| Jurídico — Programa | Fundamentos y validación | Solo lectura |

---

## Flujo típico de trabajo

```
Inicio de ejercicio:
  Catálogos → Verificar PED/PND/ODS → Crear programas → Wizard MML (7 etapas)

Trimestral:
  Panel Seguimiento → Revisar avances capturados → Aprobar o devolver →
  Verificar vencidos → Generar reporte transversal

Cierre:
  Exportar reportes → Verificar Cuenta Pública → Datos Abiertos
```

---

## Lo que NO puedes hacer

- Capturar avances de indicadores (eso es del Operador)
- Gestionar partidas presupuestales (eso es del Financiero)
- Validar sustento legal (eso es del Jurídico)
- Administrar usuarios
