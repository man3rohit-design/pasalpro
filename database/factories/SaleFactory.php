<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\SaleChannel;
use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(150, 5000);

        return [
            'channel'        => SaleChannel::Counter,
            'status'         => SaleStatus::Pending,
            'subtotal'       => $subtotal,
            'total'          => $subtotal,
            'payment_method' => PaymentMethod::Cash,
        ];
    }

    public function online(): static
    {
        return $this->state(fn (): array => [
            'channel'          => SaleChannel::Online,
            'customer_id'      => Customer::factory()->withAccount(),
            'payment_method'   => PaymentMethod::CashOnDelivery,
            'delivery_name'    => fake()->name(),
            'delivery_phone'   => fake()->numerify('98########'),
            'delivery_address' => 'Chabahil, Kathmandu',
        ]);
    }
}
