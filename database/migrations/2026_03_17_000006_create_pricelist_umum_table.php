<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricelist_umum', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->smallInteger('tahun');
            $table->tinyInteger('bulan');   // 1-12
            $table->tinyInteger('periode'); // 1 = tgl 1-15, 2 = tgl 16-akhir
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['tahun', 'bulan', 'periode']); // one pricelist per period
        });

        Schema::create('pricelist_umum_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pricelist_umum_id')->constrained('pricelist_umum')->cascadeOnDelete();
            $table->foreignUuid('katalog_barang_id')->constrained('katalog_barang')->restrictOnDelete();
            $table->decimal('modal_rata_rata', 15, 2)->default(0); // snapshot of avg cost at time of pricelist
            $table->decimal('persentase', 8, 2)->nullable();        // markup %
            $table->decimal('harga_jual', 15, 2);
            $table->timestamps();

            $table->unique(['pricelist_umum_id', 'katalog_barang_id'], 'pl_umum_items_unique');
            $table->index('katalog_barang_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricelist_umum_items');
        Schema::dropIfExists('pricelist_umum');
    }
};
