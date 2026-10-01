<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductUnit;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'supplier_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'description',
        'unit',
        'cost_price',
        'selling_price',
        'expiry_date',
        'image_path',
        'is_active',
        'is_featured',
    ];

    public static function generateBarcode(): string
    {
        do {
            $digits = '429'.str((string) random_int(0, 999_999_999))->padLeft(9, '0');
            $barcode = $digits.self::ean13CheckDigit($digits);
        } while (self::where('barcode', $barcode)->exists());

        return $barcode;
    }

    public static function ean13CheckDigit(string $twelveDigits): int
    {
        $sum = collect(str_split($twelveDigits))
            ->map(fn (string $digit, int $position): int => (int) $digit * ($position % 2 === 0 ? 1 : 3))
            ->sum();

        return (10 - $sum % 10) % 10;
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return HasOne<Inventory, $this>
     */
    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    /**
     * @return HasMany<StockTransaction, $this>
     */
    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    /**
     * @return HasMany<SaleItem, $this>
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * @return HasMany<PurchaseItem, $this>
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * @return HasMany<Alert, $this>
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * @param Builder<self> $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param Builder<self> $query
     */
    #[Scope]
    protected function lowStock(Builder $query): void
    {
        $query->whereHas('inventory', fn (Builder $inventory) => $inventory->whereColumn('quantity', '<=', 'reorder_level'));
    }

    protected static function booted(): void
    {
        static::created(fn (Product $product) => $product->inventory()->create());
    }

    protected function casts(): array
    {
        return [
            'unit'          => ProductUnit::class,
            'cost_price'    => 'decimal:2',
            'selling_price' => 'decimal:2',
            'expiry_date'   => 'date',
            'is_active'     => 'boolean',
            'is_featured'   => 'boolean',
        ];
    }
}
