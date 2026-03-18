<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesTransaction extends Model
{
    use HasFactory, UUID, SoftDeletes;

    protected $fillable = [
        'no_invoice',
        'tanggal',
        'pelanggan',
        'user_id',
        'sales_rep_id',
        'subtotal',
        'diskon_persen',
        'diskon_nominal',
        'tax_id',
        'tax_persen',
        'tax_nominal',
        'grand_total',
        'metode_pembayaran',
        'jumlah_bayar',
        'kembalian',
        'tipe',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'subtotal' => 'decimal:2',
            'diskon_persen' => 'decimal:2',
            'diskon_nominal' => 'decimal:2',
            'tax_persen' => 'decimal:2',
            'tax_nominal' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'jumlah_bayar' => 'decimal:2',
            'kembalian' => 'decimal:2',
        ];
    }

    public function scopeSearch($query, $search)
    {
        if (empty($search)) return $query;
        return $query->where(function ($q) use ($search) {
            $q->where('no_invoice', 'like', '%' . $search . '%')
                ->orWhere('pelanggan', 'like', '%' . $search . '%');
        });
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        if ($startDate) $query->where('tanggal', '>=', $startDate);
        if ($endDate) $query->where('tanggal', '<=', $endDate);
        return $query;
    }

    public function scopeTipe($query, $tipe)
    {
        if ($tipe) return $query->where('tipe', $tipe);
        return $query;
    }

    public function scopeUser($query, $userId)
    {
        if ($userId) return $query->where('user_id', $userId);
        return $query;
    }

    public function scopeSalesRep($query, $salesRepId)
    {
        if ($salesRepId) return $query->where('sales_rep_id', $salesRepId);
        return $query;
    }

    // === Relationships ===

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function salesRep()
    {
        return $this->belongsTo(SalesRep::class, 'sales_rep_id');
    }

    public function salesUser()
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function serviceOrder()
    {
        return $this->hasOne(ServiceOrder::class);
    }

    /**
     * Calculate totals from items, discount, and tax
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items()->sum('subtotal');

        // Check if we use percentage or nominal
        if ($this->diskon_persen > 0) {
            $diskonNominal = $subtotal * ($this->diskon_persen / 100);
        } else {
            $diskonNominal = $this->diskon_nominal;
        }

        $afterDiskon = $subtotal - $diskonNominal;

        $taxNominal = $afterDiskon * ($this->tax_persen / 100);
        $grandTotal = $afterDiskon + $taxNominal;

        $jumlahBayar = $this->jumlah_bayar;
        $kembalian = $jumlahBayar > $grandTotal ? $jumlahBayar - $grandTotal : 0;
        if ($this->metode_pembayaran !== 'cash') {
            $jumlahBayar = $grandTotal;
            $kembalian = 0;
        }

        $this->update([
            'subtotal' => $subtotal,
            'diskon_nominal' => $diskonNominal,
            'tax_nominal' => $taxNominal,
            'grand_total' => $grandTotal,
            'jumlah_bayar' => $jumlahBayar,
            'kembalian' => $kembalian,
        ]);
    }
}
