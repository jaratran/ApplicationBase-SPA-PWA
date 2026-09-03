<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'users';

    public function up(): void
    {
        $database = DB::connection()->getDatabaseName();

        if ($database === 'db_laportada') {
            throw new RuntimeException('ApplicationBase baseline migrations must never run against db_laportada.');
        }

        if (! Schema::hasTable(self::TABLE)) {
            $this->createTable();
            $this->assertCompatibleSchema();

            return;
        }

        if (! filter_var(env('APPLICATIONBASE_ADOPT_EXISTING_SCHEMA', false), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException('ApplicationBase users table already exists. Explicit schema adoption is required.');
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

    private function createTable(): void
    {
        Schema::create(self::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->string('rut_usuario');
            $table->string('nombre_usuario');
            $table->string('apellidos_usuario');
            $table->unsignedBigInteger('rol_id')->nullable();
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->unsignedBigInteger('sucursal_id')->nullable();
            $table->text('telefono')->nullable();
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('avatar')->nullable();
            $table->unsignedBigInteger('comuna_id');
            $table->string('direccion')->nullable();
            $table->unsignedBigInteger('es_admin')->default(0);
            $table->boolean('activo')->default(true);
            $table->text('observacion_inactividad')->nullable();
            $table->string('fecha_login')->nullable();
            $table->string('remember_token')->nullable();
            $table->string('password')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('rol_id')->references('id')->on('catalogos')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('empresa_id')->references('id')->on('empresas')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('comuna_id')->references('id')->on('comunas')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    private function assertCompatibleSchema(): void
    {
        $expected = [
            'id' => ['bigint', false, 'none'],
            'rut_usuario' => ['varchar', false, 'none'],
            'nombre_usuario' => ['varchar', false, 'none'],
            'apellidos_usuario' => ['varchar', false, 'none'],
            'rol_id' => ['bigint', true, 'null'],
            'empresa_id' => ['bigint', true, 'null'],
            'sucursal_id' => ['bigint', true, 'null'],
            'telefono' => ['text', true, 'null'],
            'email' => ['varchar', false, 'none'],
            'email_verified_at' => ['timestamp', true, 'null'],
            'avatar' => ['varchar', true, 'null'],
            'comuna_id' => ['bigint', false, 'none'],
            'direccion' => ['varchar', true, 'null'],
            'es_admin' => ['bigint', false, 'zero'],
            'activo' => ['tinyint', false, 'one'],
            'observacion_inactividad' => ['text', true, 'null'],
            'fecha_login' => ['varchar', true, 'null'],
            'remember_token' => ['varchar', true, 'null'],
            'password' => ['varchar', true, 'null'],
            'created_at' => ['timestamp', false, 'current'],
            'updated_at' => ['timestamp', false, 'current'],
        ];
        $actual = collect(Schema::getColumns(self::TABLE));

        if ($actual->pluck('name')->all() !== array_keys($expected)) {
            throw new RuntimeException('ApplicationBase cannot adopt [users]: its ordered column set is not exact.');
        }

        foreach ($expected as $name => [$type, $nullable, $default]) {
            $column = $actual->firstWhere('name', $name);

            if ($column['type_name'] !== $type || $column['nullable'] !== $nullable) {
                throw new RuntimeException("ApplicationBase cannot adopt [users]: column [$name] is incompatible.");
            }

            $matchesDefault = match ($default) {
                'none' => $column['default'] === null,
                'null' => $column['default'] === null || strtoupper((string) $column['default']) === 'NULL',
                'zero' => (string) $column['default'] === '0',
                'one' => (string) $column['default'] === '1',
                'current' => str_contains(strtolower((string) $column['default']), 'current_timestamp'),
            };

            if (! $matchesDefault) {
                throw new RuntimeException("ApplicationBase cannot adopt [users]: column [$name] has an incompatible default.");
            }
        }

        if (! $actual->firstWhere('name', 'id')['auto_increment']) {
            throw new RuntimeException('ApplicationBase cannot adopt [users]: [id] must auto-increment.');
        }

        $this->assertExactIndexes([
            [['id'], true, true],
            [['rol_id'], false, false],
            [['empresa_id'], false, false],
            [['sucursal_id'], false, false],
            [['comuna_id'], false, false],
        ]);
        $this->assertExactForeignKeys([
            [['rol_id'], 'catalogos', ['id'], 'cascade', 'restrict'],
            [['empresa_id'], 'empresas', ['id'], 'cascade', 'restrict'],
            [['sucursal_id'], 'sucursales', ['id'], 'cascade', 'restrict'],
            [['comuna_id'], 'comunas', ['id'], 'cascade', 'restrict'],
        ]);

        if (DB::connection()->getDriverName() === 'mysql') {
            $this->assertMySqlDetails();
        }
    }

    private function assertExactIndexes(array $expected): void
    {
        $actual = Schema::getIndexes(self::TABLE);

        if (count($actual) !== count($expected)) {
            throw new RuntimeException('ApplicationBase cannot adopt [users]: its index set is not exact.');
        }

        foreach ($expected as [$columns, $unique, $primary]) {
            if (! collect($actual)->contains(static fn (array $index): bool => $index['columns'] === $columns
                && $index['unique'] === $unique && $index['primary'] === $primary)) {
                throw new RuntimeException('ApplicationBase cannot adopt [users]: a required index is missing or incompatible.');
            }
        }
    }

    private function assertExactForeignKeys(array $expected): void
    {
        $actual = Schema::getForeignKeys(self::TABLE);

        if (count($actual) !== count($expected)) {
            throw new RuntimeException('ApplicationBase cannot adopt [users]: its foreign key set is not exact.');
        }

        foreach ($expected as [$columns, $foreignTable, $foreignColumns, $onUpdate, $onDelete]) {
            if (! collect($actual)->contains(static fn (array $foreignKey): bool => $foreignKey['columns'] === $columns
                && $foreignKey['foreign_table'] === $foreignTable && $foreignKey['foreign_columns'] === $foreignColumns
                && $foreignKey['on_update'] === $onUpdate && $foreignKey['on_delete'] === $onDelete)) {
                throw new RuntimeException('ApplicationBase cannot adopt [users]: a required foreign key is missing or incompatible.');
            }
        }
    }

    private function assertMySqlDetails(): void
    {
        $columns = collect(Schema::getColumns(self::TABLE))->keyBy('name');

        foreach (['id', 'rol_id', 'empresa_id', 'sucursal_id', 'comuna_id', 'es_admin'] as $column) {
            if (! str_contains(strtolower($columns[$column]['type']), 'unsigned')) {
                throw new RuntimeException("ApplicationBase cannot adopt [users]: [$column] must be unsigned.");
            }
        }

        foreach (['rut_usuario', 'nombre_usuario', 'apellidos_usuario', 'email', 'avatar', 'direccion', 'fecha_login', 'remember_token', 'password'] as $column) {
            if (strtolower($columns[$column]['type']) !== 'varchar(255)') {
                throw new RuntimeException("ApplicationBase cannot adopt [users]: [$column] must be varchar(255).");
            }
        }

        $metadata = DB::table('information_schema.columns')
            ->where('table_schema', DB::connection()->getDatabaseName())
            ->where('table_name', self::TABLE)
            ->get(['column_name', 'extra', 'column_comment'])
            ->keyBy('column_name');

        if (! str_contains(strtolower($metadata['updated_at']->extra), 'on update current_timestamp')) {
            throw new RuntimeException('ApplicationBase cannot adopt [users]: [updated_at] must update with CURRENT_TIMESTAMP.');
        }

        if ($metadata->contains(static fn (object $column): bool => $column->column_comment !== '')) {
            throw new RuntimeException('ApplicationBase cannot adopt [users]: column comments are not expected.');
        }

        $table = DB::table('information_schema.tables')
            ->where('table_schema', DB::connection()->getDatabaseName())
            ->where('table_name', self::TABLE)
            ->first(['engine', 'table_collation', 'table_comment']);

        if ($table === null || strtolower($table->engine) !== 'innodb'
            || strtolower($table->table_collation) !== 'utf8mb4_unicode_ci' || $table->table_comment !== '') {
            throw new RuntimeException('ApplicationBase cannot adopt [users]: table attributes are incompatible.');
        }
    }
};
