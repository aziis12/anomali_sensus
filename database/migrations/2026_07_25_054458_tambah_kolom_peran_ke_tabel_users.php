<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // peran menentukan hak akses di seluruh sistem
            $table->enum('peran', ['superadmin', 'pegawai', 'petugas'])->default('petugas')->after('email');

            // hanya diisi kalau peran petugas, dipakai buat mencocokkan data yang jadi tanggung jawabnya
            $table->enum('kategori_petugas', ['pml', 'ppl'])->nullable()->after('peran');

            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif')->after('kategori_petugas');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['peran', 'kategori_petugas', 'status']);
        });
    }
};