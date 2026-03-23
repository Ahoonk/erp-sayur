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
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('movement_code', 30)->default('in')->after('movement_type');
            $table->index(['movement_code', 'moved_at'], 'stock_movements_code_moved_at_idx');
        });

        DB::table('stock_movements')
            ->where('movement_type', 'out')
            ->update(['movement_code' => 'out']);

        DB::table('stock_movements')
            ->where('reference_type', 'shrinkage')
            ->update(['movement_code' => 'waste']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_code_moved_at_idx');
            $table->dropColumn('movement_code');
        });
    }
};
