<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_unggah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diunggah_oleh')->constrained('users');
            $table->string('nama_file_usaha');
            $table->string('nama_file_keluarga');
            $table->unsignedInteger('jumlah_baru')->default(0);
            $table->unsignedInteger('jumlah_diperbarui')->default(0);
            $table->unsignedInteger('jumlah_selesai_di_fasih')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_unggah');
    }
};