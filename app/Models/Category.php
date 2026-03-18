<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, UUID, SoftDeletes;

    protected $fillable = ['nama', 'kode_prefix'];

    public function scopeSearch($query, $search)
    {
        if (empty($search)) return $query;
        return $query->where('nama', 'like', '%' . $search . '%');
    }

    public function katalogBarang()
    {
        return $this->hasMany(KatalogBarang::class);
    }
}
