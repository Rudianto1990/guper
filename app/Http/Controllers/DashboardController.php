<?php

namespace App\Http\Controllers;

use App\Models\PermohonanBarangMasuk;
use App\Models\PermohonanBarangOut;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        return view('pages.dashboard.index', [
            'months' => collect(range(0, 11))
                ->map(fn (int $offset) => Carbon::now()->startOfMonth()->subMonths($offset))
                ->mapWithKeys(fn (Carbon $month) => [
                    $month->format('Y-m') => $month->locale('id')->translatedFormat('F Y'),
                ]),
            'selectedMonth' => now()->format('Y-m'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $selectedMonth = isset($validated['month'])
            ? Carbon::createFromFormat('!Y-m-d', $validated['month'].'-01')
            : now()->startOfMonth();
        $role = $request->user()->roles;
        $scopeToOwner = $role === 'user'
            || ! in_array($role, ['admin', 'sepervisior', 'petugas'], true);
        $userId = $request->user()->getAuthIdentifier();

        $incoming = PermohonanBarangMasuk::with('approvalLogs')
            ->when($scopeToOwner, fn ($query) => $query->where('user_created_by', $userId))
            ->get();
        $outgoing = PermohonanBarangOut::with(['approvalLogs', 'permohonanBarangMasuk'])
            ->when($scopeToOwner, fn ($query) => $query->whereHas(
                'permohonanBarangMasuk',
                fn ($sourceQuery) => $sourceQuery->where('user_created_by', $userId)
            ))
            ->get();

        $approvedIncoming = $incoming->filter(
            fn (PermohonanBarangMasuk $item) => $item->isFullyApproved()
        )->values();
        $completedOutgoing = $outgoing->filter(
            fn (PermohonanBarangOut $item) => $this->isCompletedOutgoing($item)
        )->values();

        $outgoingByIncoming = $completedOutgoing
            ->groupBy('permohonan_barang_masuk_id')
            ->map(fn (Collection $items) => (int) $items->sum('qty_barang'));
        $remainingByIncoming = $approvedIncoming->mapWithKeys(function (PermohonanBarangMasuk $item) use ($outgoingByIncoming) {
            $remaining = max(0, (int) $item->qty_barang - (int) $outgoingByIncoming->get($item->id, 0));

            return [$item->id => $remaining];
        });
        $storedUnits = (int) $remainingByIncoming->sum();

        $weightedStorageDays = 0;
        $ageBuckets = [
            ['label' => '0-30 Hari', 'units' => 0],
            ['label' => '31-60 Hari', 'units' => 0],
            ['label' => '61-90 Hari', 'units' => 0],
            ['label' => '> 90 Hari', 'units' => 0],
        ];
        foreach ($approvedIncoming as $item) {
            $units = (int) $remainingByIncoming->get($item->id, 0);
            if ($units === 0) {
                continue;
            }

            $ageInDays = max(0, (int) $item->tgl_permohonan->startOfDay()->diffInDays(now()->startOfDay(), false));
            $weightedStorageDays += $ageInDays * $units;
            $bucketIndex = min(3, intdiv($ageInDays, 30));
            $ageBuckets[$bucketIndex]['units'] += $units;
        }

        $trend = collect(range(4, 0))
            ->map(fn (int $offset) => $selectedMonth->copy()->subMonths($offset))
            ->map(function (Carbon $month) use ($approvedIncoming, $completedOutgoing) {
                $incomingForMonth = $approvedIncoming->filter(
                    fn (PermohonanBarangMasuk $item) => $item->tgl_permohonan->format('Y-m') === $month->format('Y-m')
                );
                $outgoingForMonth = $completedOutgoing->filter(
                    fn (PermohonanBarangOut $item) => $item->tgl_permohonan_out
                        && $item->tgl_permohonan_out->format('Y-m') === $month->format('Y-m')
                );

                return [
                    'label' => $month->locale('id')->translatedFormat('M'),
                    'incoming' => (int) $incomingForMonth->sum('qty_barang'),
                    'outgoing' => (int) $outgoingForMonth->sum('qty_barang'),
                ];
            })->values();

        $periodIncoming = $approvedIncoming->filter(
            fn (PermohonanBarangMasuk $item) => $item->tgl_permohonan->format('Y-m') === $selectedMonth->format('Y-m')
        );
        $periodOutgoing = $completedOutgoing->filter(
            fn (PermohonanBarangOut $item) => $item->tgl_permohonan_out
                && $item->tgl_permohonan_out->format('Y-m') === $selectedMonth->format('Y-m')
        );
        $pending = $this->pendingCounts($role, $incoming, $outgoing);

        return response()->json([
            'data' => [
                'period' => [
                    'label' => $selectedMonth->locale('id')->translatedFormat('F Y'),
                    'incoming_units' => (int) $periodIncoming->sum('qty_barang'),
                    'outgoing_units' => (int) $periodOutgoing->sum('qty_barang'),
                ],
                'inventory' => [
                    'stored_units' => $storedUnits,
                    'mean_storage_days' => $storedUnits > 0
                        ? (int) round($weightedStorageDays / $storedUnits)
                        : 0,
                ],
                'trend' => $trend,
                'storage_duration' => $ageBuckets,
                'pending' => $pending,
            ],
        ]);
    }

    private function pendingCounts(string $role, Collection $incoming, Collection $outgoing): array
    {
        if ($role === 'admin') {
            $pendingIncoming = $incoming->where('status_approved', 'APPROVED_1')->count();
            $pendingOutgoing = $outgoing->filter(fn (PermohonanBarangOut $item) => ! $this->isCompletedOutgoing($item)
                && in_array($item->approvalLogs->last()?->action, ['REQUESTED', 'APPROVED'], true)
            )->count();
        } elseif ($role === 'sepervisior') {
            $pendingIncoming = $incoming->filter(
                fn (PermohonanBarangMasuk $item) => $item->isWaitingForSupervisorApproval()
            )->count();
            $pendingOutgoing = $outgoing->filter(
                fn (PermohonanBarangOut $item) => $item->isWaitingForSupervisorApproval()
            )->count();
        } elseif ($role === 'petugas') {
            $pendingIncoming = 0;
            $pendingOutgoing = $outgoing->filter(
                fn (PermohonanBarangOut $item) => $item->isWaitingForPetugasApproval()
            )->count();
        } else {
            $pendingIncoming = $incoming->filter(
                fn (PermohonanBarangMasuk $item) => $item->status_approved !== 'DRAFT'
                    && ! $item->isFullyApproved()
            )->count();
            $pendingOutgoing = $outgoing->filter(
                fn (PermohonanBarangOut $item) => $item->status_approved !== 'DRAFT'
                    && ! $this->isCompletedOutgoing($item)
            )->count();
        }

        return [
            'incoming' => $pendingIncoming,
            'outgoing' => $pendingOutgoing,
            'total' => $pendingIncoming + $pendingOutgoing,
        ];
    }

    private function isCompletedOutgoing(PermohonanBarangOut $item): bool
    {
        $lastLog = $item->approvalLogs->last();

        return $item->status_approved === 'APPROVED_2'
            && $lastLog?->approval_stage === 4
            && $lastLog->action === 'APPROVED';
    }
}
