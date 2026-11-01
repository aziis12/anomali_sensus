<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_anomali', function (Blueprint $table) {
            $table->dropUnique('data_anomali_kunci_unik');

            // nama_anomali_fasih dibatasi 191 karakter untuk index karena batas panjang index mysql
            $table->unique(['assignment_id', 'kategori', 'nama_anomali_fasih'], 'data_anomali_kunci_unik_v2');
        });
    }

    public function down(): void
    {
        Schema::table('data_anomali', function (Blueprint $table) {
            $table->dropUnique('data_anomali_kunci_unik_v2');
            $table->unique(['assignment_id', 'nomor_anomali', 'kategori'], 'data_anomali_kunci_unik');
        });
    }
};