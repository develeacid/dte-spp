# Flujo de Contexto de IA en el Asistente MML

## Principio Fundamental

Cada paso del MML lee y escribe a la **base de datos**, no a una "sesión" o "chat" de IA.
No existe una sesión persistente de IA entre pasos. Cada llamada al LLM es independiente
y recibe su contexto desde los datos almacenados en la BD.

## Diagrama de Flujo de Contexto

### Paso 1 — Definición del Problema
- **Entrada**: Usuario escribe texto libre
- **IA**: Valida estructura del problema (validateProblema)
- **Persistencia**: ArbolNodo (tipo: problema_central) en BD
- **¿Contexto al paso 2?**: SÍ — se lee de ArbolNodo

### Paso 2 — Árbol del Problema
- **Entrada**: Problema central (de BD, paso 1)
- **IA — Sugerir causas**: Prompt incluye problema_central + causas existentes
- **IA — Sugerir efectos**: Prompt incluye problema_central + efectos existentes
- **IA — Sugerir causas indirectas**: Prompt incluye problema_central + causa directa padre + indirectas existentes
- **IA — Generar árbol ejemplo**: Prompt incluye solo problema_central
- **¿Puede repetir nodos?**: Mitigado — los nodos existentes se incluyen en el prompt con instrucción "no repetir"
- **¿Fusión de nodos similares?**: NO implementado actualmente (backlog)
- **Persistencia**: ArbolNodo con parent_id (relaciones padre-hijo)
- **¿Contexto al paso 3?**: SÍ — todo el árbol se lee de BD

### Paso 3 — Árbol de Objetivos
- **Entrada**: Árbol completo de problemas (de BD, paso 2)
- **IA**: Transforma cada nodo problema → objetivo positivo (transform)
- **Transformación es 1:1**: Cada nodo mantiene nodo_origen_id como referencia
- **Persistencia**: Nuevo Arbol tipo 'objetivos' con ArbolNodo derivados
- **¿Contexto al paso 4?**: SÍ — se lee de BD

### Paso 4 — Selección de Alternativas
- **Entrada**: Árbol de objetivos (de BD, paso 3)
- **IA**: Evalúa coherencia de cada alternativa individual (evaluarConIa)
- **¿Validación cruzada?**: NO — cada alternativa se evalúa independientemente.
  Alternativas contradictorias o similares NO se detectan automáticamente (backlog)
- **Persistencia**: Alternativa con nodos asociados (pivot table)
- **¿Contexto al paso 5?**: Indirecto — la alternativa seleccionada se usa en paso 6-7

### Paso 5 — Embudo de Poblaciones
- **Entrada**: Datos numéricos del usuario
- **IA**: No utilizada en este paso
- **Persistencia**: PoblacionPrograma en BD
- **¿Contexto al paso 6?**: Indirecto — se valida existencia como prerequisito

### Paso 6 — Alineación Estratégica
- **Entrada**: Problema central (de BD, paso 1-2) + catálogo PED con embeddings
- **IA**: Búsqueda semántica (SemanticSearchService) usando embeddings vectoriales
- **Persistencia**: MirNivel.ped_objetivo_estrategico_id
- **Contexto de pasos anteriores**: Usa problema_central como query de búsqueda
- **Finalización**: Genera MIR pre-llenada desde árbol de objetivos + alternativa seleccionada

### Paso 7 — Editor MIR
- **Entrada**: MIR pre-llenada (de paso 6) + todos los datos acumulados
- **IA — Validar sintaxis**: Usa texto del resumen narrativo + tipo de nivel
- **IA — Validar CREMAA**: Usa datos completos del indicador
- **IA — Extraer variables**: Usa texto de la fórmula
- **IA — Sugerir fórmula**: Usa nombre indicador + tipo + dimensión + resumen del nivel
- **IA — Validar lógica**: Usa estructura completa de la MIR
- **IA — Buscar alineación**: Búsqueda semántica similar a paso 6

## Resumen

| Paso | Lee contexto de | Escribe a | IA utilizada |
|------|-----------------|-----------|--------------|
| 1 | - | ArbolNodo (problema_central) | validateProblema |
| 2 | Paso 1 (BD) | ArbolNodo (causas, efectos) | suggest (causas, efectos, indirectas) |
| 3 | Paso 2 (BD) | ArbolNodo (objetivos) | transform |
| 4 | Paso 3 (BD) | Alternativa + pivot | evaluarConIa |
| 5 | - | PoblacionPrograma | - |
| 6 | Pasos 1-5 (BD) | MirNivel (alineación) | SemanticSearch |
| 7 | Paso 6 + todos (BD) | MirNivel, Indicador, etc. | validate, suggest, extract, search |
