<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_permohonan_barang_masuk', function (Blueprint $table) {
            $table->date('tgl_permohonan')->nullable()->change();
            $table->string('nm_barang')->nullable()->change();
            $table->string('nama_pic')->nullable()->change();
            $table->string('divisi')->nullable()->change();
            $table->string('lokasi_penyimpanan')->nullable()->change();
            $table->enum('kondisi_barang', ['BAIK', 'RUSAK'])->nullable()->change();
            $table->unsignedInteger('qty_barang')->nullable()->change();
            $table->string('asal_barang')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('t_permohonan_barang_masuk', function (Blueprint $table) {
            $table->date('tgl_permohonan')->nullable(false)->change();
            $table->string('nm_barang')->nullable(false)->change();
            $table->string('nama_pic')->nullable(false)->change();
            $table->string('divisi')->nullable(false)->change();
            $table->string('lokasi_penyimpanan')->nullable(false)->change();
            $table->enum('kondisi_barang', ['BAIK', 'RUSAK'])->nullable(false)->change();
            $table->unsignedInteger('qty_barang')->nullable(false)->change();
            $table->string('asal_barang')->nullable(false)->change();
        });
    }
};
