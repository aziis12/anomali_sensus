<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_anomali', function (Blueprint $table) {
            $table->json('daftar_nomor_anomali')->nullable()->after('nomor_anomali');
        });
    }

    public function down(): void
    {
        Schema::table('data_anomali', function (Blueprint $table) {
            $table->dropColumn('daftar_nomor_anomali');
        });
    }
};