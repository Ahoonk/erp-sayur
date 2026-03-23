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
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number');
            $table->date('expired_date')->nullable();
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('quantity', 12, 2);
            $table->decimal('remaining_quantity', 12, 2);
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'batch_number']);
            $table->index(['product_id', 'expired_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};