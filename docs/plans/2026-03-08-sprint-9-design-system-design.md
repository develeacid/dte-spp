# Sprint 9: Design System & Layout Redesign — Design Document

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:writing-plans to create the implementation plan from this design.

**Goal:** Establish a complete design system (branding, tokens, typography, components) and replace the Jetstream horizontal navbar with a collapsible sidebar layout with role-based navigation.

**Sprint 10 (deferred):** Dashboards operativos con widgets modulares, tablero de análisis, ApexCharts integration, componentes Blade para gráficas.

---

## Design Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Brand identity | Moderno-neutro (no institucional) | Survives administration changes |
| Visual tone | Clean/Minimal (Notion/Linear style) | White-dominant, subtle borders, breathing room |
| Navigation | Fixed left sidebar, collapsible to icons | Always visible, maximizes content when collapsed |
| Primary color | Emerald (green-teal) | Distinguishable, conveys growth/progress |
| Sidebar scheme | White bg, emerald accents on active | Clean, color comes from accents only |
| Typography | Inter (Google Fonts) | Best readability for data-heavy UI |
| Dashboard | Single route, modular widgets by permissions | Extensible, less code than separate dashboards |
| Chart library | ApexCharts (Sprint 10) | Interactive, heatmaps, sparklines built-in |
| Wireframes scope | Layout shell + dashboard only | Existing 42 views migrate gradually |

---

## 1. Branding & Design Tokens

### Color Palette

| Token | Use | Tailwind | RGB |
|-------|-----|----------|-----|
| `--color-primary` | Main actions, active items, links | `emerald-600` | 5 150 105 |
| `--color-primary-hover` | Button/link hover | `emerald-700` | 4 120 87 |
| `--color-primary-light` | Active backgrounds, positive badges | `emerald-50` | 236 253 245 |
| `--color-primary-dark` | Text on light backgrounds | `emerald-800` | 6 95 70 |
| `--color-surface` | Cards, sidebar, modals | `white` | 255 255 255 |
| `--color-background` | Page background | `gray-50` | 249 250 251 |
| `--color-border` | Default borders | `gray-200` | 229 231 235 |
| `--color-text` | Primary text | `gray-900` | 17 24 39 |
| `--color-text-muted` | Secondary text | `gray-500` | 107 114 128 |
| `--color-danger` | Errors, red semaphore | `red-600` | 220 38 38 |
| `--color-warning` | Alerts, yellow semaphore | `amber-500` | 245 158 11 |
| `--color-success` | Confirmation, green semaphore | `emerald-600` | = primary |
| `--color-info` | Informational | `sky-500` | 14 165 233 |

### Typography

**Font:** Inter via Google Fonts (replaces Figtree/bunny.net)

| Level | Size | Weight | Use |
|-------|------|--------|-----|
| `h1` | `text-2xl` (24px) | `font-bold` | Page title |
| `h2` | `text-xl` (20px) | `font-semibold` | Main sections |
| `h3` | `text-lg` (18px) | `font-semibold` | Subsections |
| `h4` | `text-base` (16px) | `font-medium` | Group labels |
| `body` | `text-sm` (14px) | `font-normal` | General text, tables |
| `caption` | `text-xs` (12px) | `font-normal` | Help text, timestamps |

---

## 2. Tailwind Config & CSS Variables

### `resources/css/app.css`

```css
@layer base {
  :root {
    --color-primary: 5 150 105;
    --color-primary-hover: 4 120 87;
    --color-primary-light: 236 253 245;
    --color-primary-dark: 6 95 70;
    --color-danger: 220 38 38;
    --color-warning: 245 158 11;
    --color-info: 14 165 233;
    --color-surface: 255 255 255;
    --color-background: 249 250 251;
    --color-border: 229 231 235;
    --color-text: 17 24 39;
    --color-text-muted: 107 114 128;

    --sidebar-width: 240px;
    --sidebar-collapsed-width: 64px;
    --font-sans: 'Inter', sans-serif;
  }
}
```

### `tailwind.config.js` extensions

```js
theme: {
  extend: {
    colors: {
      brand: {
        DEFAULT: 'rgb(var(--color-primary) / <alpha-value>)',
        hover: 'rgb(var(--color-primary-hover) / <alpha-value>)',
        light: 'rgb(var(--color-primary-light) / <alpha-value>)',
        dark: 'rgb(var(--color-primary-dark) / <alpha-value>)',
      },
      surface: 'rgb(var(--color-surface) / <alpha-value>)',
      background: 'rgb(var(--color-background) / <alpha-value>)',
    },
    fontFamily: {
      sans: ['var(--font-sans)', ...defaultTheme.fontFamily.sans],
    },
    width: {
      sidebar: 'var(--sidebar-width)',
      'sidebar-collapsed': 'var(--sidebar-collapsed-width)',
    },
  },
}
```

