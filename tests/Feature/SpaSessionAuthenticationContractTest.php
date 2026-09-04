<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpaSessionAuthenticationContractTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as refreshIsolatedDatabase;
    }

    public function refreshDatabase(): void
    {
        $this->assertSame(
            'mysql',
            config('database.default'),
            'Refusing to migrate: this test requires the mysql connection.',
        );

        $configuredDatabase = config('database.connections.mysql.database');

        $this->assertNotSame('db_spa_pwa', $configuredDatabase, 'Refusing to migrate the development database.');
        $this->assertNotSame('db_laportada', $configuredDatabase, 'Refusing to access db_laportada.');
        $this->assertIsString($configuredDatabase, 'Refusing to migrate: the testing database name is invalid.');
        $this->assertStringEndsWith('_testing', $configuredDatabase, 'Refusing to migrate a database without the _testing suffix.');
        $this->assertSame(
            'db_spa_pwa_testing',
            $configuredDatabase,
            'Refusing to migrate: this test requires the dedicated testing database.',
        );

        $connection = DB::connection();

        $this->assertSame('mysql', $connection->getDriverName(), 'Refusing to migrate: the real connection is not mysql.');

        $actualDatabase = $connection->getDatabaseName();

        $this->assertNotSame('db_spa_pwa', $actualDatabase, 'Refusing to migrate the development database.');
        $this->assertNotSame('db_laportada', $actualDatabase, 'Refusing to access db_laportada.');
        $this->assertStringEndsWith('_testing', $actualDatabase, 'Refusing to migrate a database without the _testing suffix.');
        $this->assertSame(
            'db_spa_pwa_testing',
            $actualDatabase,
            'Refusing to migrate: the real connection is not using the dedicated testing database.',
        );

        $this->refreshIsolatedDatabase();
    }

    public function test_spa_session_authentication_contract(): void
    {
        [$origin, $host] = $this->statefulOriginAndHost();
        $now = now();

        DB::table('regiones')->insert([
            'id' => 1,
            'nombre' => 'Synthetic Test Region',
            'orden' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('comunas')->insert([
            'id' => 1,
            'nombre' => 'Synthetic Test Commune',
            'region_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $password = 'testing-password';
        $user = User::query()->create([
            'rut_usuario' => 'TEST-USER-1',
            'nombre_usuario' => 'Synthetic',
            'apellidos_usuario' => 'User',
            'email' => 'spa-contract@example.test',
            'comuna_id' => 1,
            'password' => Hash::make($password),
        ]);

        $this->withServerVariables(['HTTP_HOST' => $host])
            ->withHeaders([
                'Accept' => 'application/json',
                'Origin' => $origin,
                'Referer' => $origin.'/',
            ]);

        $this->get('/sanctum/csrf-cookie')
            ->assertNoContent()
            ->assertCookie('XSRF-TOKEN');

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => $password,
        ])->assertOk();

        $this->assertAuthenticatedAs($user, 'web');

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);

        $this->postJson('/api/logout')->assertOk();

        $this->assertGuest('web');
        $this->app->make('auth')->forgetGuards();
        $this->getJson('/api/user')->assertUnauthorized();
    }

    /**
     * @return array{string, string}
     */
    private function statefulOriginAndHost(): array
    {
        $configuredDomain = collect(config('sanctum.stateful', []))
            ->map(static fn ($domain): string => trim((string) $domain))
            ->first(static fn (string $domain): bool => $domain !== '');

        $this->assertNotNull(
            $configuredDomain,
            'The effective Sanctum configuration has no usable stateful domain.',
        );

        $host = $configuredDomain === Sanctum::$currentRequestHostPlaceholder
            ? 'localhost'
            : preg_replace('/^\*\./', 'spa-contract.', $configuredDomain);
        $host = preg_replace('#^https?://#', '', $host);
        $host = rtrim($host, '/');

        $this->assertNotSame('', $host, 'The effective Sanctum stateful domain is not usable.');
        $this->assertStringNotContainsString('*', $host, 'The effective Sanctum stateful domain is not usable.');

        return ['http://'.$host, $host];
    }
}
