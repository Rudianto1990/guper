<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cctv', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id');
            $table->unsignedInteger('source_id')->nullable();
            $table->string('inventory_code')->nullable();
            $table->string('nama_camera', 100);
            $table->string('location', 100);
            $table->string('ip_address', 45);
            $table->string('type_camera', 50);
            $table->string('rtsp_url', 255);
            $table->string('latitude', 200);
            $table->string('longitude', 200);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->string('source_name')->nullable();
            $table->string('sub_division')->nullable();
            $table->string('category')->nullable();
            $table->string('jenis_kategori')->nullable();
            $table->string('merk')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('source_status', 50)->nullable();
            $table->date('reg_date')->nullable();
            $table->string('nvr', 50)->nullable();
            $table->string('nomor_urut', 50)->nullable();
            $table->string('link_img')->nullable();
            $table->string('source_model')->nullable();
            $table->dateTime('source_created_at')->nullable();
            $table->dateTime('source_updated_at')->nullable();

            $table->index('inventory_code');
            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cctv');
    }
};
