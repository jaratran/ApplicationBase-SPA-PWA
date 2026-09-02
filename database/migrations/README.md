# Migrations activas

Este directorio contiene las migrations constitutivas del baseline de ApplicationBase. El historial heredado y las podas ya ejecutadas se conservan en `database/migration-history` y no deben reintroducirse aquí.

El baseline comenzó con el bloque framework/auth. Estas migrations pueden crear su bloque o adoptar explícitamente un schema existente compatible; la adopción siempre requiere opt-in y coincidencia exacta del nombre de la base de datos.

Las migrations del baseline son deliberadamente irreversibles.
