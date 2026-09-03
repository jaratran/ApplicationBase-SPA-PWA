# Snapshots de esta instancia

Estos archivos no son Reference Data de ApplicationBase y no forman parte de
`php artisan db:seed`.

La secuencia completa de reconstrucción es:

```text
php artisan migrate
php artisan db:seed

APPLICATIONBASE_ALLOW_INSTANCE_SNAPSHOT=true
php artisan db:seed --class=InstanceOrganizationSnapshotSeeder
php artisan db:seed --class=InstanceUsersSnapshotSeeder
```

Los seeders requieren opt-in explícito y no son ejecutados por
`DatabaseSeeder`. Organización debe restaurarse antes que Users.

Cada seeder sólo acepta tablas vacías o una restauración que ya coincida
exactamente. No reconcilian, actualizan ni eliminan datos existentes.

El snapshot de Users conserva los hashes de password ya persistidos; nunca
incluye ni documenta contraseñas en texto plano.
