<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['design_parameters', 'operational_parameters'];

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
                'ApplicationBase parameter schema is partial. Present: [%s]. Missing: [%s]. No changes were made.',
                implode(', ', $present),
                implode(', ', array_diff(self::TABLES, $present)),
            ));
        }

        if (! filter_var(env('APPLICATIONBASE_ADOPT_EXISTING_SCHEMA', false), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException('ApplicationBase parameter tables already exist. Explicit schema adoption is required.');
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
        Schema::create('design_parameters', function (Blueprint $table): void {
            $table->id();
            $table->string('titulo_design')->nullable();
            $table->string('logo_design');
            $table->string('emblema_design');
            $table->string('favicon_design');
            $table->string('fondo_pantalla_design');
            $table->string('custom_primary');
            $table->string('custom_secondary');
            $table->string('custom_success');
            $table->string('custom_warning');
            $table->string('custom_danger');
            $table->string('custom_info');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('operational_parameters', function (Blueprint $table): void {
            $table->id();
            $table->string('support_email')->nullable();
            $table->string('support_telefono')->nullable();
            $table->string('audit_email')->nullable();
            $table->boolean('audit_email_enabled')->default(false);
            $table->integer('verification_expiration_time')->nullable();
            $table->boolean('allow_profile_editing')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    private function assertCompatibleSchema(): void
    {
        $expected = [
            'design_parameters' => [
                'id' => ['bigint', false],
                'titulo_design' => ['varchar', true],
                'logo_design' => ['varchar', false],
                'emblema_design' => ['varchar', false],
                'favicon_design' => ['varchar', false],
                'fondo_pantalla_design' => ['varchar', false],
                'custom_primary' => ['varchar', false],
                'custom_secondary' => ['varchar', false],
                'custom_success' => ['varchar', false],
                'custom_warning' => ['varchar', false],
                'custom_danger' => ['varchar', false],
                'custom_info' => ['varchar', false],
                'created_at' => ['timestamp', false],
                'updated_at' => ['timestamp', false],
            ],
            'operational_parameters' => [
                'id' => ['bigint', false],
                'support_email' => ['varchar', true],
                'support_telefono' => ['varchar', true],
                'audit_email' => ['varchar', true],
                'audit_email_enabled' => ['tinyint', false],
                'verification_expiration_time' => ['int', true],
                'allow_profile_editing' => ['tinyint', false],
                'created_at' => ['timestamp', false],
                'updated_at' => ['timestamp', false],
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

            $indexes = Schema::getIndexes($table);

            if (count($indexes) !== 1 || ! $indexes[0]['primary'] || $indexes[0]['columns'] !== ['id']) {
                throw new RuntimeException("ApplicationBase cannot adopt [$table]: only the primary index on [id] is expected.");
            }

            if (Schema::getForeignKeys($table) !== []) {
                throw new RuntimeException("ApplicationBase cannot adopt [$table]: no foreign keys are expected.");
            }

            if (! $actualColumns['id']['auto_increment']) {
                throw new RuntimeException("ApplicationBase cannot adopt [$table]: [id] must auto-increment.");
            }

            foreach (['created_at', 'updated_at'] as $column) {
                if (! str_contains(strtolower((string) $actualColumns[$column]['default']), 'current_timestamp')) {
                    throw new RuntimeException("ApplicationBase cannot adopt [$table]: [$column] must default to CURRENT_TIMESTAMP.");
                }
            }
        }

        $operationalColumns = collect(Schema::getColumns('operational_parameters'))->keyBy('name');

        if ((string) $operationalColumns['audit_email_enabled']['default'] !== '0'
            || (string) $operationalColumns['allow_profile_editing']['default'] !== '1') {
            throw new RuntimeException('ApplicationBase cannot adopt [operational_parameters]: boolean defaults are incompatible.');
        }

        foreach (['support_email', 'support_telefono', 'audit_email', 'verification_expiration_time'] as $column) {
            if ($operationalColumns[$column]['default'] !== null
                && strtoupper((string) $operationalColumns[$column]['default']) !== 'NULL') {
                throw new RuntimeException("ApplicationBase cannot adopt [operational_parameters]: [$column] must not have a default.");
            }
        }

        $tituloDefault = collect(Schema::getColumns('design_parameters'))->keyBy('name')['titulo_design']['default'];

        if ($tituloDefault !== null && strtoupper((string) $tituloDefault) !== 'NULL') {
            throw new RuntimeException('ApplicationBase cannot adopt [design_parameters]: [titulo_design] must not have a default.');
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            $this->assertMySqlDetails();
        }
    }

    private function assertMySqlDetails(): void
    {
        foreach (self::TABLES as $table) {
            $columns = collect(Schema::getColumns($table))->keyBy('name');

            if (! str_contains(strtolower($columns['id']['type']), 'unsigned')) {
                throw new RuntimeException("ApplicationBase cannot adopt [$table]: [id] must be unsigned.");
            }

            foreach ($columns as $column) {
                if ($column['type_name'] === 'varchar' && strtolower($column['type']) !== 'varchar(255)') {
                    throw new RuntimeException("ApplicationBase cannot adopt [$table]: [{$column['name']}] must be varchar(255).");
                }
            }

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
