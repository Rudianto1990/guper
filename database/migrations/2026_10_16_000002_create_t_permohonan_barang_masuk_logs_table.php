<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 't_permohonan_barang_masuk_logs';
        $indexName = 'pbm_logs_request_idx';
        $foreignKeyName = 'pbm_logs_request_fk';

        if (Schema::hasTable($tableName)) {
            $indexExists = DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', $tableName)
                ->where('INDEX_NAME', $indexName)
                ->exists();
            $foreignKeyExists = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', $tableName)
                ->where('CONSTRAINT_NAME', $foreignKeyName)
                ->exists();

            if (! $foreignKeyExists) {
                Schema::table($tableName, function (Blueprint $table) use ($indexExists, $indexName, $foreignKeyName) {
                    $table->unsignedInteger('permohonan_barang_masuk_id')->change();
                    if (! $indexExists) {
                        $table->index('permohonan_barang_masuk_id', $indexName);
                    }
                    $table->foreign('permohonan_barang_masuk_id', $foreignKeyName)
                        ->references('id')
                        ->on('t_permohonan_barang_masuk')
                        ->cascadeOnDelete();
                });
            }

            return;
        }

        Schema::create($tableName, function (Blueprint $table) use ($indexName, $foreignKeyName) {
            $table->id();
            $table->unsignedInteger('permohonan_barang_masuk_id');
            $table->index('permohonan_barang_masuk_id', $indexName);
            $table->foreign('permohonan_barang_masuk_id', $foreignKeyName)
                ->references('id')
                ->on('t_permohonan_barang_masuk')
                ->cascadeOnDelete();
            $table->enum('action', ['SUBMITTED', 'APPROVED', 'REJECTED']);
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_permohonan_barang_masuk_logs');
    }
};
