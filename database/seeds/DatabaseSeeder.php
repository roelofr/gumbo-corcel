<?php

declare(strict_types=1);

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Seed roles and permissions
        $this->call(PermissionSeeder::class);

        // Required pages
        $this->call(PageSeeder::class);
    }
}
