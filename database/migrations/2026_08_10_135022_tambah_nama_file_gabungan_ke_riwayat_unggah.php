<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riwayat_unggah', function (Blueprint $table) {
            $table->string('nama_file_gabungan')->nullable()->after('nama_file_keluarga');
        });

        // format lama upload 2 file (usaha + keluarga) masih harus tetap bisa jalan,
        // jadi kolom lama TIDAK dihapus, cuma dibikin nullable karena upload format
        // baru (1 file gabungan) gak akan mengisi kolom ini.
        Schema::table('riwayat_unggah', function (Blueprint $table) {
            $table->string('nama_file_usaha')->nullable()->change();
            $table->string('nama_file_keluarga')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_unggah', function (Blueprint $table) {
            $table->dropColumn('nama_file_gabungan');
        });

        Schema::table('riwayat_unggah', function (Blueprint $table) {
            $table->string('nama_file_usaha')->nullable(false)->change();
            $table->string('nama_file_keluarga')->nullable(false)->change();
        });
    }
};