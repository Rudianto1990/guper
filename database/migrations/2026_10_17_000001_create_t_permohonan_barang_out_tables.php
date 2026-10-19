<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_permohonan_barang_out', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kd_barang_out', 16)->unique();
            $table->date('tgl_permohonan_out')->nullable();
            $table->string('kd_barang', 16);
            $table->date('tgl_permohonan')->nullable();
            $table->string('nm_barang')->nullable();
            $table->string('nama_pic')->nullable();
            $table->string('divisi')->nullable();
            $table->string('lokasi_penyimpanan')->nullable();
            $table->enum('kondisi_barang', ['BAIK', 'RUSAK'])->nullable();
            $table->unsignedInteger('qty_barang')->nullable();
            $table->string('asal_barang')->nullable();
            $table->string('bast')->nullable();
            $table->string('foto')->nullable();
            $table->enum('status_approved', ['DRAFT', 'APPROVED_1', 'APPROVED_2'])->default('DRAFT');
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('user_created_by')->nullable();
            $table->unsignedInteger('permohonan_barang_masuk_id')->unique();
            $table->string('attribute1')->nullable();
            $table->string('attribute2')->nullable();
            $table->string('attribute3')->nullable();
            $table->string('attribute4')->nullable();
            $table->timestamps();
        });

        Schema::create('t_permohonan_barang_out_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('permohonan_barang_out_id');
            $table->unsignedTinyInteger('approval_stage')->default(4);
            $table->enum('action', ['REQUESTED', 'APPROVED', 'REJECTED']);
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('permohonan_barang_out_id', 'pbo_logs_request_idx');
            $table->foreign('permohonan_barang_out_id', 'pbo_logs_request_fk')
                ->references('id')
                ->on('t_permohonan_barang_out')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_permohonan_barang_out_logs');
        Schema::dropIfExists('t_permohonan_barang_out');
    }
};
