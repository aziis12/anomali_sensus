<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_anomali', function (Blueprint $table) {
            $table->string('status_assignment_fasih')->default(null)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('data_anomali', function (Blueprint $table) {
            $table->string('status_assignment_fasih')->default('APPROVED BY Pengawas')->change();
        });
    }
};