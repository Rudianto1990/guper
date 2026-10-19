<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('t_permohonan_barang_masuk_logs', 'approval_stage')) {
            Schema::table('t_permohonan_barang_masuk_logs', function (Blueprint $table) {
                $table->unsignedTinyInteger('approval_stage')->nullable()->after('permohonan_barang_masuk_id');
            });
        }

        DB::table('t_permohonan_barang_masuk_logs')
            ->whereNull('approval_stage')
            ->whereIn('action', ['SUBMITTED', 'APPROVED', 'REJECTED'])
            ->update(['approval_stage' => 1]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('t_permohonan_barang_masuk_logs', 'approval_stage')) {
            Schema::table('t_permohonan_barang_masuk_logs', function (Blueprint $table) {
                $table->dropColumn('approval_stage');
            });
        }
    }
};
