<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudang_peralatans', function (Blueprint $table) {
            $table->id();
            $table->string('id_barang')->unique();
            $table->string('nama_barang');
            $table->string('foto')->nullable();
            $table->unsignedInteger('qty')->default(0);
            $table->unsignedSmallInteger('tahun')->nullable();
            $table->string('satuan');
            $table->string('lokasi');
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->string('gudang_area');
            $table->enum('kondisi', ['BAIK', 'RUSAK'])->default('BAIK');
            $table->text('mekanisme_penghapusan')->nullable();
            $table->text('alasan_hapus')->nullable();
            $table->text('tata_cara')->nullable();
            $table->text('url_cctv')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudang_peralatans');
    }
};