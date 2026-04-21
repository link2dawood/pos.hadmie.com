<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Ensure the account.access permission exists
        $permission = Permission::firstOrCreate(['name' => 'account.access']);

        // Find all Admin roles (format: Admin#{business_id})
        $admin_roles = Role::where('name', 'like', 'Admin#%')->get();

        foreach ($admin_roles as $role) {
            // Give the permission to the role if it doesn't already have it
            if (!$role->hasPermissionTo('account.access')) {
                $role->givePermissionTo('account.access');
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Find all Admin roles
        $admin_roles = Role::where('name', 'like', 'Admin#%')->get();

        foreach ($admin_roles as $role) {
            // Revoke the permission from the role
            $role->revokePermissionTo('account.access');
        }
    }
};

