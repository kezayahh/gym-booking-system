<?php

namespace Database\Seeders;


use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@citygym.com',
            'password' => Hash::make('admin123'),
            'phone' => '09123456789',
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Create test user
        User::create([
            'name' => 'Test User',
            'email' => 'user@citygym.com',
            'password' => Hash::make('user123'),
            'phone' => '09987654321',
            'role' => 'user',
            'status' => 'active',
        ]);

        $this->command->info('✅ Admin and Test User created successfully!');
        $this->command->info('Admin: admin@citygym.com / admin123');
        $this->command->info('User: user@citygym.com / user123');
    }
}