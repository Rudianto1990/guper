<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermohonanBarangOutLog extends Model
{
    use HasFactory;

    protected $table = 't_permohonan_barang_out_logs';

    public $timestamps = false;

    protected $fillable = [
        'permohonan_barang_out_id',
        'approval_stage',
        'action',
        'comment',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'approval_stage' => 'integer',
        'created_at' => 'datetime',
    ];

    public function permohonanBarangOut(): BelongsTo
    {
        return $this->belongsTo(PermohonanBarangOut::class, 'permohonan_barang_out_id');
    }
}
