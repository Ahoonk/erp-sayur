<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItemAllocation extends Model
{
    use HasFactory, UUID;

    protected $fillable = [
        'sale_item_id',
        'product_id',
        'qty',
        'harga_modal',
        'subtotal_hpp',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'harga_modal' => 'decimal:2',
            'subtotal_hpp' => 'decimal:2',
        ];
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

