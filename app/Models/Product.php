<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'unit_id',
        'sku',
        'name',
        'minimum_stock',
        'is_active',
        'stock_qty',
        'stock_value',
        'average_cost',
        'selling_price',
    ];

    protected $casts = [
        'minimum_stock' => 'decimal:2',
        'is_active' => 'boolean',
        'stock_qty' => 'decimal:3',
        'stock_value' => 'decimal:4',
        'average_cost' => 'decimal:4',
        'selling_price' => 'decimal:4',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}