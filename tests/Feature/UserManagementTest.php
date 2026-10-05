<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guests_are_redirected_to_login_when_visiting_users_index(): void
    {
        $response = $this->get(route('users.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_unauthorized_users_without_permission_cannot_access_users_index(): void
    {
        $user = User::factory()->create();
        // Plain user without roles or permissions
        $this->actingAs($user);

        $response = $this->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_system_admin_can_access_users_index(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $response = $this->get(route('users.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('users/Index')
            ->has('users.data')
            ->has('roles')
            ->has('filters')
        );
    }

    public function test_users_can_be_filtered_by_search_query(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        User::factory()->create([
            'name' => 'UniqueSearchableUser',
            'email' => 'unique.search@example.com',
        ]);

        $response = $this->get(route('users.index', ['search' => 'UniqueSearchableUser']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('users/Index')
            ->where('users.total', 1)
            ->where('users.data.0.name', 'UniqueSearchableUser')
        );
    }

    public function test_users_can_be_filtered_by_role(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $response = $this->get(route('users.index', ['role' => 'System Admin']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('users/Index')
            ->where('users.total', 1)
            ->where('users.data.0.email', 'admin@surigaodelnorte.gov.ph')
        );
    }

    public function test_system_admin_can_create_user_with_assigned_role(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $response = $this->post(route('users.store'), [
            'name' => 'Hon. Governor Staff',
            'email' => 'governor.staff@surigaodelnorte.gov.ph',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'role' => 'User',
        ]);

        $response->assertRedirect(route('users.index'));

        $created = User::where('email', 'governor.staff@surigaodelnorte.gov.ph')->first();
        $this->assertNotNull($created);
        $this->assertTrue(Hash::check('SecurePass123!', $created->password));
        $this->assertTrue($created->hasRole('User'));
    }

    public function test_user_creation_validates_required_fields_and_email_uniqueness(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $response = $this->post(route('users.store'), [
            'name' => '',
            'email' => 'admin@surigaodelnorte.gov.ph', // duplicate email
            'password' => 'short',
            'password_confirmation' => 'mismatch',
            'role' => 'InvalidRole',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password', 'role']);
    }

    public function test_system_admin_can_view_user_with_roles_and_permissions(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        /** @var User $user */
        $user = User::where('email', 'user@example.com')->first();

        $response = $this->getJson(route('users.show', $user));

        $response->assertOk();
        $response->assertJsonStructure([
            'id',
            'name',
            'email',
            'roles',
            'permissions',
        ]);
        $response->assertJsonFragment(['name' => 'User']);
    }

    public function test_system_admin_can_update_user_and_role(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        /** @var User $target */
        $target = User::factory()->create();
        $target->assignRole('User');

        $response = $this->put(route('users.update', $target), [
            'name' => 'Updated Officer Name',
            'email' => 'updated.officer@surigaodelnorte.gov.ph',
            'role' => 'System Admin',
        ]);

        $response->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertSame('Updated Officer Name', $target->name);
        $this->assertSame('updated.officer@surigaodelnorte.gov.ph', $target->email);
        $this->assertTrue($target->hasRole('System Admin'));
        $this->assertFalse($target->hasRole('User'));
    }

    public function test_system_admin_can_delete_another_user(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        /** @var User $target */
        $target = User::factory()->create();

        $response = $this->delete(route('users.destroy', $target));

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_system_admin_cannot_delete_their_own_account(): void
    {
        /** @var User $admin */
        $admin = User::where('email', 'admin@surigaodelnorte.gov.ph')->first();
        $this->actingAs($admin);

        $response = $this->delete(route('users.destroy', $admin));

        $response->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'email' => $admin->email]);
    }

    public function test_seeder_establishes_template_roles_and_permissions(): void
    {
        $expectedRoles = [
            'System Admin',
            'User',
        ];

        foreach ($expectedRoles as $roleName) {
            $this->assertTrue(Role::where('name', $roleName)->exists(), "Role [{$roleName}] was not created.");
        }

        $expectedPermissions = [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'permissions.view',
            'permissions.create',
            'permissions.edit',
            'permissions.delete',
            'municipalities.view',
            'municipalities.create',
            'municipalities.edit',
            'municipalities.delete',
            'notes.view',
            'notes.create',
            'notes.edit',
            'notes.delete',
        ];

        foreach ($expectedPermissions as $permName) {
            $this->assertTrue(Permission::where('name', $permName)->exists(), "Permission [{$permName}] was not created.");
        }

        /** @var Role $adminRole */
        $adminRole = Role::findByName('System Admin');
        $this->assertTrue($adminRole->hasPermissionTo('users.view'));

        /** @var Role $userRole */
        $userRole = Role::findByName('User');
        $this->assertFalse($userRole->hasPermissionTo('users.delete'));
    }
}
