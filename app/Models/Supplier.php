<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, UUID, SoftDeletes;

    protected $fillable = ['nama', 'phone', 'email', 'alamat'];

    public function scopeSearch($query, $search)
    {
        if (empty($search)) return $query;
        return $query->where(function ($q) use ($search) {
            $q->where('nama', 'like', '%' . $search . '%')
                ->orWhere('phone', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%');
        });
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function products()
    {
        return $this->hasManyThrough(Product::class, Purchase::class);
    }
}
