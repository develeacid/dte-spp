# DTE-SPP 2026

Sistema de Planeacion Programatica — Direccion Tecnica de Evaluacion.

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
