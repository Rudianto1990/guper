<?php

namespace Tests\Feature;

use App\Models\PermohonanBarangMasuk;
use App\Models\PermohonanBarangOut;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_returns_period_inventory_movement_storage_age_and_pending_approval_metrics(): void
    {
        Carbon::setTestNow('2026-10-19 12:00:00');
        $admin = User::factory()->create(['roles' => 'admin']);
        $owner = User::factory()->create(['roles' => 'user']);
        $otherOwner = User::factory()->create(['roles' => 'user']);
        $storedFromSeptember = $this->createApprovedIncoming($owner, 'ME-10001', 10, '2026-09-01');
        $storedFromOctober = $this->createApprovedIncoming($otherOwner, 'ME-10002', 5, '2026-10-10');
        $pendingIncoming = PermohonanBarangMasuk::create([
            'kd_barang' => 'ME-10003',
            'tgl_permohonan' => '2026-10-15',
            'nm_barang' => 'Menunggu Admin',
            'nama_pic' => 'Pemohon',
            'divisi' => 'Operasional',
            'lokasi_penyimpanan' => 'Gudang',
            'kondisi_barang' => 'BAIK',
            'qty_barang' => 2,
            'asal_barang' => 'Pembelian',
            'status_approved' => 'APPROVED_1',
            'user_created_by' => $owner->id,
        ]);
        $pendingIncoming->approvalLogs()->create([
            'approval_stage' => 1,
            'action' => 'SUBMITTED',
            'user_id' => $owner->id,
        ]);

        $this->createCompletedOutgoing($storedFromSeptember, 'OUT-10001', 3, '2026-10-05');
        $pendingOutgoing = PermohonanBarangOut::create([
            'kd_barang_out' => 'OUT-10002',
            'kd_barang' => $storedFromOctober->kd_barang,
            'tgl_permohonan_out' => '2026-10-18',
            'qty_barang' => 2,
            'status_approved' => 'APPROVED_1',
            'permohonan_barang_masuk_id' => $storedFromOctober->id,
        ]);
        $pendingOutgoing->approvalLogs()->create([
            'approval_stage' => 3,
            'action' => 'REQUESTED',
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Barang Tersimpan')
            ->assertSee(route('dashboard.data'))
            ->assertSee('card-statistic-2')
            ->assertSee('id="movementChart"', false)
            ->assertSee('id="storageChart"', false)
            ->assertSee('chart.js@2.9.4');

        $this->getJson(route('dashboard.data', ['month' => '2026-10']))
            ->assertOk()
            ->assertJsonPath('data.inventory.stored_units', 12)
            ->assertJsonPath('data.inventory.mean_storage_days', 32)
            ->assertJsonPath('data.period.incoming_units', 5)
            ->assertJsonPath('data.period.outgoing_units', 3)
            ->assertJsonPath('data.pending.incoming', 1)
            ->assertJsonPath('data.pending.outgoing', 1)
            ->assertJsonPath('data.pending.total', 2)
            ->assertJsonPath('data.storage_duration.0.units', 5)
            ->assertJsonPath('data.storage_duration.1.units', 7)
            ->assertJsonPath('data.trend.4.label', 'Okt')
            ->assertJsonPath('data.trend.4.incoming', 5);
    }

    public function test_dashboard_limits_user_metrics_to_their_own_inventory_and_validates_period(): void
    {
        Carbon::setTestNow('2026-10-19 12:00:00');
        $owner = User::factory()->create(['roles' => 'user']);
        $otherOwner = User::factory()->create(['roles' => 'user']);
        $ownedItem = $this->createApprovedIncoming($owner, 'ME-20001', 4, '2026-10-01');
        $this->createApprovedIncoming($otherOwner, 'ME-20002', 50, '2026-10-01');

        $this->actingAs($owner)
            ->getJson(route('dashboard.data', ['month' => '2026-10']))
            ->assertOk()
            ->assertJsonPath('data.inventory.stored_units', 4)
            ->assertJsonPath('data.period.incoming_units', 4);

        $this->getJson(route('dashboard.data', ['month' => 'not-a-month']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('month');

        $this->assertDatabaseHas('t_permohonan_barang_masuk', [
            'id' => $ownedItem->id,
            'user_created_by' => $owner->id,
        ]);
    }

    public function test_dashboard_counts_only_approvals_waiting_for_the_signed_in_role(): void
    {
        Carbon::setTestNow('2026-10-19 12:00:00');
        $owner = User::factory()->create(['roles' => 'user']);
        $supervisor = User::factory()->create(['roles' => 'sepervisior']);
        $petugas = User::factory()->create(['roles' => 'petugas']);
        $incoming = PermohonanBarangMasuk::create([
            'kd_barang' => 'ME-30001',
            'tgl_permohonan' => '2026-10-01',
            'nm_barang' => 'Menunggu Supervisor',
            'nama_pic' => 'Pemohon',
            'divisi' => 'Operasional',
            'lokasi_penyimpanan' => 'Gudang',
            'kondisi_barang' => 'BAIK',
            'qty_barang' => 1,
            'asal_barang' => 'Pembelian',
            'status_approved' => 'APPROVED_2',
            'user_created_by' => $owner->id,
        ]);
        $incoming->approvalLogs()->createMany([
            ['approval_stage' => 1, 'action' => 'SUBMITTED', 'user_id' => $owner->id],
            ['approval_stage' => 1, 'action' => 'APPROVED', 'user_id' => $owner->id],
        ]);

        $waitingSupervisor = PermohonanBarangOut::create([
            'kd_barang_out' => 'OUT-30001',
            'kd_barang' => $incoming->kd_barang,
            'qty_barang' => 1,
            'status_approved' => 'APPROVED_1',
            'permohonan_barang_masuk_id' => $incoming->id,
        ]);
        $waitingSupervisor->approvalLogs()->create([
            'approval_stage' => 3,
            'action' => 'REQUESTED',
            'user_id' => $owner->id,
        ]);

        $waitingPetugas = PermohonanBarangOut::create([
            'kd_barang_out' => 'OUT-30002',
            'kd_barang' => $incoming->kd_barang,
            'qty_barang' => 1,
            'status_approved' => 'APPROVED_2',
            'permohonan_barang_masuk_id' => $incoming->id,
        ]);
        $waitingPetugas->approvalLogs()->createMany([
            ['approval_stage' => 3, 'action' => 'APPROVED', 'user_id' => $supervisor->id],
            ['approval_stage' => 4, 'action' => 'REQUESTED', 'user_id' => $supervisor->id],
        ]);

        $this->actingAs($supervisor)
            ->getJson(route('dashboard.data'))
            ->assertOk()
            ->assertJsonPath('data.pending.incoming', 1)
            ->assertJsonPath('data.pending.outgoing', 1);

        $this->actingAs($petugas)
            ->getJson(route('dashboard.data'))
            ->assertOk()
            ->assertJsonPath('data.pending.incoming', 0)
            ->assertJsonPath('data.pending.outgoing', 1);
    }

    private function createApprovedIncoming(User $owner, string $code, int $quantity, string $date): PermohonanBarangMasuk
    {
        $item = PermohonanBarangMasuk::create([
            'kd_barang' => $code,
            'tgl_permohonan' => $date,
            'nm_barang' => 'Barang '.$code,
            'nama_pic' => $owner->name,
            'divisi' => 'Operasional',
            'lokasi_penyimpanan' => 'Gudang',
            'kondisi_barang' => 'BAIK',
            'qty_barang' => $quantity,
            'asal_barang' => 'Pembelian',
            'status_approved' => 'APPROVED_2',
            'user_created_by' => $owner->id,
        ]);
        $item->approvalLogs()->createMany([
            [
                'approval_stage' => 1,
                'action' => 'SUBMITTED',
                'user_id' => $owner->id,
            ],
            [
                'approval_stage' => 1,
                'action' => 'APPROVED',
                'user_id' => $owner->id,
            ],
            [
                'approval_stage' => 2,
                'action' => 'APPROVED',
                'user_id' => $owner->id,
            ],
        ]);

        return $item->load('approvalLogs');
    }

    private function createCompletedOutgoing(
        PermohonanBarangMasuk $source,
        string $code,
        int $quantity,
        string $date
    ): PermohonanBarangOut {
        $item = PermohonanBarangOut::create([
            'kd_barang_out' => $code,
            'kd_barang' => $source->kd_barang,
            'tgl_permohonan_out' => $date,
            'qty_barang' => $quantity,
            'status_approved' => 'APPROVED_2',
            'permohonan_barang_masuk_id' => $source->id,
        ]);
        $item->approvalLogs()->createMany([
            [
                'approval_stage' => 3,
                'action' => 'APPROVED',
            ],
            [
                'approval_stage' => 4,
                'action' => 'REQUESTED',
            ],
            [
                'approval_stage' => 4,
                'action' => 'APPROVED',
            ],
        ]);

        return $item;
    }
}
