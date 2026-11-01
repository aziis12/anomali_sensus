<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('data_anomali', 'status_assignment_fasih')) {
            Schema::table('data_anomali', function (Blueprint $table) {
                $table->string('status_assignment_fasih')->default('APPROVED BY Pengawas')->after('urutan_duplikat');
            });
        }
    }

    public function down(): void
    {
        Schema::table('data_anomali', function (Blueprint $table) {
            $table->dropColumn('status_assignment_fasih');
        });
    }
};