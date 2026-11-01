<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referensi_ppl_pml', function (Blueprint $table) {
            $table->id();
            $table->string('idsubsls', 20);
            $table->string('pml');
            $table->string('ppl');
            $table->string('nama_sls');
            $table->timestamps();

            $table->index('idsubsls');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referensi_ppl_pml');
    }
};