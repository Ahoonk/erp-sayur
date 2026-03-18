<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sales_transaction_id')->nullable()->constrained('sales_transactions')->nullOnDelete();
            $table->string('nama_pelanggan');
            $table->string('no_hp_pelanggan')->nullable();
            $table->string('merk_hp');
            $table->string('tipe_hp');
            $table->text('kerusakan');
            $table->string('imei_hp')->nullable();
            $table->text('kelengkapan')->nullable();
            $table->decimal('biaya_jasa', 15, 2)->default(0);
            $table->enum('status', ['pending', 'dikerjakan', 'selesai', 'diambil', 'batal'])->default('pending');
            $table->date('tanggal_masuk');
            $table->date('tanggal_selesai')->nullable();
            $table->text('catatan_teknisi')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('tanggal_masuk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_orders');
    }
};
