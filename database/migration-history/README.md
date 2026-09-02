# Historial de migrations

Este directorio conserva migrations históricas exclusivamente como evidencia, material de auditoría y referencia para resolver dudas concretas o verificar el baseline. Laravel no lo incluye en su flujo automático de migrations.

## Estructura

- `laportada-original/`: 64 migrations recuperadas de la aplicación madre original.
- `calidad-sanctum/`: migration de `personal_access_tokens` publicada durante la construcción de Calidad.
- `applicationbase-pruning/`: migrations ejecutadas durante la poda logística de ApplicationBase.

Estos archivos no deben copiarse automáticamente de vuelta a `database/migrations`, ni ejecutarse mediante `--path` sobre bases de datos reales. No constituyen el baseline vigente y pueden contener estructuras o dominio que ya fueron eliminados.

La fuente principal para construir el baseline es el contrato persistente actual junto con el código actual. Este historial es sólo una fuente secundaria.
