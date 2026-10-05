<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define Granular Permissions
        $permissions = [
            // User Management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Roles & Permissions Management
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'permissions.view',
            'permissions.create',
            'permissions.edit',
            'permissions.delete',

            // Municipalities & Cities Master Data
            'municipalities.view',
            'municipalities.create',
            'municipalities.edit',
            'municipalities.delete',

            // Developer Open Notes
            'notes.view',
            'notes.create',
            'notes.edit',
            'notes.delete',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web']
            );
        }

        // 2. Define Template Roles
        $systemAdminRole = Role::firstOrCreate(['name' => 'System Admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);

        // 3. Assign Granular Permissions to Roles Idempotently
        $systemAdminRole->syncPermissions(Permission::all());
        $userRole->syncPermissions([]);

        // 4. Seed Default Initial Users
        $defaultPassword = Hash::make('password');

        $initialUsers = [
            [
                'name' => 'System Administrator',
                'email' => 'admin@surigaodelnorte.gov.ph',
                'role' => 'System Admin',
            ],
            [
                'name' => 'Test User',
                'email' => 'user@example.com',
                'role' => 'User',
            ],
        ];

        foreach ($initialUsers as $userData) {
            $user = User::firstOrNew(['email' => $userData['email']]);
            $user->name = $userData['name'];
            if (! $user->exists || empty($user->password)) {
                $user->password = $defaultPassword;
            }
            $user->email_verified_at = $user->email_verified_at ?? now();
            $user->save();

            $user->syncRoles([$userData['role']]);
        }

        // 5. Seed Additional Realistic Users to demonstrate pagination, search, and sorting
        $sampleNames = [
            'Maria Corazon Santos',
            'Juan Carlos Dela Cruz',
            'Rowena Bautista',
            'Danilo Macapagal',
            'Grace Villanueva',
            'Eduardo Hernandez',
            'Luzviminda Reyes',
            'Ramon Magsaysay Jr.',
            'Kristine Joy Alcantara',
            'Vicente Sotto III',
        ];

        foreach ($sampleNames as $name) {
            $slug = strtolower(str_replace(' ', '.', preg_replace('/[^a-zA-Z0-9 ]/', '', $name)));
            $email = "{$slug}@example.com";

            $sampleUser = User::firstOrNew(['email' => $email]);
            $sampleUser->name = $name;
            if (! $sampleUser->exists || empty($sampleUser->password)) {
                $sampleUser->password = $defaultPassword;
            }
            $sampleUser->email_verified_at = $sampleUser->email_verified_at ?? now();
            $sampleUser->save();

            $sampleUser->syncRoles(['User']);
        }
    }
}
