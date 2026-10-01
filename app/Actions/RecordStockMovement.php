<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AlertType;
use App\Enums\StockTransactionType;
use App\Enums\UserRole;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class RecordStockMovement
{
    public function handle(
        Product $product,
        int $quantity,
        StockTransactionType $type,
        ?Model $source = null,
        ?User $performedBy = null,
        ?string $note = null,
    ): StockTransaction {
        return DB::transaction(function () use ($product, $quantity, $type, $source, $performedBy, $note): StockTransaction {
            $inventory = Inventory::whereBelongsTo($product)->lockForUpdate()->firstOrFail();
            $balance = $inventory->quantity + $quantity;

            throw_if($balance < 0, ValidationException::withMessages([
                'quantity' => sprintf('Only %s of %s left in stock.', $inventory->quantity, $product->name),
            ]));

            $inventory->quantity = $balance;

            if ($type === StockTransactionType::Purchase) {
                $inventory->last_restocked_at = now();
            }

            $inventory->save();

            $transaction = $product->stockTransactions()->create([
                'type'            => $type,
                'quantity'        => $quantity,
                'balance_after'   => $balance,
                'source_type'     => $source?->getMorphClass(),
                'source_id'       => $source?->getKey(),
                'note'            => $note,
                'performed_by_id' => $performedBy?->id,
            ]);

            $this->syncLowStockAlert($product, $inventory, $performedBy);

            return $transaction;
        });
    }

    private function syncLowStockAlert(Product $product, Inventory $inventory, ?User $performedBy): void
    {
        $openAlert = $product->alerts()->open()->latest('id')->first();
        $expectedType = match (true) {
            $inventory->quantity <= 0 => AlertType::OutOfStock,
            $inventory->isLowStock()  => AlertType::LowStock,
            default                   => null,
        };

        if ($openAlert?->type === $expectedType) {
            return;
        }

        $openAlert?->update([
            'resolved_at'    => now(),
            'resolved_by_id' => $performedBy?->id,
        ]);

        if ($expectedType instanceof AlertType) {
            $product->alerts()->create([
                'type'              => $expectedType,
                'quantity_at_alert' => $inventory->quantity,
            ]);

            $this->notifyStockTeam($product, $expectedType, $inventory->quantity);
        }
    }

    private function notifyStockTeam(Product $product, AlertType $type, int $quantity): void
    {
        $stockTeam = User::where('is_active', true)
            ->whereIn('role', [UserRole::Admin, UserRole::Manager, UserRole::Storekeeper])
            ->get();

        $notification = FilamentNotification::make()
            ->title(sprintf('%s: %s', $product->name, $type->getLabel()))
            ->body($type === AlertType::OutOfStock ? 'No units left on the shelf.' : sprintf('Only %d left. Time to reorder.', $quantity))
            ->status($type === AlertType::OutOfStock ? 'danger' : 'warning')
            ->actions([
                Action::make('view')
                    ->url(ProductResource::getUrl('view', ['record' => $product], panel: 'admin'))
                    ->markAsRead(),
            ]);

        Notification::sendNow($stockTeam, $notification->toDatabase());
    }
}
