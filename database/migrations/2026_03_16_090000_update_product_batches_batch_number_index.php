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
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropUnique('product_batches_product_id_batch_number_unique');
            $table->index(['product_id', 'batch_number'], 'product_batches_product_id_batch_number_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropIndex('product_batches_product_id_batch_number_idx');
            $table->unique(['product_id', 'batch_number']);
        });
    }
};
