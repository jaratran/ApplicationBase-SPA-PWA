<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['empresas', 'sucursales', 'maquilas'];

    public function up(): void
    {
        $database = DB::connection()->getDatabaseName();

        if ($database === 'db_laportada') {
            throw new RuntimeException('ApplicationBase baseline migrations must never run against db_laportada.');
        }

        $present = array_values(array_filter(self::TABLES, static fn (string $table): bool => Schema::hasTable($table)));

        if ($present === []) {
            $this->createTables();
            $this->assertCompatibleSchema();

            return;
        }

        if (count($present) !== count(self::TABLES)) {
            throw new RuntimeException(sprintf(
                'ApplicationBase organization schema is partial. Present: [%s]. Missing: [%s]. No changes were made.',
                implode(', ', $present),
                implode(', ', array_diff(self::TABLES, $present)),
            ));
        }

        if (! filter_var(env('APPLICATIONBASE_ADOPT_EXISTING_SCHEMA', false), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException('ApplicationBase organization tables already exist. Explicit schema adoption is required.');
        }

        $expectedDatabase = env('APPLICATIONBASE_ADOPT_DATABASE');

        if (! is_string($expectedDatabase) || $expectedDatabase === '') {
            throw new RuntimeException('APPLICATIONBASE_ADOPT_DATABASE must name the exact database to adopt.');
        }

        if ($expectedDatabase === 'db_laportada') {
            throw new RuntimeException('ApplicationBase explicitly refuses to adopt db_laportada.');
        }

        if ($database !== $expectedDatabase) {
            throw new RuntimeException("Schema adoption database mismatch. Connected to [$database], expected exactly [$expectedDatabase].");
        }

        $this->assertCompatibleSchema();
    }

    public function down(): void
    {
        throw new LogicException(
            'ApplicationBase baseline migrations are intentionally irreversible. '
            .'Use migrate:fresh only on an explicitly isolated disposable database.',
        );
    }

    private function createTables(): void
    {
        Schema::create('empresas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tipo_empresa_id');
            $table->string('rut_empresa', 20);
            $table->string('razon_social');
            $table->string('direccion');
            $table->unsignedBigInteger('comuna_id');
            $table->string('telefono')->nullable();
            $table->string('email_contacto')->nullable();
            $table->string('telefono_contacto')->nullable();
            $table->boolean('activo')->default(true);
            $table->text('observacion_inactividad')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('tipo_empresa_id')->references('id')->on('catalogos')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('comuna_id')->references('id')->on('comunas')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('sucursales', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('zona_id');
            $table->string('nombre_sucursal');
            $table->unsignedBigInteger('tipo_sucursal_id');
            $table->unsignedBigInteger('comuna_id');
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->boolean('activo')->default(true);
            $table->text('observacion_inactividad')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('zona_id')->references('id')->on('catalogos')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('tipo_sucursal_id')->references('id')->on('catalogos')->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('comuna_id')->references('id')->on('comunas')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('maquilas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id');
            $table->date('fecha_inicio')->nullable();
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'sucursal_id'], 'maquilas_empresa_sucursal_unique');
            $table->foreign('empresa_id', 'maquilas_empresa_fk')->references('id')->on('empresas')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('sucursal_id', 'maquilas_sucursal_fk')->references('id')->on('sucursales')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    private function assertCompatibleSchema(): void
    {
        $expected = [
            'empresas' => [
                'id' => ['bigint', false, 'none'], 'tipo_empresa_id' => ['bigint', false, 'none'],
                'rut_empresa' => ['varchar', false, 'none'], 'razon_social' => ['varchar', false, 'none'],
                'direccion' => ['varchar', false, 'none'], 'comuna_id' => ['bigint', false, 'none'],
                'telefono' => ['varchar', true, 'null'], 'email_contacto' => ['varchar', true, 'null'],
                'telefono_contacto' => ['varchar', true, 'null'], 'activo' => ['tinyint', false, 'one'],
                'observacion_inactividad' => ['text', true, 'null'], 'created_at' => ['timestamp', false, 'current'],
                'updated_at' => ['timestamp', false, 'current'],
            ],
            'sucursales' => [
                'id' => ['bigint', false, 'none'], 'zona_id' => ['bigint', false, 'none'],
                'nombre_sucursal' => ['varchar', false, 'none'], 'tipo_sucursal_id' => ['bigint', false, 'none'],
                'comuna_id' => ['bigint', false, 'none'], 'telefono' => ['varchar', true, 'null'],
                'email' => ['varchar', true, 'null'], 'activo' => ['tinyint', false, 'one'],
                'observacion_inactividad' => ['text', true, 'null'], 'created_at' => ['timestamp', false, 'current'],
                'updated_at' => ['timestamp', false, 'current'],
            ],
            'maquilas' => [
                'id' => ['bigint', false, 'none'], 'empresa_id' => ['bigint', false, 'none'],
                'sucursal_id' => ['bigint', false, 'none'], 'fecha_inicio' => ['date', true, 'null'],
                'activo' => ['tinyint', false, 'one'], 'observaciones' => ['text', true, 'null'],
                'created_at' => ['timestamp', true, 'null'], 'updated_at' => ['timestamp', true, 'null'],
            ],
        ];

        foreach ($expected as $table => $columns) {
            $actual = collect(Schema::getColumns($table));

            if ($actual->pluck('name')->all() !== array_keys($columns)) {
                throw new RuntimeException("ApplicationBase cannot adopt [$table]: its ordered column set is not exact.");
            }

            foreach ($columns as $name => [$type, $nullable, $default]) {
                $column = $actual->firstWhere('name', $name);

                if ($column['type_name'] !== $type || $column['nullable'] !== $nullable) {
                    throw new RuntimeException("ApplicationBase cannot adopt [$table]: column [$name] is incompatible.");
                }

                $matchesDefault = match ($default) {
                    'none' => $column['default'] === null,
                    'null' => $column['default'] === null || strtoupper((string) $column['default']) === 'NULL',
                    'one' => (string) $column['default'] === '1',
                    'current' => str_contains(strtolower((string) $column['default']), 'current_timestamp'),
                };

                if (! $matchesDefault) {
                    throw new RuntimeException("ApplicationBase cannot adopt [$table]: column [$name] has an incompatible default.");
                }
            }

            if (! $actual->firstWhere('name', 'id')['auto_increment']) {
                throw new RuntimeException("ApplicationBase cannot adopt [$table]: [id] must auto-increment.");
            }
        }

        $this->assertExactIndexes('empresas', [[['id'], true, true], [['tipo_empresa_id'], false, false], [['comuna_id'], false, false]]);
        $this->assertExactIndexes('sucursales', [[['id'], true, true], [['zona_id'], false, false], [['tipo_sucursal_id'], false, false], [['comuna_id'], false, false]]);
        $this->assertExactIndexes('maquilas', [[['id'], true, true], [['empresa_id', 'sucursal_id'], true, false], [['sucursal_id'], false, false]]);

        $this->assertExactForeignKeys('empresas', [
            [['tipo_empresa_id'], 'catalogos', ['id'], 'restrict', 'restrict'],
            [['comuna_id'], 'comunas', ['id'], 'cascade', 'restrict'],
        ]);
        $this->assertExactForeignKeys('sucursales', [
            [['zona_id'], 'catalogos', ['id'], 'restrict', 'restrict'],
            [['tipo_sucursal_id'], 'catalogos', ['id'], 'restrict', 'restrict'],
            [['comuna_id'], 'comunas', ['id'], 'cascade', 'restrict'],
        ]);
        $this->assertExactForeignKeys('maquilas', [
            [['empresa_id'], 'empresas', ['id'], 'cascade', 'restrict'],
            [['sucursal_id'], 'sucursales', ['id'], 'cascade', 'restrict'],
        ]);

        if (DB::connection()->getDriverName() === 'mysql') {
            $this->assertMySqlDetails();
        }
    }

    private function assertExactIndexes(string $table, array $expected): void
    {
        $actual = Schema::getIndexes($table);

        if (count($actual) !== count($expected)) {
            throw new RuntimeException("ApplicationBase cannot adopt [$table]: its index set is not exact.");
        }

        foreach ($expected as [$columns, $unique, $primary]) {
            if (! collect($actual)->contains(static fn (array $index): bool => $index['columns'] === $columns
                && $index['unique'] === $unique && $index['primary'] === $primary)) {
                throw new RuntimeException("ApplicationBase cannot adopt [$table]: a required index is missing or incompatible.");
            }
        }
    }

    private function assertExactForeignKeys(string $table, array $expected): void
    {
        $actual = Schema::getForeignKeys($table);

        if (count($actual) !== count($expected)) {
            throw new RuntimeException("ApplicationBase cannot adopt [$table]: its foreign key set is not exact.");
        }

        foreach ($expected as [$columns, $foreignTable, $foreignColumns, $onUpdate, $onDelete]) {
            if (! collect($actual)->contains(static fn (array $foreignKey): bool => $foreignKey['columns'] === $columns
                && $foreignKey['foreign_table'] === $foreignTable && $foreignKey['foreign_columns'] === $foreignColumns
                && $foreignKey['on_update'] === $onUpdate && $foreignKey['on_delete'] === $onDelete)) {
                throw new RuntimeException("ApplicationBase cannot adopt [$table]: a required foreign key is missing or incompatible.");
            }
        }
    }

    private function assertMySqlDetails(): void
    {
        foreach ([
            ['empresas', ['id', 'tipo_empresa_id', 'comuna_id']],
            ['sucursales', ['id', 'zona_id', 'tipo_sucursal_id', 'comuna_id']],
            ['maquilas', ['id', 'empresa_id', 'sucursal_id']],
        ] as [$table, $columns]) {
            $actual = collect(Schema::getColumns($table))->keyBy('name');

            foreach ($columns as $column) {
                if (! str_contains(strtolower($actual[$column]['type']), 'unsigned')) {
                    throw new RuntimeException("ApplicationBase cannot adopt [$table]: [$column] must be unsigned.");
                }
            }
        }

        foreach ([
            ['empresas', 'rut_empresa', 'varchar(20)'], ['empresas', 'razon_social', 'varchar(255)'],
            ['empresas', 'direccion', 'varchar(255)'], ['empresas', 'telefono', 'varchar(255)'],
            ['empresas', 'email_contacto', 'varchar(255)'], ['empresas', 'telefono_contacto', 'varchar(255)'],
            ['sucursales', 'nombre_sucursal', 'varchar(255)'], ['sucursales', 'telefono', 'varchar(255)'],
            ['sucursales', 'email', 'varchar(255)'],
        ] as [$table, $column, $type]) {
            if (strtolower(collect(Schema::getColumns($table))->firstWhere('name', $column)['type']) !== $type) {
                throw new RuntimeException("ApplicationBase cannot adopt [$table]: [$column] must be [$type].");
            }
        }

        $metadata = DB::table('information_schema.columns')
            ->where('table_schema', DB::connection()->getDatabaseName())
            ->whereIn('table_name', self::TABLES)
            ->get(['table_name', 'column_name', 'extra', 'column_comment'])
            ->keyBy(static fn (object $column): string => "{$column->table_name}.{$column->column_name}");

        foreach (['empresas.updated_at', 'sucursales.updated_at'] as $column) {
            if (! str_contains(strtolower($metadata[$column]->extra), 'on update current_timestamp')) {
                throw new RuntimeException("ApplicationBase cannot adopt organization schema: [$column] must update with CURRENT_TIMESTAMP.");
            }
        }

        foreach (['maquilas.created_at', 'maquilas.updated_at'] as $column) {
            if ($metadata[$column]->extra !== '') {
                throw new RuntimeException("ApplicationBase cannot adopt organization schema: [$column] has incompatible extra attributes.");
            }
        }

        if ($metadata->contains(static fn (object $column): bool => $column->column_comment !== '')) {
            throw new RuntimeException('ApplicationBase cannot adopt organization schema: column comments are not expected.');
        }

        $tables = DB::table('information_schema.tables')
            ->where('table_schema', DB::connection()->getDatabaseName())
            ->whereIn('table_name', self::TABLES)
            ->get(['engine', 'table_collation', 'table_comment']);

        if ($tables->count() !== count(self::TABLES)
            || $tables->contains(static fn (object $table): bool => strtolower($table->engine) !== 'innodb'
                || strtolower($table->table_collation) !== 'utf8mb4_unicode_ci'
                || $table->table_comment !== '')) {
            throw new RuntimeException('ApplicationBase cannot adopt organization schema: table attributes are incompatible.');
        }
    }
};
