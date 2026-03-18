<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('katalog_barang_id')->constrained('katalog_barang')->restrictOnDelete();
            $table->foreignUuid('purchase_item_id')->constrained('purchase_items')->cascadeOnDelete();
            $table->decimal('qty_awal', 15, 3);        // initial quantity
            $table->decimal('qty_sisa', 15, 3);         // remaining quantity (for FIFO)
            $table->decimal('harga_beli', 15, 2);       // cost price for this batch
            $table->timestamps();

            $table->index('katalog_barang_id');
            $table->index(['katalog_barang_id', 'created_at']); // for FIFO ordering
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
