<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

class DatabaseSeeder extends Seeder
{
    public const HISTORY_DAYS = 60;

    public function run(): void
    {
        Date::setTestNow(now()->subDays(self::HISTORY_DAYS)->setTime(7, 0));

        $this->call([
            UserSeeder::class,
            CatalogSeeder::class,
            CustomerSeeder::class,
        ]);

        Date::setTestNow();

        $this->call(ShopActivitySeeder::class);
    }
}
