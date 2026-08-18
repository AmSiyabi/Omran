<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            OwnersSeeder::class,
            CategorySeeder::class,
            ChartOfAccountsSeeder::class,
            DistributionPolicySeeder::class,
            TaxSettingsSeeder::class,
        ]);
    }
}
