<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riwayat_unggah', function (Blueprint $table) {
            // snapshot kondisi DataAnomali tepat setelah upload ini selesai diproses,
            // dipakai buat bandingin "kemarin berapa, sekarang berapa" di dashboard monitoring
            $table->unsignedInteger('total_data_saat_ini')->nullable()->after('jumlah_selesai_di_fasih');
            $table->json('breakdown_tindak_lanjut')->nullable()->after('total_data_saat_ini');
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_unggah', function (Blueprint $table) {
            $table->dropColumn(['total_data_saat_ini', 'breakdown_tindak_lanjut']);
        });
    }
};