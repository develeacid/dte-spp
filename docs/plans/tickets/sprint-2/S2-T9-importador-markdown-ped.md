# Plan: S2-T9 — Importador de PED desde Markdown

**Ticket:** S2-T9
**Tipo:** feat
**Rama:** `feat/S2-T9-importador-markdown-ped`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S2-T3, S1-T3

---

## Contexto

El PED estatal se publica como documento de texto estructurado. Este importador permite al planeador subir un archivo Markdown con la jerarquia completa del PED y convertirlo en registros de la base de datos, evitando captura manual de cientos de nodos. El flujo incluye previsualizacion y edicion antes de confirmar.

**Decisiones tecnicas:**
- Parser de Markdown basado en headings (`#` = Plan, `##` = Eje, `###` = Tema, etc.)
- Servicio `PedMarkdownParser` separado de la logica Livewire para testabilidad
- Transaccion de BD: todo o nada al confirmar importacion
- Los registros creados disparan el Observer de embeddings (S2-T10) automaticamente

---

## Pre-requisitos

- S2-T3 completado (modelos PED para persistencia)
- S1-T3 completado (permiso `gestionar_catalogos`)

---

## Detalles Tecnicos

**Formato Markdown esperado:**
```markdown
# Plan Estatal de Desarrollo 2022-2027

## Eje 1: Seguridad y Justicia
### Tema 1.1: Prevencion del delito
#### Objetivo 1.1.1: Reducir la incidencia delictiva juvenil
##### Estrategia 1.1.1.1: Programas de intervencion temprana
- Linea de Accion 1.1.1.1.1: Implementar talleres en zonas de riesgo
- Linea de Accion 1.1.1.1.2: Crear centros comunitarios
```

**Flujo del importador:**
1. Usuario sube archivo Markdown via formulario Livewire
2. `PedMarkdownParser` parsea el archivo y genera arbol en memoria
3. Vista de previsualizacion muestra arbol resultante (editable)
4. Usuario confirma → transaccion BD crea todos los registros en cascada
5. Redireccion a S2-T6 (CRUD PED) para revision

---

## Criterios de aceptacion

- [ ] Servicio `App\Services\PedMarkdownParser` parsea Markdown con la estructura definida
- [ ] Componente Livewire con upload de archivo `.md` (validacion de tipo MIME)
- [ ] Previsualizacion de arbol resultante antes de confirmar — editable inline
- [ ] Al confirmar, creacion en transaccion BD (`DB::transaction()`) — rollback si falla cualquier insert
- [ ] Manejo de errores de formato con mensajes claros: "Linea X: se esperaba heading nivel 3, se encontro nivel 5"
- [ ] Solo accesible con permiso `gestionar_catalogos`
- [ ] Test unitario: `PedMarkdownParser` parsea correctamente un Markdown de ejemplo y retorna array estructurado
- [ ] Test unitario: Markdown mal formado lanza excepcion con mensaje descriptivo
- [ ] Test integracion: importacion completa crea todos los registros esperados en BD

---

## Notas

- El parser es tolerante con espacios extra y lineas vacias entre secciones
- Si ya existe un PED activo, se desactiva el anterior al confirmar la importacion (solo un plan activo)
- El servicio `PedMarkdownParser` es independiente de Livewire para poder reutilizarse en comandos artisan