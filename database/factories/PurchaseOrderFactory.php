<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'status'      => PurchaseOrderStatus::Pending,
            'ordered_on'  => today(),
            'expected_on' => today()->addDays(3),
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'status'      => PurchaseOrderStatus::Approved,
            'approved_at' => now(),
        ]);
    }
}
