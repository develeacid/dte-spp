# Página de Ayuda: Glosario + Marco Normativo (M01 req 7 + 27) — Diseño

> Diseño aprobado 2026-07-11. Cierra dos brechas de M01 Fundamentos del Informe
> (`docs/sistema/brechas/README.md`): no existe página de glosario (req 27) ni
> página informativa del marco normativo (req 7).

## Objetivo

Exponer en la app una página de **Ayuda** con dos secciones didácticas:
**Marco Normativo** (leyes y artículos clave del PbR-SED) y **Glosario** (términos
PbR/GpR/SED/MIR). Contenido de referencia estático, accesible a todos los roles.

## Decisiones (aprobadas)

- **Glosario**: renderizar el contenido ya escrito en
  `docs/sistema/fuente-de-verdad/glosario_MIR.md`. Se copia a
  `resources/markdown/glosario-mir.md` (fuente que consume la app; la de `docs/`
  permanece como fuente-de-verdad documental) y se renderiza con `Str::markdown()`.
- **Marco Normativo**: reutilizar la tabla `CatalogoOrdenamiento` (15 ordenamientos
  ya seedeados), agrupada por `nivel_jerarquia` con `NivelJerarquiaLegal::label()`,
  más una intro didáctica estática con los artículos clave del temario.

## Arquitectura

Página estática sin interactividad → **controlador + vista Blade** (no Livewire,
YAGNI). Ruta `GET /ayuda` (name `ayuda`), dentro del grupo `auth` de
`routes/web.php`. **Sin permiso** (contenido para todos los roles autenticados).

### Componentes

- `app/Http/Controllers/AyudaController.php` (invocable `__invoke`):
  - `$ordenamientos = CatalogoOrdenamiento::where('activo', true)->orderBy('orden')->get()->groupBy(fn ($o) => $o->nivel_jerarquia->label())`.
  - `$glosarioHtml = Str::markdown(file_get_contents(resource_path('markdown/glosario-mir.md')))`.
  - retorna `view('ayuda.index', compact('ordenamientos', 'glosarioHtml'))`.
- `resources/views/ayuda/index.blade.php`: `x-page.container` (título "Ayuda") con
  tabs Alpine **Marco Normativo** / **Glosario**.
  - Marco Normativo: intro didáctica (LFPRH art. 111, LGCG art. 46-III-C,
    Lineamientos SHCP-CONEVAL) + lista de ordenamientos agrupados por jerarquía
    (abreviatura + nombre).
  - Glosario: `{!! $glosarioHtml !!}` en contenedor `prose`.
- `resources/markdown/glosario-mir.md`: copia del glosario.
- Ruta en `routes/web.php` (grupo `auth`, junto a `dashboard`).
- Ítem `Ayuda` en `resources/views/components/layout/sidebar-nav.blade.php`
  (standalone, al final; `x-ui.sidebar-item` con icono).

## Testing

`tests/Feature/AyudaPageTest`:
- Usuario autenticado → 200; ve "Glosario", "Marco Normativo", un término del
  glosario ("Administración Pública") y un ordenamiento ("Ley Federal de
  Presupuesto").
- Operador (rol sin permisos especiales) → 200 (no gated por permiso).
- Guest → redirige a `login`.

## Fuera de alcance

- Búsqueda/filtro del glosario, edición desde UI, versionado de contenido.
- Artículos por-ordenamiento en BD (el catálogo solo tiene nombre/abreviatura; los
  artículos clave viven en la intro estática).

## Notas

- **Duplicación**: el `.md` queda en `docs/` (documental) y `resources/` (render de
  la app). Contenido de referencia estable; `resources/markdown/glosario-mir.md` es
  la copia que consume la app.
- `Str::markdown()` usa `league/commonmark` (ya instalado). El HTML del glosario es
  contenido propio confiable (no input de usuario) → `{!! !!}` es seguro aquí.
