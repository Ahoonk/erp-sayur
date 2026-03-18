<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KatalogBarang extends Model
{
    use HasFactory, UUID, SoftDeletes;

    protected $table = 'katalog_barang';

    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'category_id',
        'unit_id',
    ];

    public function scopeSearch($query, $search)
    {
        if (empty($search)) return $query;
        return $query->where(function ($q) use ($search) {
            $q->where('kode_barang', 'like', '%' . $search . '%')
                ->orWhere('nama_barang', 'like', '%' . $search . '%')
                ->orWhereHas('category', fn($sq) => $sq->where('nama', 'like', '%' . $search . '%'));
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stockBatches()
    {
        return $this->hasMany(StockBatch::class);
    }

    /**
     * Get total remaining stock across all batches
     */
    public function getTotalStokAttribute(): float
    {
        return (float) $this->stockBatches()->sum('qty_sisa');
    }

    /**
     * Calculate weighted average cost from active batches
     */
    public function getModalRataRataAttribute(): float
    {
        $batches = $this->stockBatches()->where('qty_sisa', '>', 0)->get();
        $totalQty = $batches->sum('qty_sisa');
        if ($totalQty <= 0) return 0;
        $totalValue = $batches->sum(fn($b) => $b->qty_sisa * $b->harga_beli);
        return round($totalValue / $totalQty, 2);
    }

    /**
     * Generate next kode_barang for a given category prefix
     */
    public static function generateKode(string $prefix): string
    {
        $latest = static::withTrashed()
            ->where('kode_barang', 'like', $prefix . '%')
            ->orderByDesc('kode_barang')
            ->value('kode_barang');

        if ($latest) {
            $num = (int) substr($latest, strlen($prefix));
            $nextNum = $num + 1;
        } else {
            $nextNum = 1;
        }

        return $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    }
}
