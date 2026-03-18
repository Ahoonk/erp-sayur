<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricelistMitraItem extends Model
{
    use HasFactory, UUID;

    protected $table = 'pricelist_mitra_items';

    protected $fillable = [
        'pricelist_mitra_id',
        'katalog_barang_id',
        'modal_rata_rata',
        'persentase',
        'harga_jual',
    ];

    protected function casts(): array
    {
        return [
            'modal_rata_rata' => 'decimal:2',
            'persentase'      => 'decimal:2',
            'harga_jual'      => 'decimal:2',
        ];
    }

    public function pricelistMitra()
    {
        return $this->belongsTo(PricelistMitra::class);
    }

    public function katalogBarang()
    {
        return $this->belongsTo(KatalogBarang::class);
    }
}
