<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $service_brand_id
 * @property string $merk_hp
 */
class ServiceOrder extends Model
{
    use HasFactory, UUID, SoftDeletes;

    protected $fillable = [
        'no_service',
        'sales_transaction_id',
        'technician_id',
        'service_brand_id',
        'nama_pelanggan',
        'no_hp_pelanggan',
        'merk_hp',
        'tipe_hp',
        'kerusakan',
        'imei_hp',
        'kelengkapan',
        'biaya_jasa',
        'status',
        'status_pengambilan',
        'tanggal_masuk',
        'tanggal_selesai',
        'catatan_teknisi',
    ];

    protected function casts(): array
    {
        return [
            'biaya_jasa' => 'decimal:2',
            'tanggal_masuk' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public function scopeSearch($query, $search)
    {
        if (empty($search)) return $query;
        return $query->where(function ($q) use ($search) {
            $q->where('no_service', 'like', '%' . $search . '%')
                ->orWhere('id', 'like', '%' . $search . '%')
                ->orWhere('nama_pelanggan', 'like', '%' . $search . '%')
                ->orWhere('merk_hp', 'like', '%' . $search . '%')
                ->orWhereHas('serviceBrand', function ($sq) use ($search) {
                    $sq->where('nama', 'like', '%' . $search . '%');
                })
                ->orWhere('tipe_hp', 'like', '%' . $search . '%')
                ->orWhere('no_hp_pelanggan', 'like', '%' . $search . '%')
                ->orWhere('imei_hp', 'like', '%' . $search . '%');
        });
    }

    public function scopeStatus($query, $status)
    {
        if ($status) return $query->where('status', $status);
        return $query;
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        if ($startDate) $query->where('tanggal_masuk', '>=', $startDate);
        if ($endDate) $query->where('tanggal_masuk', '<=', $endDate);
        return $query;
    }

    public function scopeStatusPengambilan($query, $status)
    {
        if ($status) return $query->where('status_pengambilan', $status);
        return $query;
    }

    // === Relationships ===

    public function salesTransaction()
    {
        return $this->belongsTo(SalesTransaction::class);
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function serviceBrand()
    {
        return $this->belongsTo(ServiceBrand::class, 'service_brand_id');
    }

    public function parts()
    {
        return $this->hasMany(ServicePart::class);
    }

    /**
     * Calculate total cost = biaya_jasa + sum of parts
     */
    public function getTotalBiayaAttribute(): float
    {
        return (float) $this->biaya_jasa + (float) $this->parts()->sum('subtotal');
    }
}
