<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->enum('metode_pembayaran', ['cash', 'transfer', 'qris'])->default('cash')->after('grand_total');
            $table->decimal('jumlah_bayar', 15, 2)->default(0)->after('metode_pembayaran');
            $table->decimal('kembalian', 15, 2)->default(0)->after('jumlah_bayar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropColumn(['metode_pembayaran', 'jumlah_bayar', 'kembalian']);
        });
    }
};
