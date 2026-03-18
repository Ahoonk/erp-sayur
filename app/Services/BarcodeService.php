<?php

namespace App\Services;

use App\Models\Product;

class BarcodeService
{
    /**
     * Generate a unique barcode for a product.
     * Format: BG-YYYYMMDD-XXXXX (e.g., BG-20260225-00001)
     *
     * Uses withTrashed() to include soft-deleted products,
     * preventing duplicate barcode generation.
     */
    public function generate(): string
    {
        $prefix = 'BRYN';

        $latest = \Illuminate\Support\Facades\DB::table('products')
            ->where('barcode', 'like', "{$prefix}%")
            ->whereRaw('LENGTH(barcode) <= 8')
            ->selectRaw('CAST(SUBSTRING(barcode, 3) AS UNSIGNED) as num')
            ->orderByDesc('num')
            ->first();

        $nextNumber = $latest ? $latest->num + 1 : 1;
        $barcode = sprintf('%s%05d', $prefix, $nextNumber);

        // Safety check: ensure the barcode doesn't exist
        while (Product::withTrashed()->where('barcode', $barcode)->exists()) {
            $nextNumber++;
            $barcode = sprintf('%s%05d', $prefix, $nextNumber);
        }

        return $barcode;
    }
}
