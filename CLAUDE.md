# DTE-SPP 2026

## Stack
- Laravel 12, PHP 8.2+, PostgreSQL (pgvector/pgvector:pg16)
- Jetstream + Livewire 3 + Teams
- barryvdh/laravel-dompdf, maatwebsite/excel, symfony/expression-language
- Tests: `./vendor/bin/sail artisan test`

## Arquitectura
- Estructura híbrida Laravel + dominio: Cascade/, Mml/, Tracking/, Evaluation/
- Frontend: Atomic Design con Slots (Layout → Page → Module)
- Rutas: `routes/web/` separado por dominio (cascade.php, mml.php, etc.)

## Convenciones
- Commits: `tipo(scope): descripción` + `Resolves DTE-<n>` para Linear
- Sin modales para flujos principales (crear/editar = páginas completas)
- Componentes Blade: x-page.container, x-page.header, x-forms.section, etc.
- Tests: baseline 426 passed, 7 skipped

## Dominio del Negocio
- Sistema de Planeación para Programas Presupuestales (PbR-SED)
- MIR (Matriz de Indicadores para Resultados) es el módulo central
- Wizard de planeación MML para construir MIR paso a paso
- Reportes: Sábana de Captura, Concentrado, MIR Aprobada
- Alineación: PND → PED → ODS → Programas Derivados
