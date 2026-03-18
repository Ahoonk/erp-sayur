<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('no_invoice')->unique();
            $table->date('tanggal');
            $table->string('pelanggan')->nullable();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete(); // kasir
            $table->foreignUuid('sales_rep_id')->nullable()->constrained('sales_reps')->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('diskon_persen', 5, 2)->default(0);
            $table->decimal('diskon_nominal', 15, 2)->default(0);
            $table->foreignUuid('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->decimal('tax_persen', 5, 2)->default(0);
            $table->decimal('tax_nominal', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->enum('tipe', ['penjualan', 'service'])->default('penjualan');
            $table->timestamps();
            $table->softDeletes();

            $table->index('no_invoice');
            $table->index('tanggal');
            $table->index('tipe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_transactions');
    }
};
