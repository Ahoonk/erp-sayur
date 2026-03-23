<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'product_batch_id',
        'movement_type',
        'movement_code',
        'reference_type',
        'reference_id',
        'qty_in',
        'qty_out',
        'unit_cost',
        'notes',
        'moved_at',
    ];

    protected $casts = [
        'qty_in' => 'decimal:2',
        'qty_out' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'moved_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }
}
