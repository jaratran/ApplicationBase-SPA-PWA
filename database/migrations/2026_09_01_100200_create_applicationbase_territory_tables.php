<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['regiones', 'comunas'];

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
                'ApplicationBase territory schema is partial. Present: [%s]. Missing: [%s]. No changes were made.',
                implode(', ', $present),
                implode(', ', array_diff(self::TABLES, $present)),
            ));
        }

        if (! filter_var(env('APPLICATIONBASE_ADOPT_EXISTING_SCHEMA', false), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException('ApplicationBase territory tables already exist. Explicit schema adoption is required.');
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
        Schema::create('regiones', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->bigInteger('orden');
            $table->timestamps();
        });

        Schema::create('comunas', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->unsignedBigInteger('region_id');
            $table->timestamps();

            $table->foreign('region_id')
                ->references('id')
                ->on('regiones')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    private function assertCompatibleSchema(): void
    {
        $expected = [
            'regiones' => [
                'id' => ['bigint', false],
                'nombre' => ['varchar', false],
                'orden' => ['bigint', false],
                'created_at' => ['timestamp', true],
                'updated_at' => ['timestamp', true],
            ],
            'comunas' => [
                'id' => ['bigint', false],
                'nombre' => ['varchar', false],
                'region_id' => ['bigint', false],
                'created_at' => ['timestamp', true],
                'updated_at' => ['timestamp', true],
            ],
        ];

        foreach ($expected as $table => $columns) {
            $actualColumns = collect(Schema::getColumns($table))->keyBy('name');

            foreach ($columns as $column => [$type, $nullable]) {
                $actual = $actualColumns->get($column);

                if ($actual === null || $actual['type_name'] !== $type || $actual['nullable'] !== $nullable) {
                    throw new RuntimeException("ApplicationBase cannot adopt [$table]: column [$column] is missing or incompatible.");
                }

                $isNullableTimestampDefault = $type === 'timestamp'
                    && $nullable
                    && strtoupper((string) $actual['default']) === 'NULL';

                if ($actual['default'] !== null && ! $isNullableTimestampDefault) {
                    throw new RuntimeException("ApplicationBase cannot adopt [$table]: column [$column] has an incompatible default.");
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

        $this->assertExactIndexes('regiones', [
            [['id'], true, true],
        ]);
        $this->assertExactIndexes('comunas', [
            [['id'], true, true],
            [['region_id'], false, false],
        ]);

        $this->assertExactForeignKeys('regiones', []);
        $this->assertExactForeignKeys('comunas', [
            [['region_id'], 'regiones', ['id'], 'cascade', 'restrict'],
        ]);

        if (DB::connection()->getDriverName() === 'mysql') {
            $this->assertMySqlSignedness();
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

    private function assertMySqlSignedness(): void
    {
        foreach ([['regiones', 'id'], ['comunas', 'id'], ['comunas', 'region_id']] as [$table, $column]) {
            $actual = collect(Schema::getColumns($table))->firstWhere('name', $column);

            if (! str_contains(strtolower($actual['type']), 'unsigned')) {
                throw new RuntimeException("ApplicationBase cannot adopt [$table]: [$column] must be unsigned.");
            }
        }

        $orden = collect(Schema::getColumns('regiones'))->firstWhere('name', 'orden');

        if (str_contains(strtolower($orden['type']), 'unsigned')) {
            throw new RuntimeException('ApplicationBase cannot adopt [regiones]: [orden] must be signed.');
        }
    }
};
