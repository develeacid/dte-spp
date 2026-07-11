# Sprint — Completar la captura del editor MIR

> Diseño aprobado 2026-07-11. Cierra 5 brechas del Informe de Brechas verificado
> (`docs/sistema/brechas/README.md`) concentradas en el editor MIR (`MirEditor`) y
> sus exports. Ver contexto de brechas en ese registro.

## Objetivo

Cerrar cinco huecos de captura del editor MIR donde el modelo/BD ya soporta el
dato (o casi) y solo falta cablear la UI, más un campo nuevo con migración. Todas
son brechas reales confirmadas contra el código.

## Alcance (5 brechas)

| # | Brecha (Informe) | Naturaleza | Migración |
|---|------------------|-----------|:---:|
| 1 | M05 #15/#12 — Sentido no editable en el editor MIR | UI + validador | No |
| 2 | M05 #8/#13 — Valor de línea base no capturable | UI + setter | No |
| 3 | M03 req9 / M04 #8 — Código A1.1 no se muestra | Accessor + fila + exports | No |
| 4 | M05 #17 — Falta frecuencia TRIANUAL | Enum + reglas | No |
| 5 | M05 #11 — Campo Definición del indicador inexistente | Migración + UI + ficha | **Sí** |

Decisiones de alcance tomadas en brainstorming:
- Definición **se incluye** (migración + editor + ficha técnica), aunque rompe el
  "cero migraciones".
- El código jerárquico se propaga a **MirEditor + exports PDF/Excel**.

## Diseño por ítem

### 1. Sentido editable

- `resources/views/livewire/mml/partials/mir-nivel-row.blade.php`: agregar
  `sentido` al objeto `x-data ind` con default
  `@js($indicador->sentido?->value ?? 'ascendente')` y un `<select>`
  Ascendente/Descendente en el grid de la fila (edit-mode).
- `app/Livewire/Mml/MirEditor.php::guardarIndicador()`: añadir
  `'sentido' => ['required', Rule::in(SentidoIndicador::values())]` al validador.
- Solo se exponen `ascendente`/`descendente`. `SentidoIndicador::REGULAR` está
  deprecado (Hardening MIR); no se ofrece en la UI.

### 2. Valor de línea base

- Nuevo método `MirEditor::guardarLineaBase(int $indicadorId, ?string $valor)` con
  regla `nullable|numeric` (columna `linea_base` decimal 12,4, ya fillable).
  Normaliza `''` → `null`, espejo de `guardarLineaBaseAnio()`.
- Input numérico (`step` decimal) junto al de "Año de línea base" en el blade.

### 3. Código jerárquico (A1.1)

- Nuevo accessor en `app/Models/Mml/MirNivel.php` — `codigoMir(): string` (o
  `getCodigoMirAttribute`):
  - `FIN → 'F'`
  - `PROPOSITO → 'P'`
  - `COMPONENTE → 'C{orden}'`
  - `ACTIVIDAD → 'A{componente.orden}.{orden}'`
  - Reutiliza la resolución del componente padre que ya hace
    `App\Support\Mml\Trazabilidad::deNivel`. El formato del temario (`A1.1`)
    difiere del `A1 · C1` de `Trazabilidad::nivelCorto()`, por eso accessor propio.
  - Fallback seguro si la actividad no tiene componente padre (log + `A?.{orden}`),
    coherente con Trazabilidad.
- **Fila** (`mir-nivel-row.blade.php`): badge de código junto al label del tipo,
  en read-mode y edit-mode.
- **Exports**:
  - `resources/views/exports/pdf/mir.blade.php`: código en la celda "Nivel".
  - `app/Exports/Excel/MirSheet.php`: nueva columna "Código" (heading + valor).
  - `resources/views/exports/pdf/ficha-tecnica.blade.php`: renglón "Código".

### 4. Frecuencia TRIANUAL (decisión normativa aprobada)

- `app/Enums/FrecuenciaMedicion.php`: nuevo `case TRIANUAL = 'trianual'`,
  label "Trianual". `orden()` reordenado (trianual = cada 3 años, entre bianual y
  sexenal): mensual=1, trimestral=2, semestral=3, anual=4, bianual=5,
  **trianual=6**, sexenal=7.
- `app/Services/Mml/IndicadorReglasService::frecuenciasPermitidas()` ampliado:

  | Nivel | Actual | +Añadido |
  |-------|--------|----------|
  | FIN | Anual, Bianual, Sexenal | +Trianual |
  | PROPÓSITO | Semestral, Anual | +Trianual |
  | COMPONENTE | Trimestral, Semestral | +Anual |
  | ACTIVIDAD | Mensual, Trimestral | +Semestral |

### 5. Definición del indicador

- Migración: `definicion` TEXT nullable en `indicadores` (sin backfill).
- `app/Models/Mml/Indicador.php`: agregar `definicion` a `$fillable`.
- Nuevo setter `MirEditor::guardarDefinicion(int $indicadorId, ?string $texto)`
  con `nullable|string|max:240` (límite del temario, Ficha Bloque 1). Textarea en
  el editor.
- Export: renglón "Definición" en `ficha-tecnica.blade.php`. **No** se agrega a la
  matriz MIR compacta (evita saturarla).

## Testing (TDD por ítem)

- Unit: `MirNivel::codigoMir()` (F/P/C1/A1.1 con componente padre + fallback);
  `FrecuenciaMedicion::orden()` y `frecuenciasPermitidas()` con las nuevas.
- Livewire (`MirEditor`): `guardarIndicador` persiste `sentido` y rechaza inválido;
  `guardarLineaBase` persiste valor y normaliza `''`; `guardarDefinicion` rechaza
  >240 ch.
- Migración/modelo: `definicion` existe y es fillable.
- Export: ficha-técnica contiene código + definición; `MirSheet` headings incluyen
  "Código".

## Fuera de alcance (anotado, no se toca)

`MirSheet` y `mir.blade.php` leen `$mv->descripcion` y `$var->descripcion`, pero
`MedioVerificacion`/`Variable` usan `nombre`/`simbolo` → probable bug preexistente
que deja MV/variables en blanco en esos exports. Se documenta como hallazgo; su
corrección es un fix aparte.

## Riesgos / notas

- Reordenar `FrecuenciaMedicion::orden()` afecta la validación cruzada B7 (MV vs
  indicador). Sexenal pasa de 6→7; ningún dato persiste el `orden` (es `match`),
  así que es seguro. Revisar tests de B7 tras el cambio.
- Agregar frecuencias antes prohibidas por nivel relaja reglas duras existentes;
  es intencional (alineación al temario), cubierto por tests de `frecuenciasPermitidas`.
