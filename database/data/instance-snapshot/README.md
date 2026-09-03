# Snapshots de esta instancia

Estos archivos no son Reference Data de ApplicationBase y no forman parte de
`php artisan db:seed`.

La secuencia completa de reconstrucción es:

```text
php artisan migrate
php artisan db:seed

APPLICATIONBASE_ALLOW_INSTANCE_SNAPSHOT=true
php artisan db:seed --class=InstanceParameterSnapshotSeeder
php artisan db:seed --class=InstanceOrganizationSnapshotSeeder
php artisan db:seed --class=InstanceUsersSnapshotSeeder

restore external avatar bundle into public/uploads/avatar
verify restored files against avatar-files-manifest.php
```

Los seeders requieren opt-in explícito y no son ejecutados por
`DatabaseSeeder`. Parameters es independiente de los otros snapshots;
Organización debe restaurarse antes que Users.

Cada seeder sólo acepta tablas vacías o una restauración que ya coincida
exactamente. No reconcilian, actualizan ni eliminan datos existentes.

El snapshot de Users conserva los hashes de password ya persistidos; nunca
incluye ni documenta contraseñas en texto plano.

El snapshot de Parameters conserva la configuración exacta de esta instancia.
Los archivos visuales que referencia se encuentran versionados en
`public/config`, por lo que también están disponibles desde un clon limpio.

## Familias de reproducibilidad de instancia

Los snapshots de base de datos cubren Parameters, Organization y Users. Los
archivos físicos de avatar constituyen una cuarta familia independiente:
**External Instance File Bundle**.

El bundle no está almacenado en Git. Debe contener exactamente los 108 archivos
referenciados por los 54 usuarios del snapshot: 54 variantes `small` y 54
variantes `medium`, restauradas como
`public/uploads/avatar/{basename}_{variant}.jpg`.

El bundle excluye los 64 archivos huérfanos, los dos fallbacks y `.gitignore`.
Debe verificarse contra `avatar-files-manifest.php`, que registra por archivo su
usuario, basename, variante, ruta relativa, tamaño y SHA-256.

Estos archivos pueden contener datos personales. El bundle debe conservarse
fuera del repositorio y bajo acceso controlado. Es necesario para reproducir
visualmente esta instancia; los fallbacks genéricos llegan directamente con Git.
Este mecanismo no usa `storage/app` ni requiere `php artisan storage:link`.

Los fingerprints se calculan con SHA-256 sobre JSON determinista, sin escapar
Unicode ni barras y conservando el orden `user_id ASC`, `small`, `medium`.
`AVATAR_MANIFEST_SHA256` usa cada entrada completa; `AVATAR_CONTENT_SET_SHA256`
usa sólo `relative_path`, `size_bytes` y `sha256`, en ese orden.
