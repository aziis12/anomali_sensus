<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riwayat_unggah', function (Blueprint $table) {
            $table->date('tanggal_data')->nullable()->after('diunggah_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_unggah', function (Blueprint $table) {
            $table->dropColumn('tanggal_data');
        });
    }
};