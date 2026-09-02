<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $database = DB::connection()->getDatabaseName();

        if ($database !== 'db_spa_pwa') {
            throw new RuntimeException(
                'Aborting logistics schema cleanup: expected [db_spa_pwa], connected to ['.$database.'].'
            );
        }

        $candidates = [
            'operational_parameters' => [
                'daily_program_execution_time' => ['time', true, null, 'time'],
                'auto_emit_daily_program' => ['tinyint', false, '0', 'tinyint(1)'],
                'notify_admins_as_coordinators' => ['tinyint', false, '0', 'tinyint(1)'],
                'average_truck_speed' => ['int', true, null, 'int(11)'],
            ],
            'sucursales' => [
                'codigo_siep' => ['int', true, null, 'int(11)'],
                'km' => ['int', true, null, 'int(11)'],
                'tiempo_estimado_viaje' => ['decimal', true, null, 'decimal(5,2)'],
            ],
        ];

        foreach ($candidates as $table => $columns) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException('Aborting cleanup: table ['.$table.'] does not exist.');
            }

            foreach ($columns as $column => [$type, $nullable, $default, $columnType]) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new RuntimeException('Aborting cleanup: column ['.$table.'.'.$column.'] does not exist.');
                }

                $actual = DB::table('information_schema.COLUMNS')
                    ->where('TABLE_SCHEMA', $database)
                    ->where('TABLE_NAME', $table)
                    ->where('COLUMN_NAME', $column)
                    ->first(['DATA_TYPE', 'COLUMN_TYPE', 'IS_NULLABLE', 'COLUMN_DEFAULT']);
                $rawDefault = $actual?->COLUMN_DEFAULT;
                $actualDefault = $rawDefault === null || strtoupper((string) $rawDefault) === 'NULL'
                    ? null
                    : (string) $rawDefault;

                if (
                    $actual === null
                    || $actual->DATA_TYPE !== $type
                    || $actual->COLUMN_TYPE !== $columnType
                    || ($actual->IS_NULLABLE === 'YES') !== $nullable
                    || $actualDefault !== $default
                ) {
                    throw new RuntimeException('Aborting cleanup: ['.$table.'.'.$column.'] has an unexpected definition.');
                }
            }

            $columnNames = array_keys($columns);
            $indexCount = DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', $table)
                ->whereIn('COLUMN_NAME', $columnNames)
                ->count();
            $foreignKeyCount = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', $table)
                ->whereIn('COLUMN_NAME', $columnNames)
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->count();

            if ($indexCount !== 0 || $foreignKeyCount !== 0) {
                throw new RuntimeException(
                    'Aborting cleanup: candidate columns in ['.$table.'] participate in indexes or foreign keys.'
                );
            }
        }

        $assertSucursalForeignKeys = function (string $phase) use ($database): void {
            $actual = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', 'sucursales')
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->get(['COLUMN_NAME', 'REFERENCED_TABLE_NAME', 'REFERENCED_COLUMN_NAME'])
                ->map(fn ($key): string => implode(':', [
                    $key->COLUMN_NAME,
                    $key->REFERENCED_TABLE_NAME,
                    $key->REFERENCED_COLUMN_NAME,
                ]))
                ->sort()
                ->values()
                ->all();
            $expected = [
                'comuna_id:comunas:id',
                'tipo_sucursal_id:catalogos:id',
                'zona_id:catalogos:id',
            ];
            sort($expected);

            if ($actual !== $expected) {
                throw new RuntimeException($phase.': sucursales foreign keys do not match the expected Base structure.');
            }
        };

        $assertSucursalForeignKeys('Aborting cleanup');

        $rowCounts = [
            'operational_parameters' => DB::table('operational_parameters')->count(),
            'sucursales' => DB::table('sucursales')->count(),
        ];

        Schema::table('operational_parameters', function ($table): void {
            $table->dropColumn([
                'daily_program_execution_time',
                'auto_emit_daily_program',
                'notify_admins_as_coordinators',
                'average_truck_speed',
            ]);
        });

        Schema::table('sucursales', function ($table): void {
            $table->dropColumn(['codigo_siep', 'km', 'tiempo_estimado_viaje']);
        });

        $required = [
            'operational_parameters' => [
                'id', 'support_email', 'support_telefono', 'audit_email',
                'audit_email_enabled', 'verification_expiration_time',
                'allow_profile_editing', 'created_at', 'updated_at',
            ],
            'sucursales' => [
                'id', 'zona_id', 'nombre_sucursal', 'tipo_sucursal_id',
                'comuna_id', 'telefono', 'email', 'activo',
                'observacion_inactividad', 'created_at', 'updated_at',
            ],
        ];

        foreach ($candidates as $table => $columns) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException('Postcondition failed: ['.$table.'] is missing; DDL may be partially applied.');
            }

            foreach (array_keys($columns) as $column) {
                if (Schema::hasColumn($table, $column)) {
                    throw new RuntimeException(
                        'Postcondition failed: ['.$table.'.'.$column.'] remains; DDL may be partially applied.'
                    );
                }
            }

            foreach ($required[$table] as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new RuntimeException(
                        'Postcondition failed: Base column ['.$table.'.'.$column.'] is missing; DDL may be partially applied.'
                    );
                }
            }

            $after = DB::table($table)->count();

            if ($after !== $rowCounts[$table]) {
                throw new RuntimeException(
                    'Postcondition failed: ['.$table.'] row count changed; DDL may be partially applied.'
                );
            }
        }

        $assertSucursalForeignKeys('Postcondition failed; DDL may be partially applied');
    }

    public function down(): void
    {
        throw new \LogicException(
            'The columns could be recreated structurally, but their deleted data cannot be restored; ApplicationBase must not simulate an incomplete rollback.'
        );
    }
};
