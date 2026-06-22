# Agent Instructions

- Always run project commands through Docker Compose.
- For PHP/Laravel commands, use the app service, for example: `docker compose exec -T app php artisan ...`.
- Do not rely on the host PHP/Node versions for project verification.
