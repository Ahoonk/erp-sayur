<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->string('no_service')->nullable()->unique()->after('id');
            $table->index('no_service');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropIndex(['no_service']);
            $table->dropUnique(['no_service']);
            $table->dropColumn('no_service');
        });
    }
};
