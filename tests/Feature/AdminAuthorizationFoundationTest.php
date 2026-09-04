<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AdminAuthorizationFoundationTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as refreshIsolatedDatabase;
    }

    public function refreshDatabase(): void
    {
        $this->assertSame('mysql', config('database.default'), 'Refusing to migrate: this test requires MySQL.');

        $configuredDatabase = config('database.connections.mysql.database');

        $this->assertIsString($configuredDatabase, 'Refusing to migrate: the testing database name is invalid.');
        $this->assertSame('db_spa_pwa_testing', $configuredDatabase, 'Refusing to migrate any database except the dedicated testing database.');

        $connection = DB::connection();

        $this->assertSame('mysql', $connection->getDriverName(), 'Refusing to migrate: the real connection is not MySQL.');
        $this->assertSame('db_spa_pwa_testing', $connection->getDatabaseName(), 'Refusing to migrate: the real connection is not using the dedicated testing database.');

        $this->refreshIsolatedDatabase();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $now = now();

        DB::table('catalogos')->insert([
            ['id' => 1, 'catalogo_id' => null, 'nombre' => 'Rol de Usuario', 'orden' => 1, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 63, 'catalogo_id' => 1, 'nombre' => 'Otro rol', 'orden' => 1, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 66, 'catalogo_id' => 1, 'nombre' => 'Nombre irrelevante para coordinador', 'orden' => 2, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 69, 'catalogo_id' => 1, 'nombre' => 'Nombre irrelevante para administrador', 'orden' => 3, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

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
    }

    public function test_guest_cannot_access_admin_context(): void
    {
        $this->getJson('/api/admin/context')->assertUnauthorized();
    }

    public function test_administrator_receives_all_administrative_capabilities(): void
    {
        $administrator = $this->userWithRole(config('constantes.ROL_ADMINISTRADOR_IT'));

        $this->assertGateStates($administrator, [true, true, true, true, true]);

        $this->actingAs($administrator, 'web');

        $this->getJson('/api/admin/context')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'can_access_admin' => true,
                    'capabilities' => [
                        'catalogs.manage',
                        'parameters.manage',
                        'organization.manage',
                        'users.manage',
                    ],
                ],
            ]);
    }

    public function test_coordinator_receives_only_organization_and_user_capabilities(): void
    {
        $coordinator = $this->userWithRole(config('constantes.ROL_COORDINADOR'));

        $this->assertGateStates($coordinator, [true, false, false, true, true]);

        $this->actingAs($coordinator, 'web');

        $this->getJson('/api/admin/context')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'can_access_admin' => true,
                    'capabilities' => [
                        'organization.manage',
                        'users.manage',
                    ],
                ],
            ]);
    }

    public function test_other_role_is_denied_every_administrative_capability(): void
    {
        $user = $this->userWithRole(config('constantes.ROL_PERSONAL_GERENCIA'));

        $this->assertGateStates($user, [false, false, false, false, false]);

        $this->actingAs($user, 'web');

        $this->getJson('/api/admin/context')->assertForbidden();
    }

    private function userWithRole(int $roleId): User
    {
        return User::query()->create([
            'rut_usuario' => 'TEST-'.$roleId,
            'nombre_usuario' => 'Synthetic',
            'apellidos_usuario' => 'User',
            'rol_id' => $roleId,
            'email' => 'role-'.$roleId.'@example.test',
            'comuna_id' => 1,
        ]);
    }

    /**
     * @param  array{bool, bool, bool, bool, bool}  $expected
     */
    private function assertGateStates(User $user, array $expected): void
    {
        $abilities = [
            'access-administration',
            'manage-parameters',
            'manage-catalogs',
            'manage-organization',
            'manage-users',
        ];

        foreach (array_combine($abilities, $expected) as $ability => $allowed) {
            $this->assertSame($allowed, Gate::forUser($user)->allows($ability), $ability);
        }
    }
}
