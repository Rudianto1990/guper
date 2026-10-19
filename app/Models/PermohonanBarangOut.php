<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermohonanBarangOut extends Model
{
    use HasFactory;

    protected $table = 't_permohonan_barang_out';

    protected $fillable = [
        'kd_barang_out',
        'tgl_permohonan_out',
        'kd_barang',
        'tgl_permohonan',
        'nm_barang',
        'nama_pic',
        'divisi',
        'lokasi_penyimpanan',
        'kondisi_barang',
        'qty_barang',
        'asal_barang',
        'tujuan_barang',
        'bast',
        'foto',
        'status_approved',
        'keterangan',
        'user_created_by',
        'permohonan_barang_masuk_id',
        'attribute1',
        'attribute2',
        'attribute3',
        'attribute4',
    ];

    protected $casts = [
        'tgl_permohonan_out' => 'date:Y-m-d',
        'tgl_permohonan' => 'date:Y-m-d',
        'qty_barang' => 'integer',
    ];

    public function approvalLogs(): HasMany
    {
        return $this->hasMany(PermohonanBarangOutLog::class, 'permohonan_barang_out_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function permohonanBarangMasuk(): BelongsTo
    {
        return $this->belongsTo(PermohonanBarangMasuk::class, 'permohonan_barang_masuk_id');
    }

    public function isWaitingForPetugasApproval(): bool
    {
        $logs = $this->approvalLogs;
        $lastLog = $logs->last();

        return $lastLog?->approval_stage === 4
            && $lastLog->action === 'REQUESTED'
            && (
                $logs->contains(fn ($log) => $log->approval_stage === 3 && $log->action === 'APPROVED')
                || ($this->status_approved === 'DRAFT'
                    && ! $logs->contains(fn ($log) => $log->approval_stage === 3))
            );
    }

    public function isWaitingForSupervisorApproval(): bool
    {
        return $this->status_approved === 'APPROVED_1'
            && $this->approvalLogs->last()?->approval_stage === 3
            && $this->approvalLogs->last()?->action === 'REQUESTED';
    }

    public function isDraftOrRejected(): bool
    {
        return $this->status_approved === 'DRAFT'
            && in_array($this->approvalLogs->last()?->action, [null, 'REJECTED'], true);
    }
}
