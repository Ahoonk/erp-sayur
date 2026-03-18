<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, UUID, SoftDeletes;

    protected $fillable = [
        'master_product_id',
        'barcode',
        'grade_id',
        'unit_id',
        'imei1',
        'imei2',
        'harga_modal',
        'harga_jual',
        'stok',
        'keterangan',
        'foto',
    ];

    protected function casts(): array
    {
        return [
            'harga_modal' => 'decimal:2',
            'harga_jual' => 'decimal:2',
            'stok' => 'integer',
        ];
    }

    /**
     * Search by master name, barcode, imei1, or imei2
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) return $query;
        return $query->where(function ($q) use ($search) {
            $q->whereHas('masterProduct', function ($mq) use ($search) {
                $mq->where('nama', 'like', '%' . $search . '%');
            })
                ->orWhere('barcode', 'like', '%' . $search . '%')
                ->orWhere('imei1', 'like', '%' . $search . '%')
                ->orWhere('imei2', 'like', '%' . $search . '%');
        });
    }

    /**
     * Filter by exact barcode or IMEI (for scanner)
     */
    public function scopeScan($query, $code)
    {
        if (empty($code)) return $query;
        return $query->where(function ($q) use ($code) {
            $q->where('barcode', $code)
                ->orWhere('imei1', $code)
                ->orWhere('imei2', $code);
        });
    }

    /**
     * Filter products with stock > 0
     */
    public function scopeInStock($query)
    {
        return $query->where('stok', '>', 0);
    }

    // === Relationships ===

    public function masterProduct()
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function saleItemAllocations()
    {
        return $this->hasMany(SaleItemAllocation::class);
    }

    public function serviceParts()
    {
        return $this->hasMany(ServicePart::class);
    }

    // Helper methods to access master data easily
    public function getNamaAttribute()
    {
        return $this->masterProduct?->nama;
    }
}
