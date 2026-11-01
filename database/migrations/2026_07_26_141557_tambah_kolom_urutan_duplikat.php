<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_anomali', function (Blueprint $table) {
            $table->unsignedTinyInteger('urutan_duplikat')->default(1)->after('nama_anomali_fasih');
        });

        Schema::table('data_anomali', function (Blueprint $table) {
            $table->dropUnique('data_anomali_kunci_unik_v2');
            $table->unique(['assignment_id', 'kategori', 'nama_anomali_fasih', 'urutan_duplikat'], 'data_anomali_kunci_unik_final');
        });
    }

    public function down(): void
    {
        Schema::table('data_anomali', function (Blueprint $table) {
            $table->dropUnique('data_anomali_kunci_unik_final');
            $table->unique(['assignment_id', 'kategori', 'nama_anomali_fasih'], 'data_anomali_kunci_unik_v2');
            $table->dropColumn('urutan_duplikat');
        });
    }
};