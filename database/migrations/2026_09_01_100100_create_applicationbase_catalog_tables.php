<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['catalogos', 'catalogo_relaciones'];

    public function up(): void
    {
        $database = DB::connection()->getDatabaseName();

        if ($database === 'db_laportada') {
            throw new RuntimeException('ApplicationBase baseline migrations must never run against db_laportada.');
        }

        $present = array_values(array_filter(
            self::TABLES,
            static fn (string $table): bool => Schema::hasTable($table),
        ));

        if ($present === []) {
            $this->createTables();
            $this->assertCompatibleSchema();

            return;
        }

        if (count($present) !== count(self::TABLES)) {
            throw new RuntimeException(sprintf(
                'ApplicationBase catalog schema is partial. Present: [%s]. Missing: [%s]. No changes were made.',
                implode(', ', $present),
                implode(', ', array_diff(self::TABLES, $present)),
            ));
        }

        if (! filter_var(env('APPLICATIONBASE_ADOPT_EXISTING_SCHEMA', false), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException('ApplicationBase catalog tables already exist. Explicit schema adoption is required.');
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
        Schema::create('catalogos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('catalogo_id')->nullable()->comment('Referencia al catálogo padre');
            $table->string('nombre');
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->foreign('catalogo_id')
                ->references('id')
                ->on('catalogos')
                ->restrictOnUpdate()
                ->cascadeOnDelete();
        });

        Schema::create('catalogo_relaciones', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('valor_origen_id');
            $table->unsignedBigInteger('valor_destino_id');
            $table->unsignedBigInteger('tipo_relacion_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->foreign('valor_origen_id')
                ->references('id')
                ->on('catalogos')
                ->restrictOnUpdate()
                ->cascadeOnDelete();
            $table->foreign('valor_destino_id')
                ->references('id')
                ->on('catalogos')
                ->restrictOnUpdate()
                ->cascadeOnDelete();
            $table->foreign('tipo_relacion_id')
                ->references('id')
                ->on('catalogos')
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });
    }

    private function assertCompatibleSchema(): void
    {
        $expected = [
            'catalogos' => [
                'id' => ['bigint', false],
                'catalogo_id' => ['bigint', true],
                'nombre' => ['varchar', false],
                'orden' => ['int', false],
                'activo' => ['tinyint', false],
                'created_at' => ['timestamp', false],
                'updated_at' => ['timestamp', false],
                'deleted_at' => ['timestamp', true],
            ],
            'catalogo_relaciones' => [
                'id' => ['bigint', false],
                'valor_origen_id' => ['bigint', false],
                'valor_destino_id' => ['bigint', false],
                'tipo_relacion_id' => ['bigint', false],
                'created_at' => ['timestamp', false],
                'updated_at' => ['timestamp', false],
                'deleted_at' => ['timestamp', true],
            ],
        ];

        foreach ($expected as $table => $columns) {
            $actualColumns = collect(Schema::getColumns($table))->keyBy('name');

            foreach ($columns as $column => [$type, $nullable]) {
                $actual = $actualColumns->get($column);

                if ($actual === null || $actual['type_name'] !== $type || $actual['nullable'] !== $nullable) {
                    throw new RuntimeException("ApplicationBase cannot adopt [$table]: column [$column] is missing or incompatible.");
                }
            }

            if ($actualColumns->keys()->sort()->values()->all() !== collect(array_keys($columns))->sort()->values()->all()) {
                throw new RuntimeException("ApplicationBase cannot adopt [$table]: its column set is not exact.");
            }
        }

        foreach (self::TABLES as $table) {
            $id = collect(Schema::getColumns($table))->firstWhere('name', 'id');

            if (! $id['auto_increment']) {
                throw new RuntimeException("ApplicationBase cannot adopt [$table]: [id] must auto-increment.");
            }
        }

        $catalogColumns = collect(Schema::getColumns('catalogos'))->keyBy('name');

        if ($catalogColumns['catalogo_id']['comment'] !== 'Referencia al catálogo padre') {
            throw new RuntimeException('ApplicationBase cannot adopt [catalogos]: [catalogo_id] comment is incompatible.');
        }

        if ((string) $catalogColumns['orden']['default'] !== '0' || (string) $catalogColumns['activo']['default'] !== '1') {
            throw new RuntimeException('ApplicationBase cannot adopt [catalogos]: orden/activo defaults are incompatible.');
        }

        foreach (self::TABLES as $table) {
            $columns = collect(Schema::getColumns($table))->keyBy('name');

            foreach (['created_at', 'updated_at'] as $column) {
                if (! str_contains(strtolower((string) $columns[$column]['default']), 'current_timestamp')) {
                    throw new RuntimeException("ApplicationBase cannot adopt [$table]: [$column] must default to CURRENT_TIMESTAMP.");
                }
            }
        }

        $this->assertExactIndexes('catalogos', [
            [['id'], true, true],
            [['catalogo_id'], false, false],
        ]);
        $this->assertExactIndexes('catalogo_relaciones', [
            [['id'], true, true],
            [['valor_origen_id'], false, false],
            [['valor_destino_id'], false, false],
            [['tipo_relacion_id'], false, false],
        ]);

        $this->assertExactForeignKeys('catalogos', [
            [['catalogo_id'], 'catalogos', ['id'], 'restrict', 'cascade'],
        ]);
        $this->assertExactForeignKeys('catalogo_relaciones', [
            [['valor_origen_id'], 'catalogos', ['id'], 'restrict', 'cascade'],
            [['valor_destino_id'], 'catalogos', ['id'], 'restrict', 'cascade'],
            [['tipo_relacion_id'], 'catalogos', ['id'], 'restrict', 'restrict'],
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
            $matches = collect($actual)->contains(
                static fn (array $index): bool => $index['columns'] === $columns
                    && $index['unique'] === $unique
                    && $index['primary'] === $primary,
            );

            if (! $matches) {
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
            $matches = collect($actual)->contains(
                static fn (array $foreignKey): bool => $foreignKey['columns'] === $columns
                    && $foreignKey['foreign_table'] === $foreignTable
                    && $foreignKey['foreign_columns'] === $foreignColumns
                    && $foreignKey['on_update'] === $onUpdate
                    && $foreignKey['on_delete'] === $onDelete,
            );

            if (! $matches) {
                throw new RuntimeException("ApplicationBase cannot adopt [$table]: a required foreign key is missing or incompatible.");
            }
        }
    }

    private function assertMySqlDetails(): void
    {
        foreach ([
            ['catalogos', 'id'],
            ['catalogos', 'catalogo_id'],
            ['catalogo_relaciones', 'id'],
            ['catalogo_relaciones', 'valor_origen_id'],
            ['catalogo_relaciones', 'valor_destino_id'],
            ['catalogo_relaciones', 'tipo_relacion_id'],
        ] as [$table, $column]) {
            $actual = collect(Schema::getColumns($table))->firstWhere('name', $column);

            if (! str_contains(strtolower($actual['type']), 'unsigned')) {
                throw new RuntimeException("ApplicationBase cannot adopt [$table]: [$column] must be unsigned.");
            }
        }

        foreach (self::TABLES as $table) {
            $metadata = DB::selectOne(
                'select extra from information_schema.columns where table_schema = ? and table_name = ? and column_name = ?',
                [DB::connection()->getDatabaseName(), $table, 'updated_at'],
            );

            if ($metadata === null || ! str_contains(strtolower($metadata->extra), 'on update current_timestamp')) {
                throw new RuntimeException("ApplicationBase cannot adopt [$table]: [updated_at] must update with CURRENT_TIMESTAMP.");
            }
        }
    }
};
