<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_anomali', function (Blueprint $table) {
            $table->id();

            // kunci unik dari fasih, dipakai buat cek data ini baru atau update pas unggah ulang
            $table->string('assignment_id')->unique();
            $table->enum('kategori', ['usaha', 'keluarga']);

            // kolom wilayah, urutannya ngikutin susunan excel
            $table->string('idsubsls', 20);
            $table->string('kode_provinsi', 5);
            $table->string('nama_provinsi');
            $table->string('kode_kabkota', 10);
            $table->string('nama_kabkota');
            $table->string('kode_kecamatan', 15);
            $table->string('nama_kecamatan');
            $table->string('kode_desa', 20);
            $table->string('nama_desa');
            $table->string('kode_sls', 10);
            $table->string('sub_sls', 5);

            // isinya nama kepala keluarga kalau kategori keluarga, atau nama usaha kalau kategori usaha
            $table->string('nama_entitas');
            $table->string('link_fasih');

            // teks mentah dari kolom nama anomali fasih, sebelum dipecah
            $table->string('nama_anomali_fasih')->nullable();
            $table->unsignedTinyInteger('nomor_anomali')->nullable();
            $table->string('deskripsi_anomali')->nullable();
            $table->string('status_dari_fasih')->nullable();

            $table->string('email_petugas_fasih')->nullable();
            $table->string('id_petugas_fasih')->nullable();

            // hasil lookup dari file referensi ppl pml
            $table->string('pml')->nullable();
            $table->string('ppl')->nullable();
            $table->string('nama_sls')->nullable();

            // kolom yang diisi manual lewat web
            $table->text('perlu_konfirm_petugas')->nullable();
            $table->text('tanggapan_petugas')->nullable();
            $table->enum('status_assignment', ['sudah', 'belum'])->default('belum');
            $table->text('penyelesaian_anomali')->nullable();
            $table->date('tanggal_respon')->nullable();

            // dipakai buat tandain data yang sudah gak muncul lagi di unggahan terbaru, tanpa dihapus
            $table->enum('status_di_fasih', ['aktif', 'tidak_muncul_lagi'])->default('aktif');

            // menampung kolom yang beda-beda antara usaha dan keluarga, misal jml ac, kbli, dan sejenisnya
            $table->json('data_spesifik')->nullable();

            $table->foreignId('riwayat_unggah_id')->nullable()->constrained('riwayat_unggah');

            $table->timestamps();

            $table->index('status_assignment');
            $table->index('kategori');
            $table->index(['pml', 'ppl']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_anomali');
    }
};
