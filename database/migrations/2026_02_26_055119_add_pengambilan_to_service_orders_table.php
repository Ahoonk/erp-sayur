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
        Schema::table('service_orders', function (Blueprint $table) {
            $table->enum('status_pengambilan', ['belum_diambil', 'sudah_diambil'])->default('belum_diambil')->after('status');
        });

        // Update data lama
        \Illuminate\Support\Facades\DB::table('service_orders')->where('status', 'diambil')->update([
            'status' => 'selesai',
            'status_pengambilan' => 'sudah_diambil'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn('status_pengambilan');
        });
    }
};
