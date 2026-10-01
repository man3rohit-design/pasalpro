<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $staff = [
            ['Sunita Shrestha', 'admin@pasalpro.test', '9851023456', UserRole::Admin],
            ['Bikram Karki', 'manager@pasalpro.test', '9841156789', UserRole::Manager],
            ['Pooja Maharjan', 'cashier@pasalpro.test', '9803345678', UserRole::Cashier],
            ['Nabin Tamang', 'nabin@pasalpro.test', '9818823410', UserRole::Cashier],
            ['Sanjay Rai', 'storekeeper@pasalpro.test', '9860012345', UserRole::Storekeeper],
        ];

        foreach ($staff as [$name, $email, $phone, $role]) {
            User::factory()->role($role)->create([
                'name'  => $name,
                'email' => $email,
                'phone' => $phone,
            ]);
        }
    }
}
