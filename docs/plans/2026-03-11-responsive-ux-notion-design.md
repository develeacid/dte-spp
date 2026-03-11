# Diseño Responsive UX/UI — Inspiración Notion

**Fecha:** 2026-03-11
**Estado:** Aprobado

## Contexto

La aplicación DTE-SPP funciona principalmente en desktop, pero los perfiles de
planeador para arriba necesitan una experiencia móvil funcional para consultas
y revisiones al momento. El sidebar hamburguesa no abría en móvil (ya corregido),
y múltiples elementos tienen problemas de UX en pantallas pequeñas.

## Principios de diseño (Notion)

- **Contenido centrado** — máximo ancho de lectura, sin ruido visual
- **Acciones contextuales** — aparecen donde y cuando se necesitan
- **Transiciones suaves** — sensación de app nativa
- **Tipografía como jerarquía** — pocos colores, la estructura la da el texto

## Usuarios y dispositivos

- **Desktop**: operadores, capturistas — flujos de captura de datos
- **Móvil**: planeadores, revisores, administradores — consulta, validación, dashboards
- Enfoque **híbrido**: responsive general + componentes alternativos para móvil

## 1. Navegación móvil (< lg)

### Bottom navigation bar
- Reemplaza el sidebar en móvil (sidebar se mantiene solo en desktop)
- 4-5 ítems con icono + etiqueta pequeña
- Visibilidad condicionada por roles/permisos (misma lógica `@can` del sidebar)
- Ítem activo con indicador sutil

| Icono | Etiqueta | Ruta | Permisos |
|-------|----------|------|----------|
| Home | Inicio | /dashboard | Todos |
| ClipboardList | Programas | /mml/programas | Todos |
| ChartBar | Seguimiento | /seguimiento | ver seguimiento |
| DocumentReport | Reportes | /reportes | ver reportes |
| Cog | Admin | /admin | solo admin |

### Bottom action bar contextual
- Barra fija encima de la bottom nav
- Acciones de la vista actual (Guardar, Exportar, Validar con IA)
- Aparece solo cuando hay acciones relevantes
- Estilo: fondo blanco, borde top sutil, botones compactos

## 2. MML Wizard — Diseño móvil

Interfaz principal de la aplicación, requiere atención especial.

| Componente | Desktop | Móvil |
|------------|---------|-------|
| **Stepper** | Horizontal con 6 pasos + labels | Mini stepper: números/iconos, paso actual expandido |
| **Árboles (problema/objetivos)** | Grid lado a lado | Stack vertical con cards colapsables |
| **Alternativas** | Tabla comparativa | Cards apiladas con swipe o tabs |
| **Embudo poblaciones** | Visualización ancha | Embudo compacto vertical |
| **Alineación estratégica** | Grid 3 columnas | Acordeón: Eje → Tema → Objetivo |
| **MIR Editor** | Tabla completa | Card por nivel, expandible |
| **Respuestas IA** | Panel inline | Bottom sheet (estilo Notion AI) |

> **Nota**: Los árboles de marco lógico (problema/objetivos) son un reto especial
> de diseño móvil. Se abordarán con diseño dedicado en el siguiente sprint.

## 3. Dashboard móvil

- **KPIs**: grid 2 columnas compacto
- **Gráficas**: apiladas verticalmente, ancho completo
- **Alertas/pendientes**: sección colapsable

## 4. Tablas — Columnas prioritarias

- Sistema de prioridad por columna (`high | medium | low`)
- Móvil: solo columnas `high`, botón "ver más" para expandir
- Aplica a: lista de programas, seguimiento, catálogos

## 5. Page headers

- Móvil: título en una línea, acciones se mueven al bottom action bar
- Subtítulo oculto o truncado

## 6. Formularios

- Campos apilan a 1 columna
- Labels arriba del campo
- Botones de submit en el bottom action bar

## 7. Cards/paneles

- Grid pasa de 3-4 columnas a 1-2 en móvil
- Padding reducido

## Fases de implementación

| Fase | Alcance | Prioridad |
|------|---------|-----------|
| **Fase 1** | Bottom nav + bottom action bar + page headers responsive | Alta — infraestructura |
| **Fase 2** | MML Wizard responsive (stepper, embudo, alineación, IA bottom sheet) | Alta — interfaz principal |
| **Fase 3** | Dashboard móvil (KPIs, gráficas) | Media |
| **Fase 4** | Seguimiento — tablas con columnas prioritarias | Media |
| **Fase 5** | Formularios y cards responsive generales | Baja |
| **Futura** | Árboles de marco lógico (diseño dedicado) + Reportes móvil | Siguiente sprint |
