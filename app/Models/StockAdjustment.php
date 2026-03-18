<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory, UUID;

    protected $table = 'stock_adjustments';

    protected $fillable = [
        'katalog_barang_id',
        'tanggal',
        'reason',
        'qty',
        'keterangan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
            'tanggal' => 'date',
        ];
    }

    public function katalogBarang()
    {
        return $this->belongsTo(KatalogBarang::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
