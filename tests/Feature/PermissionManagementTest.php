<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_unauthorized_user_cannot_create_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('permissions.store'), [
            'name' => 'custom.action',
        ]);

        $response->assertForbidden();
    }

    public function test_system_admin_can_create_new_permission(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $response = $this->post(route('permissions.store'), [
            'name' => 'users.export-csv',
            'roles' => ['User'],
        ]);

        $response->assertRedirect(route('roles.index', ['tab' => 'permissions']));
        $this->assertDatabaseHas('permissions', ['name' => 'users.export-csv']);

        $perm = Permission::findByName('users.export-csv');
        $userRole = Role::findByName('User');
        $this->assertTrue($userRole->hasPermissionTo($perm));
    }

    public function test_permission_name_must_follow_dot_syntax(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $response = $this->post(route('permissions.store'), [
            'name' => 'invalid permission name with spaces',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_cannot_create_duplicate_permission(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $response = $this->post(route('permissions.store'), [
            'name' => 'users.view',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_can_update_permission_name(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $perm = Permission::create(['name' => 'temporary.old-slug', 'guard_name' => 'web']);

        $response = $this->put(route('permissions.update', $perm), [
            'name' => 'temporary.new-slug',
            'roles' => ['User'],
        ]);

        $response->assertRedirect(route('roles.index', ['tab' => 'permissions']));
        $this->assertDatabaseHas('permissions', ['id' => $perm->id, 'name' => 'temporary.new-slug']);

        $userRole = Role::findByName('User');
        $this->assertTrue($userRole->hasPermissionTo('temporary.new-slug'));
    }

    public function test_can_delete_permission(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $perm = Permission::create(['name' => 'temporary.to-be-deleted', 'guard_name' => 'web']);

        $response = $this->delete(route('permissions.destroy', $perm));

        $response->assertRedirect(route('roles.index', ['tab' => 'permissions']));
        $this->assertDatabaseMissing('permissions', ['id' => $perm->id]);
    }
}
