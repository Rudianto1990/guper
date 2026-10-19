<?php

namespace Tests\Feature;

use App\Models\PermohonanBarangMasuk;
use App\Models\PermohonanBarangOut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PermohonanBarangOutTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_incoming_request_can_be_sent_to_petugas_and_retried_after_rejection(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['roles' => 'user']);
        $admin = User::factory()->create(['roles' => 'admin']);
        $supervisor = User::factory()->create(['roles' => 'sepervisior']);
        $petugas = User::factory()->create(['roles' => 'petugas']);

        $incomingResponse = $this->actingAs($user)->postJson(route('permohonan-barang-masuk.store'), [
            'tgl_permohonan' => '2026-10-16',
            'nm_barang' => 'Mesin Gate Barrier',
            'nama_pic' => 'Budi',
            'divisi' => 'Operasional',
            'lokasi_penyimpanan' => 'Gudang A',
            'kondisi_barang' => 'BAIK',
            'qty_barang' => 2,
            'asal_barang' => 'Pembelian',
            'submit_action' => 'submit',
            'bast' => UploadedFile::fake()->create('bast.pdf', 100, 'application/pdf'),
            'foto' => UploadedFile::fake()->image('mesin.jpg'),
        ])->assertCreated();
        $incomingId = $incomingResponse->json('data.id');

        $this->actingAs($admin)
            ->patchJson(route('permohonan-barang-masuk.approve', [$incomingId, 1]), ['decision' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.status_approved', 'APPROVED_2');

        $this->actingAs($supervisor)
            ->get(route('approval-inbox.index'))
            ->assertOk()
            ->assertSee('Persetujuan Supervisor')
            ->assertSee(route('permohonan-barang-masuk.approve', [$incomingId, 2]));

        $this->patchJson(route('permohonan-barang-masuk.approve', [$incomingId, 2]), ['decision' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.status_approved', 'APPROVED_2');

        $incoming = PermohonanBarangMasuk::with('approvalLogs')->findOrFail($incomingId);
        $this->assertTrue($incoming->isFullyApproved());
        $this->actingAs($admin)
            ->get(route('permohonan-barang-out.index'))
            ->assertOk()
            ->assertSee('Tambah Pengeluaran');

        $this->getJson(route('permohonan-barang-out.lookup', $incoming->kd_barang))
            ->assertOk()
            ->assertJsonPath('data.nm_barang', 'Mesin Gate Barrier')
            ->assertJsonPath('data.divisi', 'Operasional')
            ->assertJsonPath('data.nama_pic', 'Budi')
            ->assertJsonPath('data.stock', 2)
            ->assertJsonPath('data.stock_tersedia', 2);

        $outUrl = route('permohonan-barang-out.store');
        $outResponse = $this->postJson($outUrl, [
            'kd_barang' => $incoming->kd_barang,
            'tgl_permohonan_out' => '2026-10-20',
            'tujuan_barang' => 'Kantor cabang',
            'qty_barang' => 1,
            'submit_action' => 'draft',
        ])->assertCreated()
            ->assertJsonPath('data.kd_barang_out', 'OUT-00001')
            ->assertJsonPath('data.tgl_permohonan_out', '2026-10-20')
            ->assertJsonPath('data.status_approved', 'DRAFT')
            ->assertJsonPath('data.can_edit', true)
            ->assertJsonPath('data.can_delete', true);
        $this->assertSame($incomingResponse->json('data.kd_barang'), $outResponse->json('data.kd_barang'));
        $outId = $outResponse->json('data.id');

        $this->postJson($outUrl, [
            'kd_barang' => $incoming->kd_barang,
            'tgl_permohonan_out' => '2026-10-21',
            'tujuan_barang' => 'Tujuan kedua',
            'qty_barang' => 2,
            'submit_action' => 'draft',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Stock Barang Tidak Sesuai/Habis!!!');

        $this->actingAs($petugas)->get(route('approval-inbox.index'))
            ->assertOk()->assertDontSee('OUT-00001');

        $this->actingAs($admin)->put(route('permohonan-barang-out.update', $outId), [
            'kd_barang' => $incoming->kd_barang,
            'tgl_permohonan_out' => '2026-10-20',
            'tujuan_barang' => 'Kantor cabang',
            'qty_barang' => 1,
            'submit_action' => 'submit',
            'foto' => UploadedFile::fake()->image('outgoing.png'),
        ], ['Accept' => 'application/json'])->assertOk()
            ->assertJsonPath('data.status_approved', 'APPROVED_1')
            ->assertJsonPath('data.can_edit', false);

        $this->actingAs($supervisor)->get(route('approval-inbox.index'))
            ->assertOk()->assertSee('Persetujuan Pengeluaran Barang')->assertSee('OUT-00001');
        $this->patchJson(route('permohonan-barang-out.decide', $outId), [
            'decision' => 'reject',
        ])->assertUnprocessable()->assertJsonValidationErrors(['comment']);

        $this->patchJson(route('permohonan-barang-out.decide', $outId), [
            'decision' => 'reject',
            'comment' => 'Jumlah perlu diperiksa kembali.',
        ])->assertOk()->assertJsonPath('data.status_approved', 'DRAFT');

        $this->actingAs($admin)
            ->putJson(route('permohonan-barang-out.update', $outId), [
                'kd_barang' => $incoming->kd_barang,
                'tgl_permohonan_out' => '2026-10-22',
                'tujuan_barang' => 'Kantor cabang',
                'qty_barang' => 1,
                'submit_action' => 'submit',
            ])->assertOk()->assertJsonPath('data.status_approved', 'APPROVED_1');

        $this->actingAs($supervisor)
            ->get(route('approval-inbox.index'))
            ->assertOk()->assertSee('OUT-00001');
        $this->patchJson(route('permohonan-barang-out.decide', $outId), [
            'decision' => 'approved',
        ])->assertOk()->assertJsonPath('data.status_approved', 'APPROVED_2');

        $this->actingAs($petugas)
            ->get(route('approval-inbox.index'))
            ->assertOk()->assertSee('OUT-00001');
        $this->patchJson(route('permohonan-barang-out.decide', $outId), [
            'decision' => 'approved',
        ])->assertOk()->assertJsonPath('data.status_approved', 'APPROVED_2');

        $this->assertDatabaseHas('t_permohonan_barang_out_logs', [
            'permohonan_barang_out_id' => $outId,
            'approval_stage' => 4,
            'action' => 'APPROVED',
            'user_id' => $petugas->id,
        ]);
        $this->assertDatabaseHas('t_permohonan_barang_out_logs', [
            'permohonan_barang_out_id' => $outId,
            'approval_stage' => 3,
            'action' => 'APPROVED',
            'user_id' => $supervisor->id,
        ]);
        $this->get(route('approval-inbox.index'))->assertDontSee('OUT-00001');
    }

    public function test_only_admin_can_manage_outgoing_requests_and_only_petugas_can_decide(): void
    {
        $user = User::factory()->create(['roles' => 'user']);
        $admin = User::factory()->create(['roles' => 'admin']);
        $petugas = User::factory()->create(['roles' => 'petugas']);

        $this->actingAs($user)->get(route('permohonan-barang-out.index'))->assertOk();
        $this->actingAs($petugas)->get(route('permohonan-barang-out.index'))->assertOk();
        $this->actingAs($user)->get(route('approval-inbox.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('approval-inbox.index'))->assertOk();
    }

    public function test_user_menu_and_pages_are_available_and_data_is_limited_to_their_requests(): void
    {
        $user = User::factory()->create(['roles' => 'user']);
        $otherUser = User::factory()->create(['roles' => 'user']);

        $createIncoming = function (User $owner, string $code, string $name): PermohonanBarangMasuk {
            return PermohonanBarangMasuk::create([
                'kd_barang' => $code,
                'tgl_permohonan' => '2026-10-17',
                'nm_barang' => $name,
                'nama_pic' => $owner->name,
                'divisi' => 'Operasional',
                'lokasi_penyimpanan' => 'Gudang A',
                'kondisi_barang' => 'BAIK',
                'qty_barang' => 1,
                'asal_barang' => 'Pembelian',
                'status_approved' => 'DRAFT',
                'user_created_by' => $owner->id,
            ]);
        };

        $ownedIncoming = $createIncoming($user, 'ME-10001', 'Barang Milik Saya');
        $foreignIncoming = $createIncoming($otherUser, 'ME-10002', 'Barang Milik User Lain');
        $ownedOut = PermohonanBarangOut::create([
            'kd_barang_out' => 'OUT-10001',
            'kd_barang' => $ownedIncoming->kd_barang,
            'permohonan_barang_masuk_id' => $ownedIncoming->id,
            'nm_barang' => $ownedIncoming->nm_barang,
            'status_approved' => 'DRAFT',
        ]);
        PermohonanBarangOut::create([
            'kd_barang_out' => 'OUT-10002',
            'kd_barang' => $foreignIncoming->kd_barang,
            'permohonan_barang_masuk_id' => $foreignIncoming->id,
            'nm_barang' => $foreignIncoming->nm_barang,
            'status_approved' => 'DRAFT',
        ]);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee(route('permohonan-barang-masuk.index'))
            ->assertSee(route('permohonan-barang-out.index'))
            ->assertSee(route('permohonan-barang-masuk.monitoring'));

        $this->get(route('permohonan-barang-masuk.monitoring'))
            ->assertOk()
            ->assertSee('Barang Milik Saya')
            ->assertDontSee('Barang Milik User Lain')
            ->assertDontSee('Keluarkan');

        $this->get(route('permohonan-barang-out.index'))
            ->assertOk()
            ->assertSee('OUT-10001')
            ->assertDontSee('OUT-10002')
            ->assertDontSee('Buat Permohonan Pengeluaran');

        $this->getJson(route('permohonan-barang-out.data'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownedOut->id);

        $this->getJson(route('permohonan-barang-out.show', $ownedOut))->assertOk();
    }

    public function test_supervisor_sees_requested_menu_and_read_only_monitoring_pages(): void
    {
        $supervisor = User::factory()->create(['roles' => 'sepervisior']);
        $owner = User::factory()->create(['roles' => 'user']);
        $incoming = PermohonanBarangMasuk::create([
            'kd_barang' => 'ME-20001',
            'tgl_permohonan' => '2026-10-17',
            'nm_barang' => 'Barang Untuk Monitoring Supervisor',
            'nama_pic' => 'Pemohon',
            'divisi' => 'Operasional',
            'lokasi_penyimpanan' => 'Gudang B',
            'kondisi_barang' => 'BAIK',
            'qty_barang' => 1,
            'asal_barang' => 'Pembelian',
            'status_approved' => 'APPROVED_2',
            'user_created_by' => $owner->id,
        ]);
        $outgoing = PermohonanBarangOut::create([
            'kd_barang_out' => 'OUT-20001',
            'kd_barang' => $incoming->kd_barang,
            'permohonan_barang_masuk_id' => $incoming->id,
            'nm_barang' => $incoming->nm_barang,
            'status_approved' => 'DRAFT',
        ]);

        $this->actingAs($supervisor)->get(route('home'))
            ->assertOk()
            ->assertSee(route('permohonan-barang-masuk.index'))
            ->assertSee(route('permohonan-barang-out.index'))
            ->assertSee(route('permohonan-barang-masuk.monitoring'))
            ->assertSee(route('approval-inbox.index'));

        $this->get(route('permohonan-barang-masuk.index'))->assertOk();
        $this->get(route('permohonan-barang-masuk.monitoring'))
            ->assertOk()
            ->assertSee('Barang Untuk Monitoring Supervisor')
            ->assertDontSee('Keluarkan');
        $this->get(route('permohonan-barang-out.index'))
            ->assertOk()
            ->assertSee('OUT-20001')
            ->assertDontSee('Buat Permohonan Pengeluaran');
        $this->getJson(route('permohonan-barang-out.data'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $outgoing->id);

        $this->patchJson(route('permohonan-barang-out.decide', $outgoing), [
            'decision' => 'approved',
        ])->assertUnprocessable();
    }

    public function test_petugas_has_dashboard_outgoing_list_and_approval_inbox_but_cannot_edit_requests(): void
    {
        $petugas = User::factory()->create(['roles' => 'petugas']);
        $owner = User::factory()->create(['roles' => 'user']);
        $source = PermohonanBarangMasuk::create([
            'kd_barang' => 'ME-40001',
            'tgl_permohonan' => '2026-10-17',
            'nm_barang' => 'Barang untuk Petugas',
            'nama_pic' => 'PIC',
            'divisi' => 'Operasional',
            'lokasi_penyimpanan' => 'Gudang A',
            'kondisi_barang' => 'BAIK',
            'qty_barang' => 2,
            'asal_barang' => 'Pembelian',
            'status_approved' => 'APPROVED_2',
            'user_created_by' => $owner->id,
        ]);
        $out = PermohonanBarangOut::create([
            'kd_barang_out' => 'OUT-40001',
            'kd_barang' => $source->kd_barang,
            'permohonan_barang_masuk_id' => $source->id,
            'nm_barang' => $source->nm_barang,
            'status_approved' => 'APPROVED_1',
        ]);

        $this->actingAs($petugas)->get(route('home'))
            ->assertOk()
            ->assertSee(route('permohonan-barang-out.index'))
            ->assertSee(route('approval-inbox.index'))
            ->assertSee('Dashboard');
        $this->get(route('permohonan-barang-out.index'))
            ->assertOk()
            ->assertSee('OUT-40001')
            ->assertSee('Periksa')
            ->assertDontSee('id="createOutRequest"', false);
        $this->getJson(route('permohonan-barang-out.show', $out))->assertOk();
        $this->getJson(route('permohonan-barang-out.data'))
            ->assertOk()->assertJsonPath('data.0.id', $out->id);
        $this->putJson(route('permohonan-barang-out.update', $out), [])
            ->assertForbidden();
        $this->deleteJson(route('permohonan-barang-out.destroy', $out))
            ->assertForbidden();
    }

    public function test_admin_can_create_edit_and_delete_drafts_and_cannot_exceed_source_stock(): void
    {
        $admin = User::factory()->create(['roles' => 'admin']);
        $owner = User::factory()->create(['roles' => 'user']);
        $source = PermohonanBarangMasuk::create([
            'kd_barang' => 'ME-30001',
            'tgl_permohonan' => '2026-10-17',
            'nm_barang' => 'Stok Terbatas',
            'nama_pic' => 'PIC',
            'divisi' => 'IT',
            'lokasi_penyimpanan' => 'Rak A',
            'kondisi_barang' => 'BAIK',
            'qty_barang' => 3,
            'asal_barang' => 'Pembelian',
            'status_approved' => 'APPROVED_2',
            'user_created_by' => $owner->id,
        ]);
        $source->approvalLogs()->createMany([
            ['action' => 'SUBMITTED', 'approval_stage' => 1, 'user_id' => $owner->id],
            ['action' => 'APPROVED', 'approval_stage' => 1, 'user_id' => $admin->id],
            ['action' => 'APPROVED', 'approval_stage' => 2, 'user_id' => $owner->id],
        ]);

        $this->actingAs($admin)->postJson(route('permohonan-barang-out.store'), [
            'kd_barang' => $source->kd_barang,
            'tgl_permohonan_out' => '2026-10-19',
            'tujuan_barang' => 'Kantor',
            'qty_barang' => 2,
            'submit_action' => 'draft',
        ])->assertCreated()->assertJsonPath('data.can_edit', true);
        $draft = PermohonanBarangOut::firstOrFail();

        $this->putJson(route('permohonan-barang-out.update', $draft), [
            'kd_barang' => $source->kd_barang,
            'tgl_permohonan_out' => '2026-10-20',
            'tujuan_barang' => 'Gudang cabang',
            'qty_barang' => 4,
            'submit_action' => 'draft',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Stock Barang Tidak Sesuai/Habis!!!');

        $this->putJson(route('permohonan-barang-out.update', $draft), [
            'kd_barang' => $source->kd_barang,
            'tgl_permohonan_out' => '2026-10-20',
            'tujuan_barang' => 'Gudang cabang',
            'qty_barang' => 1,
            'submit_action' => 'draft',
        ])->assertOk()
            ->assertJsonPath('data.tujuan_barang', 'Gudang cabang')
            ->assertJsonPath('data.can_edit', true);

        $this->deleteJson(route('permohonan-barang-out.destroy', $draft))
            ->assertOk();
        $this->assertDatabaseMissing('t_permohonan_barang_out', ['id' => $draft->id]);
    }
}
