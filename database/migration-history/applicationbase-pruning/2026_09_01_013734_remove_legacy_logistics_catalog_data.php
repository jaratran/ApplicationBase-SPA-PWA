<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove the legacy logistics catalog data from ApplicationBase.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $database = DB::connection()->getDatabaseName();

            if ($database !== 'db_spa_pwa') {
                throw new \RuntimeException(
                    "Aborting legacy logistics catalog cleanup: expected database [db_spa_pwa], connected to [{$database}]."
                );
            }

            $expectedCatalogs = [
                34 => [null, 'Tipo Retiro'],
                35 => [34, 'Tolva'],
                36 => [34, 'Bins'],
                37 => [null, 'Tipo Especie'],
                38 => [37, 'Coho'],
                39 => [37, 'Salar'],
                40 => [37, 'Trucha'],
                41 => [null, 'Tipo Materia Prima'],
                42 => [41, 'Subproductos'],
                43 => [null, 'Tipo Camión'],
                44 => [43, 'Camion Simple'],
                45 => [43, 'Camion - Carro Plano'],
                46 => [43, 'Camión Grúa'],
                47 => [43, 'Rampa Frigorífica'],
                48 => [43, 'Rampa Plana'],
                49 => [43, 'Estanque 30000 Lt'],
                50 => [43, 'Estanque 15000 Lt'],
                51 => [43, 'Estanque 15000 Lt - Carro'],
                52 => [43, 'Batea'],
                53 => [null, 'Grupo de Tipo Camión'],
                54 => [53, 'BT'],
                55 => [53, 'TK'],
                56 => [53, 'BIN'],
                90 => [null, 'Estados de Retiro'],
                91 => [90, 'Esperando'],
                92 => [90, 'Comentado'],
                93 => [90, 'Aceptado'],
                94 => [90, 'Planificado'],
                95 => [90, 'Programado'],
                96 => [90, 'Terminado'],
                97 => [90, 'Cancelado'],
                110 => [null, 'Motivos de Cambio en Planificación'],
                111 => [110, 'Cambio de Fecha/Hora de Retiro'],
                112 => [110, 'Cambio en Horas de Viaje'],
                113 => [110, 'Cambio de ETA'],
                114 => [110, 'Cambio de Materia Prima'],
                115 => [110, 'Cambio de Especie'],
                116 => [110, 'Cambio de Existencia de Restricción'],
                117 => [110, 'Cambio de Camión'],
                118 => [110, 'Cambio de Rampla'],
                119 => [110, 'Cambio de Cantidad de Bins a Reponer'],
                120 => [110, 'Cambio de Conductor'],
                178 => [37, 'No Informado'],
                179 => [41, 'Mortalidad'],
            ];

            $parentIds = [34, 37, 41, 43, 53, 90, 110];
            $childIds = [
                35, 36,
                38, 39, 40,
                42,
                44, 45, 46, 47, 48, 49, 50, 51, 52,
                54, 55, 56,
                91, 92, 93, 94, 95, 96, 97,
                111, 112, 113, 114, 115, 116, 117, 118, 119, 120,
                178, 179,
            ];
            $candidateIds = array_keys($expectedCatalogs);
            $preservedIds = array_merge([0, 1], range(20, 33), [57], range(61, 69));

            if (count($candidateIds) !== 44 || count($childIds) !== 37 || count($parentIds) !== 7) {
                throw new \RuntimeException('Aborting cleanup: the migration candidate definition is internally inconsistent.');
            }

            $catalogs = DB::table('catalogos')
                ->whereIn('id', $candidateIds)
                ->get(['id', 'catalogo_id', 'nombre', 'activo', 'deleted_at'])
                ->keyBy('id');

            if ($catalogs->count() !== 44) {
                throw new \RuntimeException(
                    "Aborting cleanup: expected 44 logistics catalogs, found {$catalogs->count()}."
                );
            }

            foreach ($expectedCatalogs as $id => [$expectedParentId, $expectedName]) {
                $catalog = $catalogs->get($id);

                if ($catalog === null) {
                    throw new \RuntimeException("Aborting cleanup: expected catalog ID [{$id}] is missing.");
                }

                $actualParentId = $catalog->catalogo_id === null ? null : (int) $catalog->catalogo_id;

                if ($actualParentId !== $expectedParentId || $catalog->nombre !== $expectedName || (int) $catalog->activo !== 1 || $catalog->deleted_at !== null) {
                    throw new \RuntimeException(
                        "Aborting cleanup: catalog ID [{$id}] no longer matches its expected hierarchy, identity, or active state."
                    );
                }
            }

            if (DB::table('catalogos')->count() !== 70) {
                throw new \RuntimeException('Aborting cleanup: expected exactly 70 catalog records before deletion.');
            }

            $preservedCount = DB::table('catalogos')->whereIn('id', $preservedIds)->count();

            if ($preservedCount !== 26) {
                throw new \RuntimeException(
                    "Aborting cleanup: expected all 26 preserved catalogs, found {$preservedCount}."
                );
            }

            $relationType = DB::table('catalogos')
                ->where('id', 57)
                ->first(['catalogo_id', 'nombre', 'activo', 'deleted_at']);

            if (
                $relationType === null
                || (int) $relationType->catalogo_id !== 20
                || $relationType->nombre !== 'Agrupa'
                || (int) $relationType->activo !== 1
                || $relationType->deleted_at !== null
            ) {
                throw new \RuntimeException(
                    'Aborting cleanup: preserved relation type ID [57] no longer matches [20 → Agrupa].'
                );
            }

            $baseReferences = [
                'users.rol_id' => DB::table('users')->whereIn('rol_id', $candidateIds)->count(),
                'empresas.tipo_empresa_id' => DB::table('empresas')->whereIn('tipo_empresa_id', $candidateIds)->count(),
                'sucursales.zona_id' => DB::table('sucursales')->whereIn('zona_id', $candidateIds)->count(),
                'sucursales.tipo_sucursal_id' => DB::table('sucursales')->whereIn('tipo_sucursal_id', $candidateIds)->count(),
            ];

            foreach ($baseReferences as $reference => $count) {
                if ($count !== 0) {
                    throw new \RuntimeException(
                        "Aborting cleanup: [{$reference}] contains {$count} reference(s) to logistics catalogs."
                    );
                }
            }

            $expectedRelations = [
                '56:44:57',
                '56:45:57',
                '56:46:57',
                '56:47:57',
                '56:48:57',
                '55:49:57',
                '55:50:57',
                '55:51:57',
                '54:52:57',
            ];
            sort($expectedRelations);

            $relationRows = DB::table('catalogo_relaciones')
                ->where(function ($query) use ($candidateIds): void {
                    $query
                        ->whereIn('valor_origen_id', $candidateIds)
                        ->orWhereIn('valor_destino_id', $candidateIds)
                        ->orWhereIn('tipo_relacion_id', $candidateIds);
                })
                ->get(['valor_origen_id', 'valor_destino_id', 'tipo_relacion_id']);

            $actualRelations = $relationRows
                ->map(fn ($relation): string => implode(':', [
                    $relation->valor_origen_id,
                    $relation->valor_destino_id,
                    $relation->tipo_relacion_id,
                ]))
                ->all();
            sort($actualRelations);

            if (DB::table('catalogo_relaciones')->count() !== 9 || $actualRelations !== $expectedRelations) {
                throw new \RuntimeException(
                    'Aborting cleanup: catalog relations do not match the nine expected legacy logistics relations.'
                );
            }

            $baseCountsBefore = [
                'users' => DB::table('users')->count(),
                'empresas' => DB::table('empresas')->count(),
                'sucursales' => DB::table('sucursales')->count(),
            ];

            $deletedRelations = DB::table('catalogo_relaciones')
                ->whereIn('valor_origen_id', [54, 55, 56])
                ->whereIn('valor_destino_id', range(44, 52))
                ->where('tipo_relacion_id', 57)
                ->delete();

            if ($deletedRelations !== 9) {
                throw new \RuntimeException(
                    "Aborting cleanup: expected to delete 9 catalog relations, deleted {$deletedRelations}."
                );
            }

            $deletedChildren = DB::table('catalogos')->whereIn('id', $childIds)->delete();

            if ($deletedChildren !== 37) {
                throw new \RuntimeException(
                    "Aborting cleanup: expected to delete 37 child catalogs, deleted {$deletedChildren}."
                );
            }

            $deletedParents = DB::table('catalogos')->whereIn('id', $parentIds)->delete();

            if ($deletedParents !== 7) {
                throw new \RuntimeException(
                    "Aborting cleanup: expected to delete 7 parent catalogs, deleted {$deletedParents}."
                );
            }

            if (DB::table('catalogos')->count() !== 26) {
                throw new \RuntimeException('Cleanup postcondition failed: catalogos must contain exactly 26 records.');
            }

            if (DB::table('catalogos')->whereIn('id', $candidateIds)->exists()) {
                throw new \RuntimeException('Cleanup postcondition failed: at least one logistics catalog still exists.');
            }

            if (DB::table('catalogos')->whereIn('id', $preservedIds)->count() !== 26) {
                throw new \RuntimeException('Cleanup postcondition failed: at least one preserved catalog is missing.');
            }

            if (! DB::table('catalogos')->where('id', 57)->exists()) {
                throw new \RuntimeException('Cleanup postcondition failed: generic relation type ID [57] is missing.');
            }

            if (DB::table('catalogo_relaciones')->count() !== 0) {
                throw new \RuntimeException('Cleanup postcondition failed: catalogo_relaciones must be empty.');
            }

            foreach ($baseCountsBefore as $table => $countBefore) {
                $countAfter = DB::table($table)->count();

                if ($countAfter !== $countBefore) {
                    throw new \RuntimeException(
                        "Cleanup postcondition failed: [{$table}] changed from {$countBefore} to {$countAfter} records."
                    );
                }
            }
        });
    }

    /**
     * This destructive data cleanup is intentionally irreversible.
     */
    public function down(): void
    {
        throw new \LogicException(
            'This migration irreversibly removes legacy logistics catalog data and must not recreate it inside ApplicationBase.'
        );
    }
};
