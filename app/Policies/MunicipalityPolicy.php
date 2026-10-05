<?php

namespace App\Policies;

use App\Models\Municipality;
use App\Models\User;

class MunicipalityPolicy
{
    /**
     * Determine whether the user can view any municipalities.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('municipalities.view');
    }

    /**
     * Determine whether the user can view the municipality.
     */
    public function view(User $user, Municipality $municipality): bool
    {
        return $user->hasPermissionTo('municipalities.view');
    }

    /**
     * Determine whether the user can create municipalities.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('municipalities.create');
    }

    /**
     * Determine whether the user can update the municipality.
     */
    public function update(User $user, Municipality $municipality): bool
    {
        return $user->hasPermissionTo('municipalities.edit');
    }

    /**
     * Determine whether the user can delete the municipality.
     */
    public function delete(User $user, Municipality $municipality): bool
    {
        return $user->hasPermissionTo('municipalities.delete');
    }
}
