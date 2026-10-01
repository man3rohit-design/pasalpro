<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name'  => 'Anisha Gurung',
            'email' => 'customer@pasalpro.test',
            'phone' => '9846612345',
        ]);

        Customer::factory()->for($user)->create([
            'name'    => $user->name,
            'phone'   => $user->phone,
            'email'   => $user->email,
            'address' => 'Jhamsikhel, Lalitpur',
        ]);

        Customer::factory()->withAccount()->count(14)->create();
        Customer::factory()->count(30)->create();
    }
}
