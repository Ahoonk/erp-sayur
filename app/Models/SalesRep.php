<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesRep extends Model
{
    use HasFactory, UUID, SoftDeletes;

    protected $fillable = ['nama', 'no_hp', 'alamat'];

    public function scopeSearch($query, $search)
    {
        if (empty($search)) return $query;
        return $query->where(function ($q) use ($search) {
            $q->where('nama', 'like', '%' . $search . '%')
                ->orWhere('no_hp', 'like', '%' . $search . '%');
        });
    }

    public function salesTransactions()
    {
        return $this->hasMany(SalesTransaction::class, 'sales_rep_id');
    }
}
