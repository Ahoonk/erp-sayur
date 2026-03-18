<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory, UUID;

    protected $fillable = [
        'purchase_id',
        'katalog_barang_id',
        'qty',
        'harga_beli',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'qty'        => 'decimal:3',
            'harga_beli' => 'decimal:2',
            'subtotal'   => 'decimal:2',
        ];
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function katalogBarang()
    {
        return $this->belongsTo(KatalogBarang::class);
    }

    public function stockBatch()
    {
        return $this->hasOne(StockBatch::class);
    }
}
