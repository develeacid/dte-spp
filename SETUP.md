# Setup Guide

## Prerequisites

- Docker Desktop (or Docker Engine + Docker Compose)
- Node.js 18+ and npm
- Git

## Initial Setup

1. **Clone the repository:**
   ```bash
   git clone <repo-url>
   cd dte-spp-2026
   ```

2. **Copy environment file:**
   ```bash
   cp .env.example .env
   ```

3. **Start Sail containers:**
   ```bash
   ./vendor/bin/sail up -d
   ```
   This starts PostgreSQL, Redis, and the PHP application container.

4. **Install PHP dependencies:**
   ```bash
   ./vendor/bin/sail composer install
   ```

5. **Generate application key:**
   ```bash
   ./vendor/bin/sail artisan key:generate
   ```

6. **Run migrations and seed:**
   ```bash
   ./vendor/bin/sail artisan migrate --seed
   ```

7. **Install and build frontend assets:**
   ```bash
   npm install
   npm run build     # production build
   npm run dev       # or start Vite dev server
   ```

8. **Access the application:**
   Open http://localhost in your browser.

## Environment Variables

Key variables in `.env`:

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_EJERCICIO_FISCAL` | Fiscal year | Current year |
| `REPORT_INSTITUCION` | Institution name for reports | Gobierno del Estado |
| `REPORT_DEPENDENCIA` | Agency name for reports | Secretaria de Planeacion |
| `EVAL_PESO_FIN` | Weight for Fin level | 0.40 |
| `EVAL_PESO_PROPOSITO` | Weight for Proposito level | 0.30 |
| `EVAL_PESO_COMPONENTE` | Weight for Componente level | 0.20 |
| `EVAL_PESO_ACTIVIDAD` | Weight for Actividad level | 0.10 |

## Stopping the Environment

```bash
./vendor/bin/sail down        # stop containers
./vendor/bin/sail down -v     # stop and remove volumes (destroys DB data)
```
