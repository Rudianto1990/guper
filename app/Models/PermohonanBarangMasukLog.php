<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermohonanBarangMasukLog extends Model
{
    use HasFactory;

    protected $table = 't_permohonan_barang_masuk_logs';

    public $timestamps = false;

    protected $fillable = [
        'permohonan_barang_masuk_id',
        'approval_stage',
        'action',
        'comment',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'approval_stage' => 'integer',
    ];

    public function permohonanBarangMasuk(): BelongsTo
    {
        return $this->belongsTo(PermohonanBarangMasuk::class, 'permohonan_barang_masuk_id');
    }
}
