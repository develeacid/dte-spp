# Artisan Commands Reference

## Application Commands

### Cascade / Periodos

| Command | Schedule | Description |
|---------|----------|-------------|
| `mir:abrir-periodos` | Daily 06:00 | Opens capture periods whose start date has passed |
| `mir:cerrar-vencidos` | Daily 23:00 | Marks overdue avances as VENCIDO |

### Tracking / Reports

| Command | Schedule | Description |
|---------|----------|-------------|
| `reports:cleanup` | Daily 03:00 | Deletes generated report files older than TTL (default: 24h) |
| `reports:cleanup --hours=48` | — | Override TTL hours |
| `reports:cleanup --dry-run` | — | Preview what would be deleted |

### AI / Embeddings

| Command | Schedule | Description |
|---------|----------|-------------|
| `app:embeddings-generate` | Daily 02:00 | Generates embeddings for new/updated records |
| `app:embeddings-queue` | — | Processes embeddings queue |
| `app:calcular-indice-eficacia` | — | Calculates efficacy index for programs |

### Maintenance

| Command | Schedule | Description |
|---------|----------|-------------|
| `llm:cleanup-logs` | Monthly | Purges detailed LLM logs older than 90 days (preserves monthly summaries) |
| `llm:cleanup-logs --days=60` | — | Override retention period |
| `llm:cleanup-logs --dry-run` | — | Preview what would be deleted |

## Development Commands

```bash
# Run tests
./vendor/bin/sail artisan test
./vendor/bin/sail artisan test --parallel
./vendor/bin/sail artisan test --filter=ClassName

# Code style
./vendor/bin/pint              # fix
./vendor/bin/pint --test       # check only

# Clear caches
./vendor/bin/sail artisan optimize:clear

# Fresh migration with seed
./vendor/bin/sail artisan migrate:fresh --seed
```

## Scheduled Tasks Overview

All scheduled commands are registered in `routes/console.php`:

| Time | Command |
|------|---------|
| 02:00 | `app:embeddings-generate` |
| 03:00 | `reports:cleanup` |
| 06:00 | `mir:abrir-periodos` |
| 23:00 | `mir:cerrar-vencidos` |
| Monthly | `llm:cleanup-logs` |
