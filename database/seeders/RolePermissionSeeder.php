<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = Role::create(['name' => 'super_admin']);
        $productManager = Role::create(['name' => 'product_manager']);
        $userManager = Role::create(['name' => 'user_manager']);
        $client = Role::create(['name' => 'client']);

        $permissions = [
            'view_dashboard',
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'view_categories',
            'create_categories',
            'edit_categories',
            'delete_categories',
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'view_orders',
            'create_orders',
            'edit_orders',
            'delete_orders',
            'view_payments',
            'create_payments',
            'edit_payments',
            'delete_payments',
        ];


        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
        $productManager->givePermissionTo(['view_products', 'create_products', 'edit_products', 'delete_products', 'view_categories', 'create_categories', 'edit_categories', 'delete_categories']);
        $userManager->givePermissionTo(['view_users', 'create_users', 'edit_users', 'delete_users']);
        $superAdmin->givePermissionTo(Permission::all());
        $client->givePermissionTo(['view_orders', 'create_orders', 'edit_orders', 'delete_orders', 'view_payments', 'create_payments', 'edit_payments', 'delete_payments']);
    }
}
