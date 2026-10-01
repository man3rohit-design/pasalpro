<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'quantity',
        'reorder_level',
        'last_restocked_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'quantity'      => 0,
        'reorder_level' => 10,
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->reorder_level;
    }

    protected function casts(): array
    {
        return [
            'quantity'          => 'integer',
            'reorder_level'     => 'integer',
            'last_restocked_at' => 'datetime',
        ];
    }
}
