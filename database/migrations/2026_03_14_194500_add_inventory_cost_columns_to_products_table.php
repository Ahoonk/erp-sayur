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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('stock_qty', 18, 3)->default(0)->after('is_active');
            $table->decimal('stock_value', 18, 4)->default(0)->after('stock_qty');
            $table->decimal('average_cost', 18, 4)->default(0)->after('stock_value');
            $table->decimal('selling_price', 18, 4)->default(0)->after('average_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock_qty', 'stock_value', 'average_cost', 'selling_price']);
        });
    }
};