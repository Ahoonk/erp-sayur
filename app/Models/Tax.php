<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends Model
{
    use HasFactory, UUID, SoftDeletes;

    protected $fillable = ['nama', 'persentase', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return [
            'persentase' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeSearch($query, $search)
    {
        if (empty($search)) return $query;
        return $query->where('nama', 'like', '%' . $search . '%');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
