# Guía del Operador — DTE-SPP 2026

> El Operador captura los avances trimestrales de indicadores asignados a su UR.

---

## Tu rol en el sistema

Eres responsable de registrar los resultados de los indicadores de tus programas presupuestarios en cada periodo de captura. Tu trabajo alimenta directamente los reportes de desempeño que se presentan a la ASFE.

---

## Vistas disponibles

### Dashboard (`/dashboard`)
Panel principal. Muestra un resumen de tus indicadores pendientes y el estado general.

### Mis Indicadores (`/seguimiento/pendientes`)
**Tu vista principal de trabajo.** Lista todos los indicadores con periodos abiertos para captura.

Cada indicador muestra:
- Programa y nombre del indicador
- Periodo (trimestre) abierto
- Meta programada para ese periodo
- Estado: En captura / Capturado / Observado

### Captura de avance (`/seguimiento/captura/{avance}`)
Formulario de captura de un indicador específico.

**Flujo de captura:**

1. Selecciona el indicador desde "Mis Indicadores"
2. Ingresa los valores de las **variables** de la fórmula
3. Haz clic en **Calcular** — el sistema evalúa la fórmula y calcula el semáforo
4. Si el resultado es **amarillo o rojo**, el sistema te pedirá una **justificación**
   - Puedes escribirla manualmente o usar el botón "Generar con IA" como sugerencia
5. Opcionalmente adjunta **evidencia** (archivos PDF, imágenes)
6. Haz clic en **Guardar**

> Una vez guardado, el avance pasa al flujo de revisión. Ya no podrás editarlo a menos que un Planeador lo devuelva con observaciones.

### Sábana de Captura (`/seguimiento/sabana-captura`)
Tabla completa con todos los indicadores y sus avances por periodo. Solo lectura — útil para ver el panorama general.

### Concentrado de Captura (`/seguimiento/concentrado-captura`)
Resumen condensado de los avances capturados. Solo lectura.

### Reportes (`/evaluacion/transversal`)
Reporte transversal de desempeño. Puedes consultarlo y exportar PDF/Excel.

---

## Flujo típico de trabajo

```
Periodo se abre → Notificación llega → Mis Indicadores → Capturar →
Guardar → Planeador revisa → Aprobado / Observado → (si observado: corregir)
```

---

## Lo que NO puedes hacer

- Crear o editar programas
- Modificar la MIR
- Revisar o aprobar avances de otros
- Gestionar presupuesto
- Gestionar sustento legal
- Administrar usuarios
