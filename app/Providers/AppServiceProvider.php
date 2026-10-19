<?php


namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Models\Pengaturan;
use App\Models\PermohonanBarangMasuk;
use App\Models\PermohonanBarangOut;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer(['layouts.auth', 'layouts.main', 'partials.sidebar'], function ($view): void {
            $pengaturan = Schema::hasTable('pengaturans')
                ? Pengaturan::first()
                : null;

            $user = auth()->user();
            $approvalInboxCount = 0;

            if ($user && Schema::hasTable('t_permohonan_barang_masuk')) {
                if ($user->roles === 'admin') {
                    $approvalInboxCount = PermohonanBarangMasuk::where('status_approved', 'APPROVED_1')->count();
                } elseif ($user->roles === 'sepervisior' && Schema::hasTable('t_permohonan_barang_masuk_logs')) {
                    $incomingCount = PermohonanBarangMasuk::where('status_approved', 'APPROVED_2')
                        ->with('approvalLogs')
                        ->get()
                        ->filter(fn (PermohonanBarangMasuk $item) => $item->isWaitingForSupervisorApproval())
                        ->count();
                    $outgoingCount = Schema::hasTable('t_permohonan_barang_out_logs')
                        ? PermohonanBarangOut::with('approvalLogs')
                            ->get()
                            ->filter(fn (PermohonanBarangOut $item) => $item->isWaitingForSupervisorApproval())
                            ->count()
                        : 0;
                    $approvalInboxCount = $incomingCount + $outgoingCount;
                } elseif ($user->roles === 'petugas' && Schema::hasTable('t_permohonan_barang_out')) {
                    $approvalInboxCount = PermohonanBarangOut::whereIn('status_approved', ['DRAFT', 'APPROVED_2'])
                        ->with('approvalLogs')
                        ->get()
                        ->filter(fn (PermohonanBarangOut $item) => $item->isWaitingForPetugasApproval())
                        ->count();
                }
            }

            $view->with('pengaturan', $pengaturan)
                ->with('approvalInboxCount', $approvalInboxCount);
        });
    }
}