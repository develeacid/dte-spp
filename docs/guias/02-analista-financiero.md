# Guía del Analista Financiero — DTE-SPP 2026

> El Analista Financiero gestiona las partidas presupuestales y captura el avance financiero de los programas de su UR.

---

## Tu rol en el sistema

Eres responsable de registrar la estructura presupuestal de cada programa (partidas con montos aprobados), capturar el avance financiero trimestral, y preparar los datos para el reporte de Cuenta Pública. Tu trabajo es el pilar financiero de la validación tripartita.

---

## Vistas disponibles

### Panel Presupuestal (`/presupuesto`)
**Tu vista principal.** Muestra todos los programas de tu UR con:
- Total aprobado vs ejercido
- Porcentaje de ejercicio (con semáforo visual)
- Barra de avance por programa
- Estado de validación tripartita (MIR / Legal / Costeo)

Puedes filtrar por ejercicio fiscal.

### Partidas (`/presupuesto/partidas`)
Lista de todas las partidas presupuestales registradas.

### Crear Partida (`/presupuesto/partidas/create`)
Formulario para registrar una nueva partida:
- Selecciona el programa presupuestario
- Ingresa la **clave de partida** (COG, ej: "1000")
- Descripción (ej: "Servicios Personales")
- **Monto aprobado** y opcionalmente monto modificado
- Ejercicio fiscal

### Editar Partida (`/presupuesto/partidas/{id}/edit`)
Modifica los datos de una partida existente (claves, montos, descripción).

### Captura de Avance Financiero (`/presupuesto/captura/{programa}`)
Registra el avance financiero trimestral de un programa:
- Monto comprometido, devengado y pagado por partida
- El sistema calcula automáticamente el porcentaje de ejercicio

### Importar Partidas (`/presupuesto/importar`)
Carga masiva de partidas desde archivo CSV. Útil al inicio del ejercicio fiscal.

### Cuenta Pública (`/presupuesto/cuenta-publica`)
Vista previa del reporte de Cuenta Pública. Muestra datos cruzados: financiero + indicadores + alineación + sustento legal.

### Exportar PDF/Excel
Desde la vista de Cuenta Pública puedes descargar:
- `/presupuesto/exportar/pdf/{ejercicio}` — PDF
- `/presupuesto/exportar/excel/{ejercicio}` — Excel

---

## También puedes consultar

| Módulo | Qué ves | Nivel de acceso |
|--------|---------|----------------|
| Jurídico — Panel | Estado jurídico de programas | Solo lectura |
| Jurídico — Programa | Fundamentos y validación | Solo lectura |

---

## Flujo típico de trabajo

```
Inicio de ejercicio:
  Importar partidas CSV → Verificar montos → Asignar a programas

Trimestral:
  Panel Presupuestal → Seleccionar programa → Capturar avance financiero

Cierre:
  Cuenta Pública → Verificar datos → Exportar PDF/Excel
```

---

## Lo que NO puedes hacer

- Crear o editar programas / MIR
- Capturar avances de indicadores (físicos)
- Validar sustento legal
- Administrar usuarios
