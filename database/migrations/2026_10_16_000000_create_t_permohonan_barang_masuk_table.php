<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_permohonan_barang_masuk', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kd_barang', 16)->unique();
            $table->date('tgl_permohonan');
            $table->string('nm_barang');
            $table->string('nama_pic');
            $table->string('divisi');
            $table->string('lokasi_penyimpanan');
            $table->enum('kondisi_barang', ['BAIK', 'RUSAK']);
            $table->unsignedInteger('qty_barang');
            $table->string('asal_barang');
            $table->string('bast')->nullable();
            $table->string('foto')->nullable();
            $table->enum('status_approved', ['DRAFT', 'APPROVED_1', 'APPROVED_2'])->default('DRAFT');
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('user_created_by')->nullable();
            $table->string('attribute1')->nullable();
            $table->string('attribute2')->nullable();
            $table->string('attribute3')->nullable();
            $table->string('attribute4')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_permohonan_barang_masuk');
    }
};
