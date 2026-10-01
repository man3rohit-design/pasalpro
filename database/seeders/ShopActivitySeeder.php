<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\CancelSale;
use App\Actions\CompleteSale;
use App\Actions\CreateSale;
use App\Actions\ReceivePurchaseOrder;
use App\Actions\RecordStockMovement;
use App\Enums\PaymentMethod;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SaleChannel;
use App\Enums\SaleStatus;
use App\Enums\StockTransactionType;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\LoginHistory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class ShopActivitySeeder extends Seeder
{
    private const ONLINE_SALES_START_DAY = 30;

    private const RESTOCK_EVERY_DAYS = 6;

    /** @var Collection<int, User> */
    private Collection $cashiers;

    private User $manager;

    private User $storekeeper;

    public function __construct(
        private readonly CreateSale $createSale,
        private readonly CompleteSale $completeSale,
        private readonly CancelSale $cancelSale,
        private readonly ReceivePurchaseOrder $receivePurchaseOrder,
        private readonly RecordStockMovement $recordStockMovement,
    ) {}

    public function run(): void
    {
        $this->cashiers = User::where('role', UserRole::Cashier)->get();
        $this->manager = User::where('role', UserRole::Manager)->firstOrFail();
        $this->storekeeper = User::where('role', UserRole::Storekeeper)->firstOrFail();

        $customers = Customer::all();
        $onlineCustomers = $customers->whereNotNull('user_id');
        $realNow = CarbonImmutable::now();

        for ($daysAgo = DatabaseSeeder::HISTORY_DAYS; $daysAgo >= 0; $daysAgo--) {
            $day = $realNow->subDays($daysAgo)->startOfDay();

            DB::transaction(function () use ($day, $daysAgo, $customers, $onlineCustomers, $realNow): void {
                $this->recordStaffLogins($day->setTime(7, 45));
                $this->receiveApprovedPurchaseOrders($day->setTime(9, 30));

                if ($daysAgo % self::RESTOCK_EVERY_DAYS === 0) {
                    $this->orderLowStock($day->setTime(10, 15), approve: $daysAgo > 0);
                }

                $openingHour = $day->setTime(8, 0);
                $closingHour = $daysAgo === 0 ? $realNow : $day->setTime(20, 0);
                $salesToday = $day->isSaturday() ? fake()->numberBetween(20, 28) : fake()->numberBetween(12, 20);

                foreach ($this->timesBetween($openingHour, $closingHour, $salesToday) as $time) {
                    $this->counterSale($time, $customers);
                }

                if ($daysAgo <= self::ONLINE_SALES_START_DAY) {
                    foreach ($this->timesBetween($openingHour, $closingHour, fake()->numberBetween(1, 4)) as $time) {
                        $this->onlineOrder($time, $onlineCustomers, $daysAgo);
                    }
                }

                if (fake()->boolean(15)) {
                    $this->writeOffDamagedStock($day->setTime(19, 30));
                }
            });
        }

        Date::setTestNow();

        DatabaseNotification::where('created_at', '<', now()->subDays(3))->update(['read_at' => now()]);
    }

    /**
     * @param Collection<int, Customer> $customers
     */
    private function counterSale(CarbonImmutable $time, Collection $customers): void
    {
        Date::setTestNow($time);

        $quantities = $this->pickBasket(fake()->numberBetween(1, 5));

        if ($quantities === []) {
            return;
        }

        $paymentMethod = fake()->randomElement([
            ...array_fill(0, 12, PaymentMethod::Cash),
            ...array_fill(0, 4, PaymentMethod::Fonepay),
            ...array_fill(0, 2, PaymentMethod::Esewa),
            PaymentMethod::Khalti,
            PaymentMethod::Card,
        ]);

        $this->createSale->handle(
            $quantities,
            SaleChannel::Counter,
            $paymentMethod,
            customer: fake()->boolean(30) ? $customers->random() : null,
            soldBy: $this->cashiers->random(),
            discount: fake()->boolean(10) ? fake()->randomElement([10, 20, 50]) : 0,
            paymentReference: $paymentMethod === PaymentMethod::Cash ? null : str()->upper(fake()->bothify('??#########')),
        );
    }

    /**
     * @param Collection<int, Customer> $onlineCustomers
     */
    private function onlineOrder(CarbonImmutable $time, Collection $onlineCustomers, int $daysAgo): void
    {
        Date::setTestNow($time);

        $quantities = $this->pickBasket(fake()->numberBetween(2, 6));

        if ($quantities === []) {
            return;
        }

        $customer = $onlineCustomers->random();

        $sale = $this->createSale->handle(
            $quantities,
            SaleChannel::Online,
            fake()->randomElement([PaymentMethod::CashOnDelivery, PaymentMethod::CashOnDelivery, PaymentMethod::Fonepay]),
            customer: $customer,
            details: [
                'delivery_name'    => $customer->name,
                'delivery_phone'   => $customer->phone,
                'delivery_address' => $customer->address,
                'notes'            => fake()->optional(0.3)->randomElement(['Please call before delivery.', 'Leave with the guard at the gate.', 'Deliver after 5 pm.']),
            ],
        );

        if ($daysAgo === 0) {
            return;
        }

        if ($daysAgo === 1) {
            $sale->update(['status' => fake()->randomElement([SaleStatus::Confirmed, SaleStatus::OutForDelivery])]);

            return;
        }

        Date::setTestNow($time->addHours(fake()->numberBetween(3, 20)));

        fake()->boolean(8)
            ? $this->cancelSale->handle($sale, $this->manager)
            : $this->completeSale->handle($sale, $this->manager);
    }

    /**
     * @return array<int, int>
     */
    private function pickBasket(int $size): array
    {
        return Inventory::where('quantity', '>=', 3)
            ->inRandomOrder()
            ->limit($size)
            ->pluck('product_id')
            ->mapWithKeys(fn (int $productId): array => [$productId => fake()->randomElement([1, 1, 1, 2, 2, 3])])
            ->all();
    }

    private function orderLowStock(CarbonImmutable $time, bool $approve): void
    {
        Date::setTestNow($time);

        Product::whereHas('inventory', fn (Builder $inventory) => $inventory->whereRaw('quantity <= reorder_level * 1.5'))
            ->whereDoesntHave('purchaseItems.purchaseOrder', fn (Builder $purchaseOrder) => $purchaseOrder->whereIn('status', [PurchaseOrderStatus::Pending, PurchaseOrderStatus::Approved]))
            ->with('inventory')
            ->get()
            ->groupBy('supplier_id')
            ->each(function (Collection $products, int $supplierId) use ($approve): void {
                $purchaseOrder = PurchaseOrder::create([
                    'supplier_id'   => $supplierId,
                    'ordered_on'    => today(),
                    'expected_on'   => today()->addDay(),
                    'created_by_id' => $this->storekeeper->id,
                ]);

                foreach ($products as $product) {
                    $purchaseOrder->items()->create([
                        'product_id' => $product->id,
                        'quantity'   => $product->inventory->reorder_level * 4,
                        'unit_cost'  => $product->cost_price,
                    ]);
                }

                $purchaseOrder->recalculateTotal();

                if ($approve) {
                    $purchaseOrder->update([
                        'status'         => PurchaseOrderStatus::Approved,
                        'approved_by_id' => $this->manager->id,
                        'approved_at'    => now()->addMinutes(40),
                    ]);
                }
            });
    }

    private function receiveApprovedPurchaseOrders(CarbonImmutable $time): void
    {
        Date::setTestNow($time);

        PurchaseOrder::where('status', PurchaseOrderStatus::Approved)
            ->whereDate('expected_on', '<=', $time)
            ->get()
            ->each(fn (PurchaseOrder $purchaseOrder): PurchaseOrder => $this->receivePurchaseOrder->handle($purchaseOrder, $this->storekeeper));
    }

    private function writeOffDamagedStock(CarbonImmutable $time): void
    {
        Date::setTestNow($time);

        $inventory = Inventory::with('product')->where('quantity', '>', 10)->inRandomOrder()->first();

        $this->recordStockMovement->handle(
            $inventory->product,
            -fake()->numberBetween(1, 3),
            StockTransactionType::Adjustment,
            performedBy: $this->storekeeper,
            note: fake()->randomElement(['Damaged packet found on shelf', 'Expired, removed from shelf', 'Leaking bottle, written off']),
        );
    }

    private function recordStaffLogins(CarbonImmutable $time): void
    {
        $this->cashiers->concat([$this->manager, $this->storekeeper])
            ->each(fn (User $user): LoginHistory => $user->loginHistories()->create([
                'ip_address'   => '192.168.1.'.fake()->numberBetween(20, 60),
                'user_agent'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Safari/537.36',
                'logged_in_at' => $time->addMinutes(fake()->numberBetween(0, 30)),
            ]));
    }

    /**
     * @return list<CarbonImmutable>
     */
    private function timesBetween(CarbonImmutable $start, CarbonImmutable $end, int $count): array
    {
        if ($end->lessThanOrEqualTo($start)) {
            return [];
        }

        return collect(range(1, $count))
            ->map(fn (): CarbonImmutable => $start->addSeconds(fake()->numberBetween(0, (int) $start->diffInSeconds($end))))
            ->sort()
            ->values()
            ->all();
    }
}
