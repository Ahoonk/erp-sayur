<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricelist_mitra', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mitra_id')->constrained('mitra')->restrictOnDelete();
            $table->smallInteger('tahun');
            $table->tinyInteger('bulan');
            $table->tinyInteger('periode'); // 1 = tgl 1-15, 2 = tgl 16-akhir
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['mitra_id', 'tahun', 'bulan', 'periode']);
        });

        Schema::create('pricelist_mitra_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pricelist_mitra_id')->constrained('pricelist_mitra')->cascadeOnDelete();
            $table->foreignUuid('katalog_barang_id')->constrained('katalog_barang')->restrictOnDelete();
            $table->decimal('modal_rata_rata', 15, 2)->default(0);
            $table->decimal('persentase', 8, 2)->nullable();
            $table->decimal('harga_jual', 15, 2);
            $table->timestamps();

            $table->unique(['pricelist_mitra_id', 'katalog_barang_id'], 'pl_mitra_items_unique');
            $table->index('katalog_barang_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricelist_mitra_items');
        Schema::dropIfExists('pricelist_mitra');
    }
};
