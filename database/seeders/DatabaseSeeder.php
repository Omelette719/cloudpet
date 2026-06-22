<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@cloudpet.id')],
            [
                'name'          => 'Super Admin',
                'password'      => Hash::make(env('ADMIN_PASSWORD', 'Admin@12345')),
                'role'          => 'admin',
                'animal_avatar' => '🦁',
                'balance'       => 0,
                'account_status'=> 'ACTIVE',
                'storage_plan'  => 'free',
                'storage_quota_gb' => 30,
            ]
        );

        // Demo user
        User::firstOrCreate(
            ['email' => 'budi@cloudpet.id'],
            [
                'name'          => 'Budi Kucing',
                'password'      => Hash::make('User@12345'),
                'role'          => 'user',
                'animal_avatar' => '🐱',
                'balance'       => 0,
                'account_status'=> 'ACTIVE',
                'storage_plan'  => 'free',
                'storage_quota_gb' => 30,
            ]
        );

        // Database plans
        $this->call(DatabasePlanSeeder::class);
    }
}
