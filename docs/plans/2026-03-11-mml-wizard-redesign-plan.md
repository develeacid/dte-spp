# Plan de Ejecución: MML Wizard Redesign

> **Rama:** `feat/mml-wizard-redesign`
> **Baseline tests:** 172 passed (MML), 426 total
> **Regla:** No avanzar al siguiente task si los tests fallan.

## Instrucciones para el agente ejecutor

1. Ejecutar cada task usando un **subagente en worktree aislado**
2. Después de cada task, correr `./vendor/bin/sail artisan test --filter=Mml`
3. Si los tests pasan → commit, merge al branch, continuar
4. Si los tests fallan → arreglar antes de continuar
5. Después de cada etapa, **mostrar al usuario** qué cambió para revisión visual en navegador
6. El usuario aprueba visualmente antes de continuar con la siguiente etapa

## Arquitectura de cambios

Cada task toca SOLO la vista blade + el componente Livewire de su etapa.
NO se tocan rutas, modelos, ni migraciones (ya existen todos).
Los cambios son puramente de **UX/UI** — mejorar la experiencia visual sin romper la lógica.

---

## Task 1: Stepper visual mejorado (`x-mml.stepper`)
**Archivos:** `resources/views/components/mml/stepper.blade.php`
**Cambios:**
- Mejorar diseño visual: iconos SVG en vez de texto, transiciones suaves
- Añadir tooltips con nombre del paso al hover
- Hacer responsive: en móvil mostrar solo paso actual con flechas ← →
- Asegurar estados: completado (verde ✓), activo (azul), pendiente (gris), bloqueado (candado)
**Tests:** El stepper es un componente Blade puro, verificar que las vistas de etapas 1-6 siguen renderizando correctamente
**Criterio de éxito:** Tests pasan + usuario confirma visualmente

## Task 2: Etapa 1 — Definición del Problema
**Archivos:**
- `app/Livewire/Mml/DefinicionProblema.php`
- `resources/views/livewire/mml/definicion-problema.blade.php`
**Cambios UI:**
- Textarea con auto-resize y placeholder guiado: "Ej: Alta tasa de desnutrición infantil..."
- Panel de IA estilo Notion: lateral o inferior, no modal
- Mostrar resultado de validación IA con badges de color (estado negativo ✓, solución ⚠, ausencia ⚠)
- Botones claros: "Aceptar sugerencia" / "Descartar"
- Incluir stepper en el layout
**NO tocar:** Lógica de `validarConIa()`, `guardar()`, modelo Arbol/ArbolNodo
**Tests existentes:** `DefinicionProblemaTest.php` — deben seguir pasando

## Task 3: Etapa 2 — Árbol de Problemas
**Archivos:**
- `app/Livewire/Mml/ArbolProblemaBuilder.php`
- `resources/views/livewire/mml/arbol-problema-builder.blade.php`
- `resources/views/livewire/mml/partials/nodo-card.blade.php`
**Cambios UI:**
- Visualización tipo árbol con conectores CSS (líneas verticales/horizontales)
- Cards de nodos con colores: problema central (rojo), causas (naranja), efectos (morado)
- Sugerencias IA en panel lateral, no inline
- Contador: "3 causas / 2 efectos agregados"
- Botón "Siguiente: Árbol de Objetivos →" en footer
- Incluir stepper en el layout
**NO tocar:** Lógica de `agregarNodo()`, `eliminarNodo()`, `sugerirConIa()`, `guardarNuevoNodo()`
**Tests existentes:** `ArbolProblemaBuilderTest.php` — deben seguir pasando

## Task 4: Etapa 3 — Árbol de Objetivos
**Archivos:**
- `app/Livewire/Mml/ArbolObjetivosBuilder.php`
- `resources/views/livewire/mml/arbol-objetivos-builder.blade.php`
**Cambios UI:**
- Layout lado a lado: problema (rojo, izquierda) → objetivo (verde, derecha)
- Flecha de transformación entre nodos pareados
- Botón "Transformar todos con IA" que itera nodos pendientes
- Badge [Pendiente] en rojo, [Transformado] en verde
- Botón "Siguiente: Selección de Alternativa →" en footer
- Incluir stepper en el layout
**Cambio Livewire (mínimo):** Agregar método `transformarTodosConIa()` que itera nodos pendientes
**Tests existentes:** `ArbolObjetivosBuilderTest.php` — deben seguir pasando

## Task 5: Etapa 4 — Selección de Alternativas
**Archivos:**
- `app/Livewire/Mml/SeleccionAlternativas.php`
- `resources/views/livewire/mml/seleccion-alternativas.blade.php`
**Cambios UI:**
- Grid de evaluación responsive (cards en vez de tabla)
- Badge "Enlace MIR" en alternativa seleccionada
- Banner informativo explicando el propósito del paso
- Evaluación IA con indicadores visuales (viabilidad técnica/institucional/presupuestal)
- Botón "Siguiente: Poblaciones →" en footer
- Incluir stepper en el layout
**NO tocar:** Lógica de `crearAlternativa()`, `toggleNodo()`, `seleccionarAlternativa()`, `evaluarConIa()`
**Tests existentes:** `SeleccionAlternativasTest.php` — deben seguir pasando

## Task 6: Etapa 5 — Embudo de Poblaciones
**Archivos:**
- `app/Livewire/Mml/EmbudoPoblaciones.php`
- `resources/views/livewire/mml/embudo-poblaciones.blade.php`
**Cambios UI:**
- Visualización de embudo siempre visible (izquierda) con formulario (derecha)
- Embudo se actualiza en tiempo real al escribir cantidades
- Validación visual: borde rojo si objetivo > potencial > referencia
- Porcentajes automáticos entre niveles
- Botón "Siguiente: Alineación →" en footer
- Incluir stepper en el layout
**NO tocar:** Lógica de `guardar()`, validaciones de modelo
**Tests existentes:** `EmbudoPoblacionesTest.php` — deben seguir pasando

## Task 7: Etapa 6 — Alineación Estratégica
**Archivos:**
- `app/Livewire/Mml/AlineacionEstrategica.php`
- `resources/views/livewire/mml/alineacion-estrategica.blade.php`
**Cambios UI:**
- Cards con sugerencias IA pre-llenadas al cargar (si hay problema central)
- Selects dependientes con UX clara (eje → tema → objetivo)
- Panel de sugerencias semánticas con score de relevancia
- Sección de ODS con multiselect visual
- Anexos transversales como checkboxes con iconos
- CTA final "Finalizar Planeación y Crear MIR" cuando todos los pasos están completos
- Incluir stepper en el layout
**NO tocar:** Lógica de `buscarConIa()`, `guardar()`, `finalizarPlaneacion()`
**Tests existentes:** `AlineacionEstrategicaTest.php` — deben seguir pasando

---

## Protocolo de verificación por task

```bash
# Después de cada task:
./vendor/bin/sail artisan test --filter=Mml
# Esperado: 172 passed (o más si se agregan tests)

# Si falla:
# 1. Identificar qué test falló
# 2. Verificar que el cambio no rompió lógica
# 3. Arreglar y re-correr tests
# 4. NO avanzar hasta que pasen todos
```

## Protocolo de revisión visual por task

Después de que los tests pasan, informar al usuario:
1. Qué archivos se modificaron
2. Qué cambios visuales esperar
3. URL para revisar: `http://localhost/mml/{programa}/etapa/{N}`
4. Esperar aprobación del usuario antes de continuar
