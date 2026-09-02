<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RegionesComunasSeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array{id: int, nombre: string, orden: int}> $expectedRegiones */
        $expectedRegiones = require database_path('data/regiones.php');
        /** @var list<array{id: int, nombre: string, region_id: int}> $expectedComunas */
        $expectedComunas = require database_path('data/comunas.php');

        $this->assertDefinition($expectedRegiones, $expectedComunas);
        $this->withExplicitZeroId(function () use ($expectedRegiones, $expectedComunas): void {
            DB::transaction(function () use ($expectedRegiones, $expectedComunas): void {
                $actualRegiones = $this->readRegiones();
                $actualComunas = $this->readComunas();

                if ($actualRegiones === [] && $actualComunas === []) {
                    foreach ($expectedRegiones as $region) {
                        DB::table('regiones')->insert($region);
                    }

                    foreach ($expectedComunas as $comuna) {
                        DB::table('comunas')->insert($comuna);
                    }

                    $this->assertExactTerritory($expectedRegiones, $expectedComunas);
                    $this->assertSafeAutoIncrement('regiones', $expectedRegiones);
                    $this->assertSafeAutoIncrement('comunas', $expectedComunas);

                    return;
                }

                $this->assertExactTerritory($expectedRegiones, $expectedComunas);
            });
        });
    }

    private function assertDefinition(array $regiones, array $comunas): void
    {
        if (count($regiones) !== 16 || count($comunas) !== 347) {
            throw new RuntimeException('Territory reference data definition has unexpected row counts.');
        }

        $regionIds = array_column($regiones, 'id');
        $comunaIds = array_column($comunas, 'id');
        $sortedRegionIds = $regionIds;
        $sortedComunaIds = $comunaIds;
        sort($sortedRegionIds);
        sort($sortedComunaIds);

        if ($regionIds !== $sortedRegionIds || $comunaIds !== $sortedComunaIds) {
            throw new RuntimeException('Territory reference data must be ordered by ascending ID.');
        }

        if ($regiones[0] !== ['id' => 0, 'nombre' => 'No especificado', 'orden' => 0]) {
            throw new RuntimeException('Territory reference data has an invalid region sentinel.');
        }

        if ($comunas[0] !== ['id' => 0, 'nombre' => 'No especificado', 'region_id' => 0]) {
            throw new RuntimeException('Territory reference data has an invalid comuna sentinel.');
        }

        $definedRegionIds = array_flip($regionIds);

        foreach ($comunas as $comuna) {
            if (! isset($definedRegionIds[$comuna['region_id']])) {
                throw new RuntimeException("Territory reference data contains an orphan comuna [{$comuna['id']}].");
            }
        }
    }

    private function assertExactTerritory(array $expectedRegiones, array $expectedComunas): void
    {
        if ($this->readRegiones() !== $expectedRegiones || $this->readComunas() !== $expectedComunas) {
            throw new RuntimeException('Territory reference data is mixed, partial, divergent, or contains unexpected rows. No reconciliation was attempted.');
        }
    }

    private function readRegiones(): array
    {
        return DB::table('regiones')
            ->select(['id', 'nombre', 'orden'])
            ->orderBy('id')
            ->get()
            ->map(static fn (object $region): array => [
                'id' => (int) $region->id,
                'nombre' => (string) $region->nombre,
                'orden' => (int) $region->orden,
            ])
            ->all();
    }

    private function readComunas(): array
    {
        return DB::table('comunas')
            ->select(['id', 'nombre', 'region_id'])
            ->orderBy('id')
            ->get()
            ->map(static fn (object $comuna): array => [
                'id' => (int) $comuna->id,
                'nombre' => (string) $comuna->nombre,
                'region_id' => (int) $comuna->region_id,
            ])
            ->all();
    }

    private function assertSafeAutoIncrement(string $table, array $expected): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $metadata = DB::selectOne(
            'select auto_increment from information_schema.tables where table_schema = ? and table_name = ?',
            [DB::connection()->getDatabaseName(), $table],
        );

        if ($metadata === null || (int) $metadata->auto_increment <= max(array_column($expected, 'id'))) {
            throw new RuntimeException("Territory reference data did not advance AUTO_INCREMENT safely for [$table].");
        }
    }

    private function withExplicitZeroId(callable $callback): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $callback();

            return;
        }

        $originalSqlMode = (string) DB::selectOne('select @@session.sql_mode as sql_mode')->sql_mode;
        $sqlMode = collect(explode(',', $originalSqlMode))
            ->filter()
            ->push('NO_AUTO_VALUE_ON_ZERO')
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
