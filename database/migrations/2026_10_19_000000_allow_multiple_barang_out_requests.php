<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_permohonan_barang_out', function (Blueprint $table) {
            $table->dropUnique('t_permohonan_barang_out_permohonan_barang_masuk_id_unique');
            $table->index('permohonan_barang_masuk_id', 'pbo_source_idx');
            $table->string('tujuan_barang')->nullable()->after('asal_barang');
        });
    }

    public function down(): void
    {
        Schema::table('t_permohonan_barang_out', function (Blueprint $table) {
            $table->dropColumn('tujuan_barang');
            $table->dropIndex('pbo_source_idx');
            $table->unique('permohonan_barang_masuk_id');
        });
    }
};
