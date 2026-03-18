<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('UPDATE products p JOIN master_products mp ON mp.id = p.master_product_id SET p.unit_id = mp.unit_id WHERE p.unit_id IS NULL');

        Schema::table('master_products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['grade_id']);

            $table->dropColumn([
                'category_id',
                'unit_id',
                'grade_id',
                'identifier_type',
                'foto',
                'keterangan',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $table->foreignUuid('category_id')->nullable()->after('brand_id')->constrained('categories')->nullOnDelete();
            $table->foreignUuid('unit_id')->nullable()->after('category_id')->constrained('units')->nullOnDelete();
            $table->uuid('grade_id')->nullable()->after('unit_id');
            $table->foreign('grade_id')->references('id')->on('grades')->nullOnDelete();
            $table->string('identifier_type', 20)->default('none')->after('grade_id');
            $table->string('foto')->nullable()->after('identifier_type');
            $table->text('keterangan')->nullable()->after('foto');
        });
    }
};
