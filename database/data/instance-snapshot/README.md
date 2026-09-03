# Snapshot de organización de esta instancia

Estos archivos no son Reference Data de ApplicationBase y no forman parte de
`php artisan db:seed`.

Su restauración requiere una ejecución explícita:

```text
APPLICATIONBASE_ALLOW_INSTANCE_SNAPSHOT=true
php artisan db:seed --class=InstanceOrganizationSnapshotSeeder
```

El seeder sólo acepta tablas organizacionales vacías o una restauración que ya
coincida exactamente. No reconcilia, actualiza ni elimina datos existentes.
