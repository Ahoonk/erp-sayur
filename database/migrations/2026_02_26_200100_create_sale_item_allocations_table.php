<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_item_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_item_id')->constrained('sale_items')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('qty');
            $table->decimal('harga_modal', 15, 2)->default(0);
            $table->decimal('subtotal_hpp', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['sale_item_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_item_allocations');
    }
};

