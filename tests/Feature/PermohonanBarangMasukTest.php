<?php

namespace Tests\Feature;

use App\Models\PermohonanBarangMasuk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PermohonanBarangMasukTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_draft_submit_then_admin_can_approve_and_delete_request(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['roles' => 'staff']);
        $admin = User::factory()->create(['roles' => 'admin']);

        $response = $this->actingAs($user)->postJson(route('permohonan-barang-masuk.store'), [
            'tgl_permohonan' => '2026-10-16',
            'nm_barang' => 'Mesin Gate Barrier',
            'nama_pic' => 'Budi',
            'divisi' => 'Operasional',
            'lokasi_penyimpanan' => 'Gudang A',
            'kondisi_barang' => 'BAIK',
            'qty_barang' => 2,
            'asal_barang' => 'Pembelian',
            'submit_action' => 'draft',
            'bast' => UploadedFile::fake()->create('bast.pdf', 100, 'application/pdf'),
            'foto' => UploadedFile::fake()->create('mesin.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.kd_barang', 'ME-00001')
            ->assertJsonPath('data.user_created_by', $user->id)
            ->assertJsonPath('data.status_approved', 'DRAFT');

        $id = $response->json('data.id');
        $storedBast = $response->json('data.bast');
        $storedFoto = $response->json('data.foto');
        $this->get(route('permohonan-barang-masuk.index'))
            ->assertOk()
            ->assertSee('Mesin Gate Barrier');
        $this->getJson(route('permohonan-barang-masuk.data'))
            ->assertOk()
            ->assertJsonPath('data.0.kd_barang', 'ME-00001');
        $this->get($response->json('data.foto_url'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
        Storage::disk('public')->assertExists($storedBast);
        Storage::disk('public')->assertExists($storedFoto);

        $this->putJson(route('permohonan-barang-masuk.update', $id), [
            'tgl_permohonan' => '2026-10-17',
            'nm_barang' => 'Mesin Gate Barrier',
            'nama_pic' => 'Budi',
            'divisi' => 'Operasional',
            'lokasi_penyimpanan' => 'Gudang B',
            'kondisi_barang' => 'RUSAK',
            'qty_barang' => 1,
            'asal_barang' => 'Pembelian',
            'submit_action' => 'submit',
        ])->assertOk()->assertJsonPath('data.status_approved', 'APPROVED_1');

        Storage::disk('public')->assertExists($storedBast);
        Storage::disk('public')->assertExists($storedFoto);
        $this->assertDatabaseHas('t_permohonan_barang_masuk', [
            'id' => $id,
            'kd_barang' => 'ME-00001',
            'lokasi_penyimpanan' => 'Gudang B',
        ]);

        $this->actingAs($user)
            ->patchJson(route('permohonan-barang-masuk.approve', [$id, 1]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patchJson(route('permohonan-barang-masuk.approve', [$id, 1]), ['decision' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.status_approved', 'APPROVED_2')
            ->assertJsonPath('data.approval_logs.1.action', 'APPROVED')
            ->assertJsonPath('data.approval_logs.1.created_at_human', fn ($value) => is_string($value));

        $this->assertDatabaseHas('t_permohonan_barang_masuk_logs', [
            'permohonan_barang_masuk_id' => $id,
            'action' => 'APPROVED',
            'user_id' => $admin->id,
        ]);

        $this->deleteJson(route('permohonan-barang-masuk.destroy', $id))
            ->assertOk();

        Storage::disk('public')->assertMissing($storedBast);
        Storage::disk('public')->assertMissing($storedFoto);
        $this->assertDatabaseMissing('t_permohonan_barang_masuk', ['id' => $id]);
        $this->assertSame(0, PermohonanBarangMasuk::count());
    }

    public function test_incomplete_request_can_be_saved_as_draft_but_not_submitted(): void
    {
        $user = User::factory()->create(['roles' => 'staff']);

        $this->actingAs($user)->postJson(route('permohonan-barang-masuk.store'), [
            'nm_barang' => 'Barang belum lengkap',
            'submit_action' => 'draft',
        ])->assertCreated()
            ->assertJsonPath('data.status_approved', 'DRAFT');

        $item = PermohonanBarangMasuk::firstOrFail();
        $this->assertNull($item->tgl_permohonan);
        $this->assertNull($item->foto);
        $this->getJson(route('permohonan-barang-masuk.data'))
            ->assertJsonPath('data.0.can_edit', true);

        $anotherUser = User::factory()->create(['roles' => 'staff']);
        $this->actingAs($anotherUser)
            ->getJson(route('permohonan-barang-masuk.show', $item))
            ->assertForbidden();
        $this->get(route('permohonan-barang-masuk.attachment', [$item, 'foto']))
            ->assertForbidden();

        $this->actingAs($user)->putJson(route('permohonan-barang-masuk.update', $item), [
            'nm_barang' => 'Barang belum lengkap',
            'submit_action' => 'submit',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'tgl_permohonan',
                'nama_pic',
                'divisi',
                'lokasi_penyimpanan',
                'kondisi_barang',
                'qty_barang',
                'asal_barang',
                'bast',
                'foto',
            ]);
    }

    public function test_admin_rejection_requires_and_saves_comment_and_returns_request_to_draft(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['roles' => 'staff']);
        $admin = User::factory()->create(['roles' => 'admin']);

        $response = $this->actingAs($user)->postJson(route('permohonan-barang-masuk.store'), [
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
        $id = $response->json('data.id');

        $this->actingAs($admin)
            ->patchJson(route('permohonan-barang-masuk.approve', [$id, 1]), [
                'decision' => 'reject',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['comment']);

        $this->patchJson(route('permohonan-barang-masuk.approve', [$id, 1]), [
            'decision' => 'reject',
            'comment' => 'Mohon perbaiki data lokasi penyimpanan.',
        ])->assertOk()
            ->assertJsonPath('data.status_approved', 'DRAFT')
            ->assertJsonPath('data.approval_logs.1.action', 'REJECTED')
            ->assertJsonPath('data.approval_logs.1.comment', 'Mohon perbaiki data lokasi penyimpanan.')
            ->assertJsonPath('data.can_edit', false);

        $this->assertDatabaseHas('t_permohonan_barang_masuk_logs', [
            'permohonan_barang_masuk_id' => $id,
            'action' => 'REJECTED',
            'comment' => 'Mohon perbaiki data lokasi penyimpanan.',
            'user_id' => $admin->id,
        ]);

        $this->actingAs($user)
            ->getJson(route('permohonan-barang-masuk.data'))
            ->assertJsonPath('data.0.can_edit', true)
            ->assertJsonPath('data.0.approval_logs.1.comment', 'Mohon perbaiki data lokasi penyimpanan.');

        $this->actingAs($admin)
            ->get(route('permohonan-barang-masuk.monitoring'))
            ->assertOk()
            ->assertSee('data-monitoring-status="rejected"', false)
            ->assertSee('Ditolak')
            ->assertSee('badge badge-danger approval-state', false)
            ->assertSee('fas fa-times', false);
    }

    public function test_monitoring_dashboard_shows_status_counts_to_admin_and_scoped_user(): void
    {
        $admin = User::factory()->create(['roles' => 'admin']);
        $user = User::factory()->create(['roles' => 'user']);

        $makeRequest = function (string $code, string $status) use ($user): PermohonanBarangMasuk {
            return PermohonanBarangMasuk::create([
                'kd_barang' => $code,
                'tgl_permohonan' => '2026-10-16',
                'nm_barang' => 'Barang '.$code,
                'nama_pic' => 'Budi',
                'divisi' => 'Operasional',
                'lokasi_penyimpanan' => 'Gudang',
                'kondisi_barang' => 'BAIK',
                'qty_barang' => 1,
                'asal_barang' => 'Pembelian',
                'status_approved' => $status,
                'user_created_by' => $user->id,
            ]);
        };

        $waiting = $makeRequest('ME-10001', 'APPROVED_1');
        $waiting->approvalLogs()->create(['action' => 'SUBMITTED', 'user_id' => $user->id]);

        $approved = $makeRequest('ME-10002', 'APPROVED_2');
        $approved->approvalLogs()->create(['action' => 'SUBMITTED', 'approval_stage' => 1, 'user_id' => $user->id]);
        $approved->approvalLogs()->create(['action' => 'APPROVED', 'approval_stage' => 1, 'user_id' => $admin->id]);
        $approved->approvalLogs()->create(['action' => 'APPROVED', 'approval_stage' => 2, 'user_id' => $admin->id]);

        $rejected = $makeRequest('ME-10003', 'DRAFT');
        $rejected->approvalLogs()->create(['action' => 'SUBMITTED', 'approval_stage' => 1, 'user_id' => $user->id]);
        $rejected->approvalLogs()->create([
            'action' => 'REJECTED',
            'approval_stage' => 1,
            'comment' => 'Perbaiki data',
            'user_id' => $admin->id,
        ]);

        $draft = $makeRequest('ME-10004', 'DRAFT');

        $this->actingAs($admin)->get(route('permohonan-barang-masuk.monitoring'))
            ->assertOk()
            ->assertSee('Semua')
            ->assertSee('Menunggu Persetujuan')
            ->assertSee('data-monitoring-status="waiting"', false)
            ->assertSee('data-monitoring-status="approved"', false)
            ->assertSee('data-monitoring-status="rejected"', false)
            ->assertSee('ME-10001')
            ->assertSee('ME-10002')
            ->assertSee('ME-10003')
            ->assertSee('ME-10004');

        $this->actingAs($user)->get(route('permohonan-barang-masuk.monitoring'))
            ->assertOk()
            ->assertSee('ME-10001')
            ->assertDontSee('Keluarkan');
    }

    public function test_invalid_values_and_attachments_are_rejected(): void
    {
        $admin = User::factory()->create(['roles' => 'admin']);

        $this->actingAs($admin)->postJson(route('permohonan-barang-masuk.store'), [
            'tgl_permohonan' => '2026-10-16',
            'nm_barang' => 'Barang',
            'nama_pic' => 'Budi',
            'divisi' => 'Operasional',
            'lokasi_penyimpanan' => 'Gudang A',
            'kondisi_barang' => 'HILANG',
            'qty_barang' => 0,
            'asal_barang' => 'Pembelian',
            'submit_action' => 'submit',
            'bast' => UploadedFile::fake()->create('bast.txt', 10, 'text/plain'),
            'foto' => UploadedFile::fake()->image('barang.jpg'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['kondisi_barang', 'qty_barang', 'bast']);

        $this->assertDatabaseCount('t_permohonan_barang_masuk', 0);
    }

    public function test_supervisor_rejection_can_be_resubmitted_through_both_approval_stages(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['roles' => 'user']);
        $admin = User::factory()->create(['roles' => 'admin']);
        $supervisor = User::factory()->create(['roles' => 'sepervisior']);
        $payload = [
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
        ];
        $response = $this->actingAs($user)
            ->postJson(route('permohonan-barang-masuk.store'), $payload)
            ->assertCreated();
        $id = $response->json('data.id');

        $this->actingAs($admin)
            ->patchJson(route('permohonan-barang-masuk.approve', [$id, 1]), ['decision' => 'approved'])
            ->assertOk();
        $this->actingAs($supervisor)
            ->patchJson(route('permohonan-barang-masuk.approve', [$id, 2]), [
                'decision' => 'reject',
                'comment' => 'Mohon perbarui detail barang.',
            ])->assertOk()->assertJsonPath('data.status_approved', 'DRAFT');

        unset($payload['bast'], $payload['foto']);
        $this->actingAs($user)
            ->putJson(route('permohonan-barang-masuk.update', $id), $payload)
            ->assertOk()->assertJsonPath('data.status_approved', 'APPROVED_1');
        $this->actingAs($admin)
            ->patchJson(route('permohonan-barang-masuk.approve', [$id, 1]), ['decision' => 'approved'])
            ->assertOk();

        $this->actingAs($supervisor)
            ->get(route('approval-inbox.index'))
            ->assertOk()
            ->assertSee('Mesin Gate Barrier');
        $this->patchJson(route('permohonan-barang-masuk.approve', [$id, 2]), ['decision' => 'approved'])
            ->assertOk();

        $this->assertTrue(PermohonanBarangMasuk::with('approvalLogs')->findOrFail($id)->isFullyApproved());
    }
}
