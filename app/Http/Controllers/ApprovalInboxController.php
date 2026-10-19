<?php

namespace App\Http\Controllers;

use App\Models\PermohonanBarangMasuk;
use App\Models\PermohonanBarangOut;
use Illuminate\Http\Request;

class ApprovalInboxController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->user()->roles;

        if ($role === 'admin') {
            $permohonanMasuk = PermohonanBarangMasuk::with(['approvalLogs', 'creator'])
                ->where('status_approved', 'APPROVED_1')
                ->orderBy('created_at')
                ->get();

            return view('pages.approval-inbox.index', [
                'inboxType' => 'incoming',
                'inboxTitle' => 'Pesan Masuk - Persetujuan Admin',
                'permohonanMasuk' => $permohonanMasuk,
                'permohonanOut' => collect(),
                'stage' => 1,
            ]);
        }

        if ($role === 'sepervisior') {
            $permohonanMasuk = PermohonanBarangMasuk::with(['approvalLogs', 'creator'])
                ->where('status_approved', 'APPROVED_2')
                ->orderBy('created_at')
                ->get()
                ->filter(fn (PermohonanBarangMasuk $item) => $item->isWaitingForSupervisorApproval());

            $permohonanOut = PermohonanBarangOut::with(['approvalLogs', 'permohonanBarangMasuk'])
                ->where('status_approved', 'APPROVED_1')
                ->orderBy('created_at')
                ->get()
                ->filter(fn (PermohonanBarangOut $item) => $item->isWaitingForSupervisorApproval());

            return view('pages.approval-inbox.index', [
                'inboxType' => 'supervisor',
                'inboxTitle' => 'Pesan Masuk - Persetujuan Supervisor',
                'permohonanMasuk' => $permohonanMasuk,
                'permohonanOut' => $permohonanOut,
                'stage' => 2,
            ]);
        }

        abort_unless($role === 'petugas', 403);

        $permohonanOut = PermohonanBarangOut::with(['approvalLogs', 'permohonanBarangMasuk'])
            ->whereIn('status_approved', ['DRAFT', 'APPROVED_2'])
            ->orderBy('created_at')
            ->get()
            ->filter(fn (PermohonanBarangOut $item) => $item->isWaitingForPetugasApproval());

        return view('pages.approval-inbox.index', [
            'inboxType' => 'outgoing',
            'inboxTitle' => 'Pesan Masuk - Persetujuan Pengeluaran Petugas',
            'permohonanMasuk' => collect(),
            'permohonanOut' => $permohonanOut,
            'stage' => 4,
        ]);
    }
}
