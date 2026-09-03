<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class InstanceParameterSnapshotSeeder extends Seeder
{
    private const TABLES = ['design_parameters', 'operational_parameters'];

    private const COLUMNS = [
        'design_parameters' => [
            'id', 'titulo_design', 'logo_design', 'emblema_design', 'favicon_design',
            'fondo_pantalla_design', 'custom_primary', 'custom_secondary', 'custom_success',
            'custom_warning', 'custom_danger', 'custom_info', 'created_at', 'updated_at',
        ],
        'operational_parameters' => [
            'id', 'support_email', 'support_telefono', 'audit_email', 'audit_email_enabled',
            'verification_expiration_time', 'allow_profile_editing', 'created_at', 'updated_at',
        ],
    ];

    public function run(): void
    {
        if (! filter_var(env('APPLICATIONBASE_ALLOW_INSTANCE_SNAPSHOT', false), FILTER_VALIDATE_BOOL)) {
            throw new RuntimeException('Instance parameter snapshot seeding requires APPLICATIONBASE_ALLOW_INSTANCE_SNAPSHOT=true.');
        }
        if (DB::connection()->getDatabaseName() === 'db_laportada') {
            throw new RuntimeException('ApplicationBase instance snapshots must never run against db_laportada.');
        }
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException('Instance parameter snapshot requires table ['.$table.'].');
            }
        }
        $snapshot = $this->loadSnapshot();
        $this->assertSnapshotIntegrity($snapshot);
        DB::transaction(fn () => $this->restoreOrVerify($snapshot));
    }

    private function loadSnapshot(): array
    {
        return collect(self::TABLES)->mapWithKeys(function (string $table): array {
            $rows = require database_path('data/instance-snapshot/'.$table.'.php');
            if (! is_array($rows)) {
                throw new RuntimeException('Instance parameter snapshot ['.$table.'] is invalid.');
            }

            return [$table => $rows];
        })->all();
    }

    private function assertSnapshotIntegrity(array $snapshot): void
    {
        foreach (self::TABLES as $table) {
            if (count($snapshot[$table]) !== 1 || array_keys($snapshot[$table][0]) !== self::COLUMNS[$table]) {
                throw new RuntimeException('Instance parameter snapshot ['.$table.'] must contain one row with the exact columns.');
            }
        }
    }

    private function restoreOrVerify(array $snapshot): void
    {
        $counts = collect(self::TABLES)->mapWithKeys(
            static fn (string $table): array => [$table => DB::table($table)->count()],
        );
        if ($counts->every(static fn (int $count): bool => $count === 0)) {
            foreach (self::TABLES as $table) {
                DB::table($table)->insert($snapshot[$table]);
            }
            foreach (self::TABLES as $table) {
                if (! $this->tableMatches($table, $snapshot[$table])) {
                    throw new RuntimeException('Instance parameter snapshot postcondition failed for ['.$table.'].');
                }
            }

            return;
        }
        if (collect(self::TABLES)->every(
            fn (string $table): bool => $this->tableMatches($table, $snapshot[$table]),
        )) {
            $this->command?->info('Instance parameter snapshot already matches exactly; no changes were made.');

            return;
        }
        throw new RuntimeException('Parameter tables are neither both empty nor an exact snapshot match. No changes were made.');
    }

    private function tableMatches(string $table, array $expected): bool
    {
        if (DB::table($table)->count() !== count($expected)) {
            return false;
        }
        $actual = DB::table($table)->select(self::COLUMNS[$table])->orderBy('id')->get()
            ->map(static fn (object $row): array => (array) $row)->all();

        return $actual === $expected;
    }
}
