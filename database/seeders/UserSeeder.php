<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * User Seeder
 * Seeds default admin and test users
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $customerRole = Role::where('name', 'customer')->first();

        // Create super admin
        User::create([
            'role_id' => $superAdminRole->id,
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@shwomens.com',
            'password' => Hash::make('password123'),
            'phone' => '9876543210',
            'is_active' => true,
        ]);

        // Create admin
        User::create([
            'role_id' => $adminRole->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@shwomens.com',
            'password' => Hash::make('password123'),
            'phone' => '9876543211',
            'is_active' => true,
        ]);

        // Create test customer
        User::create([
            'role_id' => $customerRole->id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'customer@test.com',
            'password' => Hash::make('password123'),
            'phone' => '9876543212',
            'address' => '123 Test Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            'country' => 'India',
            'is_active' => true,
        ]);
    }
}
