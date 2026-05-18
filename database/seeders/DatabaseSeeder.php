<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'          => 'Super Admin',
            'email'         => env('ADMIN_EMAIL', 'admin@cloudpet.id'),
            'password'      => Hash::make(env('ADMIN_PASSWORD', 'Admin@12345')),
            'role'          => 'admin',
            'animal_avatar' => '🦁',
        ]);

        User::create([
            'name'          => 'Budi Kucing',
            'email'         => 'budi@cloudpet.id',
            'password'      => Hash::make('User@12345'),
            'role'          => 'user',
            'animal_avatar' => '🐱',
        ]);
    }
}