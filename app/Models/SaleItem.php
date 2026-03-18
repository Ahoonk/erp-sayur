<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory, UUID;

    protected $fillable = [
        'sales_transaction_id',
        'product_id',
        'qty',
        'harga_satuan',
        'subtotal',
        'hpp_total',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'harga_satuan' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'hpp_total' => 'decimal:2',
        ];
    }

    // === Relationships ===

    public function salesTransaction()
    {
        return $this->belongsTo(SalesTransaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function allocations()
    {
        return $this->hasMany(SaleItemAllocation::class);
    }
}
