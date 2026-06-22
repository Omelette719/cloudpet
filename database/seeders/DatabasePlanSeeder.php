<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabasePlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'db-micro',  'vcpu' => 1, 'ram' => 1024, 'storage' => 10, 'price' => 1500],
            ['name' => 'db-small',  'vcpu' => 1, 'ram' => 2048, 'storage' => 20, 'price' => 3000],
            ['name' => 'db-medium', 'vcpu' => 2, 'ram' => 4096, 'storage' => 40, 'price' => 6000],
        ];

        foreach ($plans as $p) {
            Plan::updateOrCreate(
                ['service_type' => 'DATABASE', 'name' => $p['name']],
                array_merge($p, ['id' => (string) Str::uuid(), 'service_type' => 'DATABASE'])
            );
        }
    }
}
