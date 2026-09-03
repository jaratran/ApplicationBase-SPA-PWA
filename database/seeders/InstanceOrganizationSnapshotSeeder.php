<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class InstanceOrganizationSnapshotSeeder extends Seeder
{
    private const TABLES = ['empresas', 'sucursales', 'maquilas'];

    public function run(): void
    {
        if (! filter_var(env('APPLICATIONBASE_ALLOW_INSTANCE_SNAPSHOT', false), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException('Instance organization snapshot seeding requires APPLICATIONBASE_ALLOW_INSTANCE_SNAPSHOT=true.');
        }

        if (DB::connection()->getDatabaseName() === 'db_laportada') {
            throw new RuntimeException('ApplicationBase instance snapshots must never run against db_laportada.');
        }

        foreach (['catalogos', 'comunas', ...self::TABLES] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Instance organization snapshot requires table [$table].");
            }
        }

        $snapshot = $this->loadSnapshot();
        $this->assertSnapshotIntegrity($snapshot);

        $this->withExplicitZeroId(function () use ($snapshot): void {
            DB::transaction(function () use ($snapshot): void {
                $this->assertReferenceData($snapshot);
                $counts = collect(self::TABLES)->mapWithKeys(
                    static fn (string $table): array => [$table => DB::table($table)->count()],
                );

                if ($counts->every(static fn (int $count): bool => $count === 0)) {
                    foreach (self::TABLES as $table) {
                        foreach (array_chunk($snapshot[$table], 100) as $chunk) {
                            DB::table($table)->insert($chunk);
                        }
                    }

                    foreach (self::TABLES as $table) {
                        if (! $this->tableMatches($table, $snapshot[$table])) {
                            throw new RuntimeException("Instance organization snapshot postcondition failed for [$table].");
                        }
                    }

                    return;
                }

                if (collect(self::TABLES)->every(
                    fn (string $table): bool => $this->tableMatches($table, $snapshot[$table]),
                )) {
                    $this->command?->info('Instance organization snapshot already matches exactly; no changes were made.');

                    return;
                }

                throw new RuntimeException('Organization tables are neither empty nor an exact snapshot match. No changes were made.');
            });
        });
    }

    private function loadSnapshot(): array
    {
        return collect(self::TABLES)->mapWithKeys(function (string $table): array {
            $rows = require database_path("data/instance-snapshot/$table.php");

            if (! is_array($rows)) {
                throw new RuntimeException("Instance organization snapshot [$table] is invalid.");
            }

            return [$table => $rows];
        })->all();
    }

    private function assertSnapshotIntegrity(array $snapshot): void
    {
        foreach (self::TABLES as $table) {
            $ids = array_column($snapshot[$table], 'id');

            if (count($ids) !== count(array_unique($ids, SORT_REGULAR))) {
                throw new RuntimeException("Instance organization snapshot [$table] contains duplicate IDs.");
            }
        }

        $empresaIds = array_fill_keys(array_column($snapshot['empresas'], 'id'), true);
        $sucursalIds = array_fill_keys(array_column($snapshot['sucursales'], 'id'), true);
        $pairs = [];

        foreach ($snapshot['maquilas'] as $maquila) {
            if (! isset($empresaIds[$maquila['empresa_id']])) {
                throw new RuntimeException("Snapshot maquila [{$maquila['id']}] references a missing empresa.");
            }

            if (! isset($sucursalIds[$maquila['sucursal_id']])) {
                throw new RuntimeException("Snapshot maquila [{$maquila['id']}] references a missing sucursal.");
            }

            $pair = "{$maquila['empresa_id']}:{$maquila['sucursal_id']}";

            if (isset($pairs[$pair])) {
                throw new RuntimeException("Instance organization snapshot contains duplicate maquila pair [$pair].");
            }

            $pairs[$pair] = true;
        }
    }

    private function assertReferenceData(array $snapshot): void
    {
        $catalogIds = array_values(array_unique([
            ...array_column($snapshot['empresas'], 'tipo_empresa_id'),
            ...array_column($snapshot['sucursales'], 'zona_id'),
            ...array_column($snapshot['sucursales'], 'tipo_sucursal_id'),
        ]));
        $comunaIds = array_values(array_unique([
            ...array_column($snapshot['empresas'], 'comuna_id'),
            ...array_column($snapshot['sucursales'], 'comuna_id'),
        ]));

        $this->assertIdsExist('catalogos', $catalogIds);
        $this->assertIdsExist('comunas', $comunaIds);
    }

    private function assertIdsExist(string $table, array $expectedIds): void
    {
        $actualIds = DB::table($table)->whereIn('id', $expectedIds)->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)->all();
        $missing = array_values(array_diff($expectedIds, $actualIds));

        if ($missing !== []) {
            throw new RuntimeException("Instance organization snapshot requires missing [$table] IDs: ".implode(', ', $missing).'.');
        }
    }

    private function tableMatches(string $table, array $expected): bool
    {
        if (DB::table($table)->count() !== count($expected)) {
            return false;
        }

        if ($expected === []) {
            return true;
        }

        $actual = DB::table($table)->select(array_keys($expected[0]))->orderBy('id')->get()
            ->map(static fn (object $row): array => (array) $row)->all();

        return $actual === $expected;
    }

    private function withExplicitZeroId(callable $callback): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $callback();

            return;
        }

        $originalSqlMode = (string) DB::selectOne('select @@session.sql_mode as sql_mode')->sql_mode;
        $sqlMode = collect(explode(',', $originalSqlMode))
            ->push('NO_AUTO_VALUE_ON_ZERO')
            ->filter()
            ->unique()
            ->implode(',');

        DB::statement('set session sql_mode = ?', [$sqlMode]);

        try {
            $callback();
        } finally {
            DB::statement('set session sql_mode = ?', [$originalSqlMode]);
        }
    }
}
