<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user if it doesn't exist
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        // Update role if user exists but isn't admin
        if ($admin->role !== 'admin' && $admin->role !== 'super_admin') {
            $admin->update(['role' => 'admin']);
        }

        // Update password if it's null (for existing users without password)
        if (is_null($admin->password)) {
            $admin->update(['password' => Hash::make('admin123')]);
        }

        $this->command->info('Admin user created/updated successfully!');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: admin123');
        $this->command->warn('⚠️  Please change the password in production!');

        // Optionally create a super admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('superadmin123'),
                'role' => 'super_admin',
            ]
        );

        if ($superAdmin->role !== 'super_admin') {
            $superAdmin->update(['role' => 'super_admin']);
        }

        if (is_null($superAdmin->password)) {
            $superAdmin->update(['password' => Hash::make('superadmin123')]);
        }

        $this->command->info('Super admin user created/updated successfully!');
        $this->command->info('Email: superadmin@example.com');
        $this->command->info('Password: superadmin123');
    }
}
