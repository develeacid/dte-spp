# DTE-SPP 2026

Sistema de Planeacion Programatica — Direccion Tecnica de Evaluacion.

## Documentacion

La documentacion integral del sistema vive en [`docs/sistema/`](docs/sistema/README.md):

- **[Indice + matriz de cobertura](docs/sistema/README.md)** — punto de entrada, mapa del sistema y trazabilidad temario ↔ codigo.
- **[Manuales por rol](docs/sistema/manuales/)** — guias operativas de dte-spp (operador, planeador, analistas, RDA, admin) y geobase.
- **[Registro de brechas verificadas](docs/sistema/brechas/README.md)** — brechas reales del ecosistema, validadas contra el codigo.
- **[Fuente de verdad](docs/sistema/fuente-de-verdad/)** — glosario MIR, inventario de conceptos del temario, modulos M01–M10 y la integracion geobase ⋈ dte.

## Stack

- **Backend:** Laravel 12, PHP 8.2+, PostgreSQL 16
- **Frontend:** Livewire 3, Alpine.js, Tailwind CSS
- **Auth:** Jetstream (Teams) + Spatie Permission
- **Dev:** Docker via Laravel Sail

## Quick start

```bash
# Clone and setup
git clone <repo-url> && cd dte-spp-2026
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail composer install
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
npm install && npm run build
```

See [SETUP.md](SETUP.md) for detailed installation and [COMMANDS.md](COMMANDS.md) for artisan commands.

## Testing

```bash
./vendor/bin/sail artisan test            # all tests
./vendor/bin/sail artisan test --parallel  # parallel execution
```

## CI/CD

GitHub Actions runs on push to `desarrollo` and `main`:
- PHPUnit test suite
- Laravel Pint code style check
- Composer security audit

## License

Proprietary — Gobierno del Estado.
