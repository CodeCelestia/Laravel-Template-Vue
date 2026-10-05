<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'notes.view',
            'notes.create',
            'notes.edit',
            'notes.delete',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $systemAdmin = Role::firstOrCreate(['name' => 'System Admin', 'guard_name' => 'web']);
        $developer = Role::firstOrCreate(['name' => 'Developer', 'guard_name' => 'web']);

        $systemAdmin->givePermissionTo($permissions);
        $developer->givePermissionTo($permissions);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'notes.view',
            'notes.create',
            'notes.edit',
            'notes.delete',
        ];

        Permission::whereIn('name', $permissions)->where('guard_name', 'web')->delete();
    }
};
