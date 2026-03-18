<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    use HasFactory, UUID;

    protected $table = 'stock_batches';

    protected $fillable = [
        'katalog_barang_id',
        'purchase_item_id',
        'qty_awal',
        'qty_sisa',
        'harga_beli',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'qty_awal'    => 'decimal:3',
            'qty_sisa'    => 'decimal:3',
            'harga_beli'  => 'decimal:2',
            'expired_at'  => 'date',
        ];
    }

    public function katalogBarang()
    {
        return $this->belongsTo(KatalogBarang::class);
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class);
    }
}
