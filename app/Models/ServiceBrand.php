<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $nama
 */
class ServiceBrand extends Model
{
    use HasFactory, UUID, SoftDeletes;

    protected $fillable = ['nama'];

    public function scopeSearch($query, $search)
    {
        if (empty($search)) return $query;
        return $query->where('nama', 'like', '%' . $search . '%');
    }

    public function serviceOrders()
    {
        return $this->hasMany(ServiceOrder::class);
    }
}
