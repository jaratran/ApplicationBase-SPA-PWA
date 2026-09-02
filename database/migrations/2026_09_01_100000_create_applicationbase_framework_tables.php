<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['cache', 'cache_locks', 'jobs', 'failed_jobs', 'job_batches', 'sessions', 'password_resets'];

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
                'ApplicationBase framework/auth schema is partial. Present: [%s]. Missing: [%s]. No changes were made.',
                implode(', ', $present),
                implode(', ', array_diff(self::TABLES, $present)),
            ));
        }

        if (! filter_var(env('APPLICATIONBASE_ADOPT_EXISTING_SCHEMA', false), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException('ApplicationBase framework/auth tables already exist. Explicit schema adoption is required.');
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
        Schema::create('cache', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        Schema::create('jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('failed_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        Schema::create('job_batches', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('password_resets', function (Blueprint $table): void {
            $table->string('email');
            $table->string('token');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    private function assertCompatibleSchema(): void
    {
        $expected = [
            'cache' => ['key' => ['varchar', false], 'value' => ['mediumtext', false], 'expiration' => ['int', false]],
            'cache_locks' => ['key' => ['varchar', false], 'owner' => ['varchar', false], 'expiration' => ['int', false]],
            'jobs' => ['id' => ['bigint', false], 'queue' => ['varchar', false], 'payload' => ['longtext', false], 'attempts' => ['tinyint', false], 'reserved_at' => ['int', true], 'available_at' => ['int', false], 'created_at' => ['int', false]],
            'failed_jobs' => ['id' => ['bigint', false], 'uuid' => ['varchar', false], 'connection' => ['text', false], 'queue' => ['text', false], 'payload' => ['longtext', false], 'exception' => ['longtext', false], 'failed_at' => ['timestamp', false]],
            'job_batches' => ['id' => ['varchar', false], 'name' => ['varchar', false], 'total_jobs' => ['int', false], 'pending_jobs' => ['int', false], 'failed_jobs' => ['int', false], 'failed_job_ids' => ['longtext', false], 'options' => ['mediumtext', true], 'cancelled_at' => ['int', true], 'created_at' => ['int', false], 'finished_at' => ['int', true]],
            'sessions' => ['id' => ['varchar', false], 'user_id' => ['bigint', true], 'ip_address' => ['varchar', true], 'user_agent' => ['text', true], 'payload' => ['longtext', false], 'last_activity' => ['int', false]],
            'password_resets' => ['email' => ['varchar', false], 'token' => ['varchar', false], 'created_at' => ['timestamp', false]],
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

        $this->assertIndex('cache', ['key'], primary: true);
        $this->assertIndex('cache_locks', ['key'], primary: true);
        $this->assertIndex('jobs', ['id'], primary: true);
        $this->assertIndex('jobs', ['queue']);
        $this->assertIndex('failed_jobs', ['id'], primary: true);
        $this->assertIndex('failed_jobs', ['uuid'], unique: true);
        $this->assertIndex('job_batches', ['id'], primary: true);
        $this->assertIndex('sessions', ['id'], primary: true);
        $this->assertIndex('sessions', ['user_id']);
        $this->assertIndex('sessions', ['last_activity']);

        if (Schema::getIndexes('password_resets') !== []) {
            throw new RuntimeException('ApplicationBase cannot adopt [password_resets]: no indexes are expected.');
        }

        if (Schema::getForeignKeys('sessions') !== []) {
            throw new RuntimeException('ApplicationBase cannot adopt [sessions]: user_id must not have a foreign key.');
        }

        foreach ([['jobs', 'id'], ['failed_jobs', 'id']] as [$table, $column]) {
            $actual = collect(Schema::getColumns($table))->firstWhere('name', $column);

            if (! $actual['auto_increment']) {
                throw new RuntimeException("ApplicationBase cannot adopt [$table]: [$column] must auto-increment.");
            }
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            foreach ([
                ['jobs', 'id'],
                ['jobs', 'attempts'],
                ['jobs', 'reserved_at'],
                ['jobs', 'available_at'],
                ['jobs', 'created_at'],
                ['failed_jobs', 'id'],
                ['sessions', 'user_id'],
            ] as [$table, $column]) {
                $actual = collect(Schema::getColumns($table))->firstWhere('name', $column);

                if (! str_contains(strtolower($actual['type']), 'unsigned')) {
                    throw new RuntimeException("ApplicationBase cannot adopt [$table]: [$column] must be unsigned.");
                }
            }
        }

        foreach ([['failed_jobs', 'failed_at'], ['password_resets', 'created_at']] as [$table, $column]) {
            $actual = collect(Schema::getColumns($table))->firstWhere('name', $column);

            if ($actual['default'] === null) {
                throw new RuntimeException("ApplicationBase cannot adopt [$table]: [$column] must have a current timestamp default.");
            }
        }
    }

    private function assertIndex(string $table, array $columns, bool $unique = false, bool $primary = false): void
    {
        $matches = collect(Schema::getIndexes($table))->contains(
            static fn (array $index): bool => $index['columns'] === $columns
                && $index['primary'] === $primary
                && ($primary || $index['unique'] === $unique),
        );

        if (! $matches) {
            $kind = $primary ? 'primary' : ($unique ? 'unique' : 'non-unique');
            throw new RuntimeException("ApplicationBase cannot adopt [$table]: required $kind index is missing.");
        }
    }
};
