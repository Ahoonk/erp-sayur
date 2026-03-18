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
            $table->foreignUuid('master_product_id')->after('id')->nullable()->constrained('master_products')->cascadeOnDelete();

            // Drop redundant columns moved to master_products
            $table->dropForeign(['brand_id']);
            $table->dropColumn('brand_id');
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
            // Explicitly remove index first for SQLite compatibility when dropping column.
            $table->dropIndex('products_nama_index');
            $table->dropColumn('nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['master_product_id']);
            $table->dropColumn('master_product_id');

            $table->string('nama')->after('barcode');
            $table->foreignUuid('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignUuid('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->index('nama');
        });
    }
};
