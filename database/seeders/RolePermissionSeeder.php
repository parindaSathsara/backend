<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * Role and Permission Seeder
 * Seeds initial roles and permissions
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $superAdmin = Role::create([
            'name' => 'super_admin',
            'display_name' => 'Super Admin',
            'description' => 'Full system access'
        ]);

        $admin = Role::create([
            'name' => 'admin',
            'display_name' => 'Admin',
            'description' => 'Admin access to manage products, orders, and customers'
        ]);

        $customer = Role::create([
            'name' => 'customer',
            'display_name' => 'Customer',
            'description' => 'Customer account for shopping'
        ]);

        // Create permissions
        $permissions = [
            // Product permissions
            ['name' => 'view_products', 'display_name' => 'View Products'],
            ['name' => 'create_products', 'display_name' => 'Create Products'],
            ['name' => 'edit_products', 'display_name' => 'Edit Products'],
            ['name' => 'delete_products', 'display_name' => 'Delete Products'],
            
            // Category permissions
            ['name' => 'view_categories', 'display_name' => 'View Categories'],
            ['name' => 'create_categories', 'display_name' => 'Create Categories'],
            ['name' => 'edit_categories', 'display_name' => 'Edit Categories'],
            ['name' => 'delete_categories', 'display_name' => 'Delete Categories'],
            
            // Album permissions
            ['name' => 'view_albums', 'display_name' => 'View Albums'],
            ['name' => 'create_albums', 'display_name' => 'Create Albums'],
            ['name' => 'edit_albums', 'display_name' => 'Edit Albums'],
            ['name' => 'delete_albums', 'display_name' => 'Delete Albums'],
            
            // Order permissions
            ['name' => 'view_orders', 'display_name' => 'View Orders'],
            ['name' => 'edit_orders', 'display_name' => 'Edit Orders'],
            ['name' => 'delete_orders', 'display_name' => 'Delete Orders'],
            
            // Customer permissions
            ['name' => 'view_customers', 'display_name' => 'View Customers'],
            ['name' => 'edit_customers', 'display_name' => 'Edit Customers'],
            ['name' => 'delete_customers', 'display_name' => 'Delete Customers'],
            
            // Inventory permissions
            ['name' => 'view_inventory', 'display_name' => 'View Inventory'],
            ['name' => 'manage_inventory', 'display_name' => 'Manage Inventory'],
            
            // Analytics permissions
            ['name' => 'view_analytics', 'display_name' => 'View Analytics'],
            ['name' => 'view_reports', 'display_name' => 'View Reports'],
            
            // Review permissions
            ['name' => 'view_reviews', 'display_name' => 'View Reviews'],
            ['name' => 'approve_reviews', 'display_name' => 'Approve Reviews'],
            ['name' => 'delete_reviews', 'display_name' => 'Delete Reviews'],
            
            // Banner permissions
            ['name' => 'manage_banners', 'display_name' => 'Manage Banners'],
            
            // Coupon permissions
            ['name' => 'manage_coupons', 'display_name' => 'Manage Coupons'],
        ];

        $createdPermissions = [];
        foreach ($permissions as $permission) {
            $createdPermissions[$permission['name']] = Permission::create($permission);
        }

        // Assign all permissions to super admin
        $superAdmin->permissions()->attach(array_values(array_map(fn($p) => $p->id, $createdPermissions)));

        // Assign specific permissions to admin
        $adminPermissions = [
            'view_products', 'create_products', 'edit_products', 'delete_products',
            'view_categories', 'create_categories', 'edit_categories', 'delete_categories',
            'view_albums', 'create_albums', 'edit_albums', 'delete_albums',
            'view_orders', 'edit_orders',
            'view_customers', 'edit_customers',
            'view_inventory', 'manage_inventory',
            'view_analytics', 'view_reports',
            'view_reviews', 'approve_reviews', 'delete_reviews',
            'manage_banners',
            'manage_coupons',
        ];

        foreach ($adminPermissions as $permName) {
            if (isset($createdPermissions[$permName])) {
                $admin->permissions()->attach($createdPermissions[$permName]->id);
            }
        }
    }
}
