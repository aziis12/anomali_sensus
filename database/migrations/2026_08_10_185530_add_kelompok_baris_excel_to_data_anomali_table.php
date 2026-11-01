<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_anomali', function (Blueprint $table) {
            // Kunci grup TAMPILAN: 1 nilai unik per BARIS EXCEL ASLI (bukan per link_fasih).
            // Format: "{link_fasih}|{urutan ke berapa baris ini di antara baris-baris excel
            // yang berbagi link_fasih yang sama}".
            //
            // Contoh kasus "KASANAH" (1 link_fasih muncul di 2 baris excel berbeda):
            //   - baris excel ke-6   -> "https://fasih.../xxxx|1"
            //   - baris excel ke-198 -> "https://fasih.../xxxx|2"
            //
            // Dengan kunci ini, keduanya tetap tercatat sebagai 1 link_fasih yang sama
            // (dipakai untuk badge/link "Link Fasih"), TAPI tampil sebagai 2 kartu
            // terpisah di halaman Data Anomali, masing-masing dengan form tanggapan sendiri
            // -- sesuai jumlah baris excel aslinya (supaya jumlah kartu = jumlah baris excel).
            $table->string('kelompok_baris_excel')->nullable()->after('link_fasih');
            $table->index('kelompok_baris_excel');
        });
    }

    public function down(): void
    {
        Schema::table('data_anomali', function (Blueprint $table) {
            $table->dropIndex(['kelompok_baris_excel']);
            $table->dropColumn('kelompok_baris_excel');
        });
    }
};