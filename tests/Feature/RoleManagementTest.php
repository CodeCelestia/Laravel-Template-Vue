<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guests_are_redirected_to_login_when_visiting_roles_index(): void
    {
        $response = $this->get(route('roles.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_unauthorized_users_without_permission_cannot_access_roles_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('roles.index'));

        $response->assertForbidden();
    }

    public function test_system_admin_can_access_roles_index(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $response = $this->get(route('roles.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('roles/Index')
            ->has('roles.data')
            ->has('permissions')
            ->has('all_roles')
            ->has('filters')
        );
    }

    public function test_roles_can_be_filtered_by_search_query(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        Role::create(['name' => 'Custom Land Inspector', 'guard_name' => 'web']);

        $response = $this->get(route('roles.index', ['search' => 'Land Inspector']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('roles/Index')
            ->where('roles.total', 1)
            ->where('roles.data.0.name', 'Custom Land Inspector')
        );
    }

    public function test_can_view_role_details_via_json(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $role = Role::where('name', 'User')->first();

        $response = $this->getJson(route('roles.show', $role));

        $response->assertOk();
        $response->assertJsonPath('name', 'User');
    }

    public function test_can_create_new_role_with_permissions(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $response = $this->post(route('roles.store'), [
            'name' => 'Auditor Specialist',
            'permissions' => ['roles.view', 'users.view'],
        ]);

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', ['name' => 'Auditor Specialist']);

        $role = Role::findByName('Auditor Specialist');
        $this->assertTrue($role->hasPermissionTo('roles.view'));
        $this->assertTrue($role->hasPermissionTo('users.view'));
        $this->assertFalse($role->hasPermissionTo('users.delete'));
    }

    public function test_cannot_create_duplicate_role_name(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $response = $this->post(route('roles.store'), [
            'name' => 'System Admin',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_can_update_role_and_synchronize_permissions(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $role = Role::create(['name' => 'Junior Operator', 'guard_name' => 'web']);
        $role->givePermissionTo('users.view');

        $response = $this->put(route('roles.update', $role), [
            'name' => 'Senior Operator',
            'permissions' => ['users.view', 'users.edit'],
        ]);

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'Senior Operator']);

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('users.edit'));
    }

    public function test_cannot_rename_system_admin_role(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $systemAdminRole = Role::findByName('System Admin');

        $response = $this->put(route('roles.update', $systemAdminRole), [
            'name' => 'Super Administrator',
            'permissions' => Permission::all()->pluck('name')->toArray(),
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('roles', ['id' => $systemAdminRole->id, 'name' => 'System Admin']);
    }

    public function test_cannot_delete_system_admin_role(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $systemAdminRole = Role::findByName('System Admin');

        $response = $this->delete(route('roles.destroy', $systemAdminRole));

        $response->assertForbidden();
        $this->assertDatabaseHas('roles', ['id' => $systemAdminRole->id]);
    }

    public function test_cannot_delete_role_with_active_users(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $role = Role::findByName('User');
        $this->assertGreaterThan(0, $role->users()->count());

        $response = $this->delete(route('roles.destroy', $role));

        $response->assertForbidden();
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_can_delete_unassigned_custom_role(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $customRole = Role::create(['name' => 'Temporary Contractor', 'guard_name' => 'web']);

        $response = $this->delete(route('roles.destroy', $customRole));

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $customRole->id]);
    }
}
