<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user if it doesn't exist
        $adminEmail = 'admin@ecommerce.com';
        
        if (!User::where('email', $adminEmail)->exists()) {
            User::create([
                'name' => 'Administrador',
                'email' => $adminEmail,
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'is_admin' => true,
            ]);
            
            $this->command->info('Usuario administrador creado:');
            $this->command->info('Email: ' . $adminEmail);
            $this->command->info('Password: admin123');
        } else {
            $this->command->info('El usuario administrador ya existe.');
        }
    }
}