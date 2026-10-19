<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PermohonanBarangMasuk extends Model
{
    use HasFactory;

    protected $table = 't_permohonan_barang_masuk';

    protected $fillable = [
        'kd_barang',
        'tgl_permohonan',
        'nm_barang',
        'nama_pic',
        'divisi',
        'lokasi_penyimpanan',
        'kondisi_barang',
        'qty_barang',
        'asal_barang',
        'bast',
        'foto',
        'status_approved',
        'keterangan',
        'user_created_by',
        'attribute1',
        'attribute2',
        'attribute3',
        'attribute4',
    ];

    protected $casts = [
        'tgl_permohonan' => 'date:Y-m-d',
        'qty_barang' => 'integer',
    ];

    public function approvalLogs(): HasMany
    {
        return $this->hasMany(PermohonanBarangMasukLog::class, 'permohonan_barang_masuk_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_created_by');
    }

    public function outgoingRequest(): HasOne
    {
        return $this->hasOne(PermohonanBarangOut::class, 'permohonan_barang_masuk_id');
    }

    public function isWaitingForSupervisorApproval(): bool
    {
        $logs = $this->approvalLogs;
        $latestStageOneDecision = $logs
            ->where('approval_stage', 1)
            ->whereIn('action', ['APPROVED', 'REJECTED'])
            ->last();
        $latestStageTwoDecision = $logs
            ->where('approval_stage', 2)
            ->whereIn('action', ['APPROVED', 'REJECTED'])
            ->last();

        return $this->status_approved === 'APPROVED_2'
            && $latestStageOneDecision?->action === 'APPROVED'
            && (! $latestStageTwoDecision || $latestStageOneDecision->id > $latestStageTwoDecision->id);
    }

    public function isFullyApproved(): bool
    {
        $logs = $this->approvalLogs;
        $latestStageOneDecision = $logs
            ->where('approval_stage', 1)
            ->whereIn('action', ['APPROVED', 'REJECTED'])
            ->last();
        $latestStageTwoDecision = $logs
            ->where('approval_stage', 2)
            ->whereIn('action', ['APPROVED', 'REJECTED'])
            ->last();

        return $this->status_approved === 'APPROVED_2'
            && $latestStageOneDecision?->action === 'APPROVED'
            && $latestStageTwoDecision?->action === 'APPROVED'
            && $latestStageTwoDecision->id > $latestStageOneDecision->id;
    }
}
