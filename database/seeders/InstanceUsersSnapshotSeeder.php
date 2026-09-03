<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class InstanceUsersSnapshotSeeder extends Seeder
{
    public function run(): void
    {
        if (! filter_var(env('APPLICATIONBASE_ALLOW_INSTANCE_SNAPSHOT', false), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException('Instance users snapshot seeding requires APPLICATIONBASE_ALLOW_INSTANCE_SNAPSHOT=true.');
        }

        if (DB::connection()->getDatabaseName() === 'db_laportada') {
            throw new RuntimeException('ApplicationBase instance snapshots must never run against db_laportada.');
        }

        foreach (['users', 'catalogos', 'comunas', 'empresas', 'sucursales'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Instance users snapshot requires table [$table].");
            }
        }

        $snapshot = require database_path('data/instance-snapshot/users.php');

        if (! is_array($snapshot)) {
            throw new RuntimeException('Instance users snapshot is invalid.');
        }

        $this->assertSnapshotIntegrity($snapshot);

        DB::transaction(function () use ($snapshot): void {
            $this->assertReferences($snapshot);
            $count = DB::table('users')->count();

            if ($count === 0) {
                foreach (array_chunk($snapshot, 100) as $chunk) {
                    DB::table('users')->insert($chunk);
                }

                if (! $this->tableMatches($snapshot)) {
                    throw new RuntimeException('Instance users snapshot postcondition failed.');
                }

                return;
            }

            if ($this->tableMatches($snapshot)) {
                $this->command?->info('Instance users snapshot already matches exactly; no changes were made.');

                return;
            }

            throw new RuntimeException('Users table is neither empty nor an exact snapshot match. No changes were made.');
        });
    }

    private function assertSnapshotIntegrity(array $snapshot): void
    {
        $ids = array_column($snapshot, 'id');

        if (count($ids) !== count(array_unique($ids, SORT_REGULAR))) {
            throw new RuntimeException('Instance users snapshot contains duplicate IDs.');
        }

        if ($snapshot !== [] && array_keys($snapshot[0]) !== [
            'id', 'rut_usuario', 'nombre_usuario', 'apellidos_usuario', 'rol_id',
            'empresa_id', 'sucursal_id', 'telefono', 'email', 'email_verified_at',
            'avatar', 'comuna_id', 'direccion', 'es_admin', 'activo',
            'observacion_inactividad', 'fecha_login', 'remember_token', 'password',
            'created_at', 'updated_at',
        ]) {
            throw new RuntimeException('Instance users snapshot column set or order is incompatible.');
        }
    }

    private function assertReferences(array $snapshot): void
    {
        $this->assertIdsExist('catalogos', array_column($snapshot, 'rol_id'));
        $this->assertIdsExist('comunas', array_column($snapshot, 'comuna_id'));
        $this->assertIdsExist('empresas', array_column($snapshot, 'empresa_id'));
        $this->assertIdsExist('sucursales', array_column($snapshot, 'sucursal_id'));
    }

    private function assertIdsExist(string $table, array $ids): void
    {
        $expected = array_values(array_unique(array_filter($ids, static fn (mixed $id): bool => $id !== null)));
        $actual = DB::table($table)->whereIn('id', $expected)->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)->all();
        $missing = array_values(array_diff($expected, $actual));

        if ($missing !== []) {
            throw new RuntimeException("Instance users snapshot requires missing [$table] IDs: ".implode(', ', $missing).'.');
        }
    }

    private function tableMatches(array $expected): bool
    {
        if (DB::table('users')->count() !== count($expected)) {
            return false;
        }

        if ($expected === []) {
            return true;
        }

        $actual = DB::table('users')->select(array_keys($expected[0]))->orderBy('id')->get()
            ->map(static fn (object $row): array => (array) $row)->all();

        return $actual === $expected;
    }
}
