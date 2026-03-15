# Analisis: Anexos Transversales e Indicadores

> Generado: 2026-03-15

## 1. Los 4 Anexos Transversales del Sistema

Seedeados por AnexosTransversalesSeeder:

| Anexo | Clave | Descripcion |
|-------|-------|-------------|
| Igualdad de Genero | genero | Perspectiva de genero en programas (SHCP Anexo 13) |
| Niñas, Niños y Adolescentes | nna | Programas que impactan a NNA |
| Cambio Climatico | cambio_climatico | Programas con impacto ambiental (LGCC) |
| Anticorrupcion | anticorrupcion | Programas de transparencia y rendicion de cuentas |

## 2. Como se Vinculan a Indicadores

### Modelo de datos

```
Indicador ──── indicador_anexo_transversal (pivot) ──── AnexoTransversal
               indicador_id                              id, nombre, clave
               anexo_transversal_id                      descripcion, activo, orden
```

Un indicador puede tener 0, 1 o varios anexos transversales.
Un anexo puede estar vinculado a multiples indicadores de distintos programas.

### Quien asigna los vinculos

**Rol**: Planeador (editar_mir)
**Donde**: MirEditor, en el formulario de edicion de cada indicador
**Como**: Checkboxes con Alpine.js que llaman `syncAnexosTransversales(indicadorId, selectedIds)`

```php
// MirEditor.php
public function syncAnexosTransversales(int $indicadorId, array $anexoIds): void
{
    $indicador = Indicador::findOrFail($indicadorId);
    $indicador->anexosTransversales()->sync(array_map('intval', $anexoIds));
}
```

**Visualizacion**: Badges de color indigo bajo cada indicador en modo lectura de la MIR.

## 3. Panel Transversal (Evaluacion)

**Ruta**: `/evaluacion/transversal`
**Permiso**: `exportar_reportes`
**Componente**: PanelTransversal

### 4 Tabs del panel

| Tab | Metodo | Agrupa por | Datos |
|-----|--------|-----------|-------|
| PED | getDataPed() | Eje PED | Programas alineados, indice eficacia promedio |
| ODS | getDataOds() | Objetivo ODS | Programas vinculados, semaforos |
| UR | getDataUr() | Team/UR | Programas por UR, rendimiento |
| **Anexo** | **getDataAnexo()** | **Anexo Transversal** | **Indicadores taggeados, semaforos** |

### Tab Anexo — getDataAnexo()

Para cada AnexoTransversal activo:

```php
1. Busca indicadores vinculados al anexo
2. Agrupa por programa
3. Para cada programa: busca EvaluacionPrograma
4. Calcula:
   - total_indicadores: count de indicadores vinculados
   - total_programas: count de programas unicos
   - promedio_indice: promedio de indice_eficacia
   - semaforos: {verde: N, amarillo: N, rojo: N}
```

**Resultado**: Dashboard que muestra "¿Como van los indicadores de Genero?" o
"¿Cuantos indicadores de Cambio Climatico estan en rojo?"

## 4. Exports

### Excel: TransversalExcelExport

Tabla plana con columnas:
- Anexo, Clave Programa, Nombre Programa, Nivel MIR, Indicador, Meta

### PDF: TransversalPdfExport

Reporte de rendimiento por tema transversal con graficos de semaforo.

### Datos Abiertos

Ruta: `/evaluacion/datos-abiertos/transversal`
Formato: CSV/JSON para portal de transparencia

## 5. Visibilidad en Tracking

`PanelSeguimiento` carga `indicadores.anexosTransversales` y muestra badges
de color indigo junto a cada indicador. Esto permite al planeador revisor
ver de un vistazo que temas transversales cruzan cada indicador.

## 6. Brechas en Seeders

| Aspecto | Estado actual | Lo que deberia ser |
|---------|:---:|---|
| AnexosTransversales seedeados | Si (4 temas) | OK |
| Indicadores vinculados a anexos | **No** | Al menos 10-12 indicadores distribuidos en los 4 temas |
| EvaluacionPrograma calculada | **No** | Necesaria para que PanelTransversal funcione |
| Reportes transversales con datos | **No** | Sin datos, exports salen vacios |

### Distribucion sugerida para seeders

De los ~64 indicadores (16 programas x ~4 indicadores promedio):

| Anexo | Indicadores sugeridos | Programas tipicos |
|-------|:---:|---|
| Genero | 8-10 | Educacion (becas desagregadas), Salud (materna), Turismo (capacitacion) |
| NNA | 4-6 | Educacion basica, Salud infantil, Seguridad (prevencion) |
| Cambio Climatico | 3-4 | Turismo sustentable, Infraestructura escolar |
| Anticorrupcion | 2-3 | Seguridad (transparencia), cualquier programa con indicador de economia |

Total: ~20 vinculos indicador↔anexo, cubriendo los 4 temas.
