<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_anomali', function (Blueprint $table) {
            // assignment_id sendirian ternyata gak unik, karena 1 assignment bisa punya beberapa jenis anomali sekaligus
            $table->dropUnique(['assignment_id']);
            $table->unique(['assignment_id', 'nomor_anomali'], 'data_anomali_assignment_nomor_unique');
        });
    }

    public function down(): void
    {
        Schema::table('data_anomali', function (Blueprint $table) {
            $table->dropUnique('data_anomali_assignment_nomor_unique');
            $table->unique('assignment_id');
        });
    }
};