Usage in Blade: `bg-brand`, `text-brand-dark`, `border-brand`, `bg-brand/10`.
Standard Tailwind colors (gray, red, amber, etc.) remain available for non-themeable use.

---

## 3. Layout App & Sidebar

### Structure

```
┌─────────────┬────────────────────────────────────┐
│  Sidebar     │  Top Bar (sticky)                  │
│  240px       │  [breadcrumb]            [avatar]  │
│  ┌─────────┐├────────────────────────────────────│
│  │Logo+Name││                                    │
│  ├─────────┤│  Page Content                      │
│  │Nav Group││  (x-page.container)                │
│  │  Item   ││                                    │
│  │  Item   ││                                    │
│  │Nav Group││                                    │
│  │  Item   ││                                    │
│  ├─────────┤│                                    │
│  │Team Sw. ││                                    │
│  │User/Out ││                                    │
│  └─────────┘│                                    │
└─────────────┴────────────────────────────────────┘
```

### Behavior

- **Expanded (default desktop):** 240px, icon + text + chevron for sections
- **Collapsed:** 64px, icons only with tooltip on hover
- **Toggle:** Button at sidebar bottom (chevron left/right)
- **Mobile (< 1024px):** Hidden, opens as overlay with dark backdrop, hamburger button in top bar
- **Persistence:** Collapsed/expanded state saved in `localStorage`

### Top bar (inside content area)

- Sticky top, white background, subtle bottom border
- Left: breadcrumb (optional slot)
- Right: user avatar with dropdown (profile, API tokens, logout)
- No navigation duplication — all nav lives in sidebar

### Jetstream compatibility

- `navigation-menu.blade.php` replaced completely
- `x-app-layout` maintained but internal content changes
- Team switcher moves to sidebar footer

---

## 4. Navigation Mapping by Roles

| Section | Items | Permission Required | Visible to |
|---------|-------|-------------------|------------|
| **Inicio** | Dashboard | *(none)* | All |
| **Planeación** | Programas | `crear_programa` | Admin, Planeador |
| | Editor MIR | `editar_mir` | Admin, Planeador |
| | Importaciones | `crear_programa` | Admin, Planeador |
| **Seguimiento** | Panel | `revisar_avance` | Admin, Planeador |
| | Mis Indicadores | `capturar_avance` | Admin, Operador |
| | Vencidos | `revisar_avance` | Admin, Planeador |
| **Catálogos** | Plan Estatal | `gestionar_catalogos` | Admin, Planeador |
| | Programas Derivados | `gestionar_catalogos` | Admin, Planeador |
| | Matriz de Alineación | `gestionar_catalogos` | Admin, Planeador |
| **Reportes** | Evaluación | `exportar_reportes` | All |
| | Transversal | `exportar_reportes` | All |
| | Datos Abiertos | `exportar_reportes` | All |
| **Administración** | Usuarios | `administrar_usuarios` | Admin |
| | Monitor IA | `administrar_usuarios` | Admin |

### Rules

- If a complete section has no visible items for the role, the section hides
- Icons: Heroicons (outline) — already available with Jetstream/Blade
- Active section auto-expands on navigation
- Collapsed: shows section icon only, hover opens popover with items

### Resulting view by role

- **Operador:** Inicio, Seguimiento (Mis Indicadores), Reportes
- **Planeador:** Inicio, Planeación, Seguimiento, Catálogos, Reportes
- **Admin:** Everything

---

## 5. Component Inventory

### New Components

| Component | Description |
|-----------|-------------|
| `x-ui.sidebar` | Sidebar shell: logo, nav groups, footer, collapse toggle |
| `x-ui.sidebar-group` | Section with label, icon, collapsible items |
| `x-ui.sidebar-item` | Individual link: icon, text, optional badge count |
| `x-ui.topbar` | Sticky top bar: breadcrumb slot, avatar dropdown |
| `x-ui.avatar` | Circle with initials or photo, sizes sm/md/lg |
| `x-ui.widget` | Dashboard card: title, value, icon, trend |
| `x-ui.stat` | Compact stat: label + large number + percentage change |
| `x-ui.empty-state` | No-data placeholder: icon, text, action |
| `x-ui.tooltip` | Alpine.js tooltip for collapsed sidebar |

### Existing Components to Adapt

| Component | Change |
|-----------|--------|
| `x-ui.button.primary` | `bg-indigo-600` → `bg-brand` |
| `x-ui.button.secondary` | No change (neutral gray) |
| `x-ui.button.danger` | No change (red) |
| `x-ui.badge` | Keep variants, add `brand` option |
| `x-page.container` | `bg-gray-100` → `bg-background` |
| `x-page.header` | Adapt sizes to typography hierarchy |
| `x-page.form-footer` | Adjust `left` to sidebar width |
| `x-forms.section` | Border tokens only |
| `x-modals.confirm` | `focus:ring-indigo-500` → `focus:ring-brand` |

### Jetstream Component Mapping

