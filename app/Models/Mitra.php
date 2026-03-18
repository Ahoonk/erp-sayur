<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mitra extends Model
{
    use HasFactory, UUID, SoftDeletes;

    protected $table = 'mitra';

    protected $fillable = [
        'nama',
        'telepon',
        'alamat',
        'keterangan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeSearch($query, $search)
    {
        if (empty($search)) return $query;
        return $query->where(function ($q) use ($search) {
            $q->where('nama', 'like', '%' . $search . '%')
                ->orWhere('telepon', 'like', '%' . $search . '%');
        });
    }

    public function pricelistMitra()
    {
        return $this->hasMany(PricelistMitra::class);
    }
}
