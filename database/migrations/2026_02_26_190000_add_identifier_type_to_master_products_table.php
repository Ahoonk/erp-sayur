<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $table->string('identifier_type', 20)->default('none')->after('grade_id');
        });

        DB::table('master_products')
            ->whereNull('identifier_type')
            ->update(['identifier_type' => 'none']);
    }

    public function down(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $table->dropColumn('identifier_type');
        });
    }
};

