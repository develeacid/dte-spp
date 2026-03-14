# Guía del Analista Jurídico — DTE-SPP 2026

> El Analista Jurídico registra el sustento legal de los programas presupuestarios y valida que cumplan con el marco normativo aplicable.

---

## Tu rol en el sistema

Eres responsable de verificar que cada programa tenga fundamento jurídico válido antes de que pueda operar. Registras los ordenamientos legales, subes documentos normativos (ROP, leyes), y emites la validación jurídica. Tu trabajo es el pilar legal de la validación tripartita.

---

## Vistas disponibles

### Panel Jurídico (`/juridico`)
**Tu vista principal.** Muestra todos los programas de tu UR con:
- KPIs: Validados, Pendientes, Rechazados, Sin registro
- Tabla de programas con estado jurídico y checklist (3 columnas: Facultad UR, Mandato, ROP)
- Filtro por ejercicio fiscal y estado
- Alerta de documentos próximos a vencer (30 días)

### Vista de Programa (`/juridico/programa/{id}`)
Detalle del sustento legal de un programa específico, dividido en 3 secciones:

**Sección 1 — Fundamentos jurídicos:**
- Lista de ordenamientos agrupados por tipo (Facultad UR, Mandato de gasto, ROP, Otro)
- Cada fundamento muestra: ordenamiento, artículo, nivel jerárquico, vigencia
- Botones para agregar, editar o eliminar fundamentos

**Sección 2 — Documentos normativos:**
- PDFs subidos con metadata (tipo, tamaño, estado de verificación)
- Link para gestionar documentos

**Sección 3 — Estado de validación:**
- Checklist automático: ✓/✗ Facultad UR, ✓/✗ Mandato gasto, ✓/✗/N.A. ROP
- Estado actual y observaciones
- Link para ir a la vista de validación

### Registrar Fundamento (`/juridico/programa/{id}/fundamento/create`)
Formulario para registrar un nuevo fundamento jurídico:
- **Tipo de sustento**: Facultad de la UR, Mandato de gasto, Regla de operación, Otro
- **Ordenamiento**: selecciona del catálogo (15 leyes precargadas) o escribe manualmente
- **Artículo(s)**: texto libre (ej: "Art. 45, Frac. III, inciso b")
- **Nivel de jerarquía**: Constitucional → Federal → Estatal → Reglamentario → Operativo
- **Descripción**: cómo este ordenamiento faculta al programa
- **Vigente**: toggle sí/no

> Al seleccionar del catálogo, el nombre y nivel de jerarquía se llenan automáticamente.

### Gestionar Documentos (`/juridico/programa/{id}/documentos`)
Subida y administración de documentos normativos PDF:
- **Subir**: arrastra o selecciona PDF (máx 10MB), indica tipo, nombre, fechas de publicación y vigencia
- **Verificar**: marca un documento como verificado (confirma que es auténtico)
- **Descargar**: descarga controlada con verificación de permisos
- **Eliminar**: borra documento del sistema

### Validación Jurídica (`/juridico/programa/{id}/validacion`)
**Vista de decisión.** Aquí emites tu dictamen:
- Checklist automático (se recalcula con los fundamentos y documentos registrados)
- Detalle expandible de cada fundamento y documento
- Campo de observaciones (obligatorio para rechazar)
- 3 acciones: **Validar jurídicamente** (verde), **Rechazar** (rojo), **Marcar en revisión** (amarillo)

---

## Los 3 tipos de sustento legal

| Tipo | Pregunta que responde | Obligatorio |
|------|----------------------|:-----------:|
| **Facultad de la UR** | ¿La Ley Orgánica dice que a esta dependencia le toca resolver este problema? | ✓ |
| **Mandato de gasto** | ¿Existe una ley que obligue al Estado a gastar en esto? | ✓ |
| **Reglas de Operación** | Si el programa entrega subsidios, ¿las ROP están publicadas en el Periódico Oficial? | Solo si `requiere_rop` |

---

## Flujo típico de trabajo

```
Panel Jurídico → Seleccionar programa pendiente →
  1. Registrar Facultad de la UR (fundamento)
  2. Registrar Mandato de gasto (fundamento)
  3. Si requiere ROP: subir PDF de Reglas de Operación → Verificar
  4. Ir a Validación → Revisar checklist → Validar o Rechazar
```

---

## También puedes consultar

| Módulo | Qué ves | Nivel de acceso |
|--------|---------|----------------|
| Presupuesto — Panel | Datos financieros de programas | Solo lectura |

---

## Lo que NO puedes hacer

- Crear o editar programas / MIR
- Capturar avances (físicos o financieros)
- Gestionar partidas presupuestales
- Administrar usuarios
