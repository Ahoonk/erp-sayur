<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, UUID, SoftDeletes;

    protected $fillable = [
        'no_invoice',
        'tanggal',
        'supplier_id',
        'user_id',
        'total',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'total' => 'decimal:2',
        ];
    }

    public function scopeSearch($query, $search)
    {
        if (empty($search)) return $query;
        return $query->where(function ($q) use ($search) {
            $q->where('no_invoice', 'like', '%' . $search . '%')
                ->orWhereHas('supplier', fn($sq) => $sq->where('nama', 'like', '%' . $search . '%'));
        });
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        if ($startDate) $query->where('tanggal', '>=', $startDate);
        if ($endDate) $query->where('tanggal', '<=', $endDate);
        return $query;
    }

    // === Relationships ===

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Recalculate total from items
     */
    public function recalculateTotal(): void
    {
        $this->update([
            'total' => $this->items()->sum('subtotal'),
        ]);
    }
}
