<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superUser = User::create([
            'name' => 'superuser',
            'email' => 'superuser@gmail.com',
            'password' => Hash::make('superuser'),
        ]);
        $superUser->assignRole('superuser');

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@fleetmanager.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin');

        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@fleetmanager.com',
            'password' => Hash::make('password'),
        ]);
        $manager->assignRole('manager');

        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@fleetmanager.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('user');
    }
}
