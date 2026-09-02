<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CatalogosSeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array{id: int, catalogo_id: int|null, nombre: string, orden: int, activo: bool, deleted_at: null}> $expected */
        $expected = require database_path('data/catalogos.php');

        $this->assertDefinition($expected);
        $this->withExplicitZeroId(function () use ($expected): void {
            DB::transaction(function () use ($expected): void {
                if (DB::table('catalogo_relaciones')->exists()) {
                    throw new RuntimeException('Catalog reference data is invalid: catalogo_relaciones must be empty.');
                }

                $actual = $this->readCatalogos();

                if ($actual === []) {
                    foreach ($expected as $catalogo) {
                        DB::table('catalogos')->insert($catalogo);
                    }
                    $this->assertExactCatalogos($expected);
                    $this->assertSafeAutoIncrement($expected);

                    return;
                }

                $this->assertExactCatalogos($expected);
            });
        });
    }

    private function assertDefinition(array $expected): void
    {
        $ids = array_column($expected, 'id');
        $expectedIds = [0, 1, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 57, 61, 62, 63, 64, 65, 66, 67, 68, 69];

        if ($ids !== $expectedIds) {
            throw new RuntimeException('Catalog reference data definition does not contain the exact contractual IDs in insertion order.');
        }

        $definedIds = array_flip($ids);

        foreach ($expected as $catalogo) {
            if ($catalogo['catalogo_id'] !== null && ! isset($definedIds[$catalogo['catalogo_id']])) {
                throw new RuntimeException("Catalog reference data parent is missing for ID [{$catalogo['id']}].");
            }
        }
    }

    private function assertExactCatalogos(array $expected): void
    {
        if ($this->readCatalogos() !== $expected) {
            throw new RuntimeException('Catalog reference data is partial, divergent, soft-deleted, or contains unexpected rows. No reconciliation was attempted.');
        }
    }

    private function readCatalogos(): array
    {
        return DB::table('catalogos')
            ->select(['id', 'catalogo_id', 'nombre', 'orden', 'activo', 'deleted_at'])
            ->orderBy('id')
            ->get()
            ->map(static fn (object $catalogo): array => [
                'id' => (int) $catalogo->id,
                'catalogo_id' => $catalogo->catalogo_id === null ? null : (int) $catalogo->catalogo_id,
                'nombre' => (string) $catalogo->nombre,
                'orden' => (int) $catalogo->orden,
                'activo' => (bool) $catalogo->activo,
                'deleted_at' => $catalogo->deleted_at === null ? null : (string) $catalogo->deleted_at,
            ])
            ->all();
    }

    private function assertSafeAutoIncrement(array $expected): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $metadata = DB::selectOne(
            'select auto_increment from information_schema.tables where table_schema = ? and table_name = ?',
            [DB::connection()->getDatabaseName(), 'catalogos'],
        );

        if ($metadata === null || (int) $metadata->auto_increment <= max(array_column($expected, 'id'))) {
            throw new RuntimeException('Catalog reference data insertion did not advance AUTO_INCREMENT safely.');
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
