<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('katalog_barang', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode_barang', 10)->unique();
            $table->string('nama_barang');
            $table->foreignUuid('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignUuid('unit_id')->constrained('units')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('kode_barang');
            $table->index('nama_barang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('katalog_barang');
    }
};
