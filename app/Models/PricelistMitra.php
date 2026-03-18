<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricelistMitra extends Model
{
    use HasFactory, UUID;

    protected $table = 'pricelist_mitra';

    protected $fillable = [
        'mitra_id',
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

    public function mitra()
    {
        return $this->belongsTo(Mitra::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PricelistMitraItem::class);
    }

    public function getPeriodeLabelAttribute(): string
    {
        return $this->periode === 1 ? '1-15' : '16-Akhir';
    }
}
