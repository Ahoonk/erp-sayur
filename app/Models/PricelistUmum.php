<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricelistUmum extends Model
{
    use HasFactory, UUID;

    protected $table = 'pricelist_umum';

    protected $fillable = [
        'tahun',
        'bulan',
        'periode',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tahun'   => 'integer',
            'bulan'   => 'integer',
            'periode' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PricelistUmumItem::class);
    }

    public function getPeriodeLabelAttribute(): string
    {
        return $this->periode === 1 ? '1-15' : '16-Akhir';
    }
}
