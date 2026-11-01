<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // tabel ini isinya hasil upload excel "Penugasan PJ" dari superadmin: siapa PJ yang
    // membawahi PML tertentu, per kecamatan. 1 baris = 1 kombinasi PML + Kecamatan,
    // karena 1 PML bisa punya beberapa kecamatan, dan 1 PJ bisa membawahi beberapa PML.
    //
    // Setiap kali file diupload ulang, seluruh isi tabel ini di-REPLACE TOTAL (lihat
    // MonitoringTindakLanjutController::unggahPj), jadi gak perlu kolom penanda "aktif/tidak"
    // atau relasi ke riwayat_unggah -- data di tabel ini selalu representasi upload terakhir.
    public function up(): void
    {
        Schema::create('penugasan_pj', function (Blueprint $table) {
            $table->id();
            $table->string('email_pml')->nullable();
            $table->string('pml');
            $table->string('email_pj')->nullable();
            $table->string('pj');
            $table->string('kecamatan');
            $table->timestamps();

            // dipakai buat pencocokan cepat ke data_anomali (lihat kunciNormalisasi()
            // di controller). Bukan unique constraint keras, karena kita gak bisa jamin
            // 100% file dari pusat gak pernah punya baris duplikat persis.
            $table->index(['pml', 'kecamatan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penugasan_pj');
    }
};