| Jetstream | Our Equivalent | Action |
|-----------|---------------|--------|
| `x-button` | `x-ui.button.primary` | Align color to brand |
| `x-secondary-button` | `x-ui.button.secondary` | Already exists |
| `x-danger-button` | `x-ui.button.danger` | Already exists |
| `x-input` | Keep as-is | Change focus ring to brand |
| `x-checkbox` | Keep as-is | `text-indigo-600` → `text-brand` |
| `x-nav-link` | Replaced by `x-ui.sidebar-item` | Jetstream nav disappears |
| `x-dropdown` | Keep as-is | Used in topbar avatar |
| `x-modal` | Keep as-is | Base for confirm modal |

---

## 6. Wireframes

### Layout Shell

```
Desktop expanded:
┌─────────────┬────────────────────────────────────┐
│  ☰ SPP 2026 │  Catálogos > Plan Estatal    (JD) │
│─────────────│────────────────────────────────────│
│  ◇ Inicio   │                                    │
│             │  Plan Estatal de Desarrollo         │
│  □ Planeac. │  Gestionar la estructura del PED   │
│    Programas│           [+ Nuevo Eje]            │
│    MIR      │                                    │
│    Importar │  ┌──────────────────────────────┐  │
│             │  │  Content area               │  │
│  ◈ Seguim.  │  │  (varies by view)           │  │
│    Panel    │  │                              │  │
│    Mis Ind. │  │                              │  │
│             │  │                              │  │
│  ◆ Catálog. │  │                              │  │
│    Plan Est.│  │                              │  │
│    Prog.Der.│  │                              │  │
│    Alineac. │  │                              │  │
│             │  └──────────────────────────────┘  │
│  ◑ Reportes │                                    │
│─────────────│                                    │
│  ★ Admin    │                                    │
│─────────────│                                    │
│  ⇅ UR Norte │                                    │
│  (JD) Salir │                                    │
└─────────────┴────────────────────────────────────┘

Desktop collapsed:
┌────┬───────────────────────────────────────────┐
│ ☰  │  Catálogos > Plan Estatal          (JD)  │
│────│───────────────────────────────────────────│
│ ◇  │                                           │
│ □  │  (more horizontal space for content)      │
│ ◈  │                                           │
│ ◆  │                                           │
│ ◑  │                                           │
│────│                                           │
│ ★  │                                           │
│────│                                           │
│ ⇅  │                                           │
│ JD │                                           │
└────┴───────────────────────────────────────────┘
```

### Dashboard — Widget Layout (Admin sees all)

```
┌─────────────────────────────────────────────────┐
│  Dashboard                                       │
│                                                  │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌─────┐│
│  │ Programas│ │Indicadore│ │ Avance   │ │Pend.││
│  │    12    │ │    87    │ │  68.4%   │ │ 14  ││
│  │ activos  │ │ en MIR   │ │ promedio │ │p/cap││
│  └──────────┘ └──────────┘ └──────────┘ └─────┘│
│                                                  │
│  ┌────────────────────────┐ ┌──────────────────┐│
│  │ Semáforo Global        │ │ Actividad Recient││
│  │ 🟢 42  🟡 28  🔴 17   │ │ • Juan capturó.. ││
│  │ ░░░░░░░░░░░░░░░░░░░░  │ │ • María aprobó.. ││
│  │ (stacked bar)          │ │ • Import. compl..││
│  └────────────────────────┘ └──────────────────┘│
│                                                  │
│  ┌──────────────────────────────────────────────┐│
│  │ Mis Acciones Pendientes          ver todo → ││
│  │ ┌──────┬──────────────┬─────────┬─────────┐ ││
│  │ │ Prog │ Indicador    │ Período │ Estado  │ ││
│  │ │ P001 │ % cobertura  │ T1-2026 │ ⏳ pend │ ││
│  │ │ P003 │ Tasa deserc. │ T1-2026 │ 🔴 venc │ ││
│  │ └──────┴──────────────┴─────────┴─────────┘ ││
│  └──────────────────────────────────────────────┘│
└─────────────────────────────────────────────────┘
```

### Widgets by Permission

| Widget | Permission | Roles |
|--------|-----------|-------|
| Stats (programas, indicadores, avance) | `revisar_avance` | Admin, Planeador |
| Semáforo Global | `revisar_avance` | Admin, Planeador |
| Actividad Reciente | *(none)* | All |
| Mis Acciones Pendientes | `capturar_avance` | Admin, Operador |
| Indicadores Vencidos (alert) | `revisar_avance` | Admin, Planeador |
| Resumen IA (costos/uso) | `administrar_usuarios` | Admin |

---

## Out of Scope (Sprint 9)

- Visual access controls (future sprint)
- ApexCharts integration (Sprint 10)
- Dashboard widget implementation (Sprint 10)
- Chart Blade components (Sprint 10)
- Migration of existing 42 Livewire views to new design (gradual)
