<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBatch extends Model
{
    protected $fillable = [
        'product_id',
        'batch_number',
        'expired_date',
        'purchase_price',
        'quantity',
        'remaining_quantity',
        'received_at',
    ];

    protected $casts = [
        'expired_date' => 'date',
        'received_at' => 'datetime',
        'purchase_price' => 'decimal:2',
        'quantity' => 'decimal:2',
        'remaining_quantity' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}