<?php

namespace Tests\Feature;

use App\Models\Cctv;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CctvTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_list_update_and_delete_cctv_with_ajax(): void
    {
        $admin = User::factory()->create(['roles' => 'admin']);
        $payload = [
            'source_id' => 12,
            'inventory_code' => 'INV-CCTV-12',
            'nama_camera' => 'Kamera Pintu Utama',
            'location' => 'Pintu Utama',
            'ip_address' => '192.168.1.12',
            'type_camera' => 'IP Camera',
            'rtsp_url' => 'rtsp://192.168.1.12:554/stream1',
            'latitude' => '-6.200000',
            'longitude' => '106.816666',
            'source_name' => 'Sistem Kamera',
            'sub_division' => 'Keamanan',
            'category' => 'CCTV',
            'jenis_kategori' => 'IP',
            'merk' => 'Contoh',
            'serial_number' => 'SN-12',
            'source_status' => 'ACTIVE',
            'reg_date' => '2026-10-19',
            'nvr' => 'NVR-1',
            'nomor_urut' => '12',
            'link_img' => 'https://example.test/camera.jpg',
            'source_model' => 'Camera Model',
            'source_created_at' => '2026-10-19 10:00:00',
            'source_updated_at' => '2026-10-19 10:30:00',
        ];

        $created = $this->actingAs($admin)
            ->postJson(route('cctv.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('data.nama_camera', 'Kamera Pintu Utama')
            ->assertJsonPath('data.rtsp_url', 'rtsp://192.168.1.12:554/stream1');
        $id = $created->json('data.id');

        $this->get(route('cctv.index'))
            ->assertOk()
            ->assertSee('Live CCTV')
            ->assertSee('Tambah Kamera')
            ->assertSee('id="cctvLocationMap"', false)
            ->assertSee('Gunakan Geolocation')
            ->assertSee('id="saveCctv"', false);
        $this->getJson(route('cctv.data'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.inventory_code', 'INV-CCTV-12');
        $this->getJson(route('cctv.show', $id))
            ->assertOk()
            ->assertJsonPath('data.location', 'Pintu Utama');

        $this->putJson(route('cctv.update', $id), array_merge($payload, [
            'nama_camera' => 'Kamera Gerbang Utama',
            'location' => 'Gerbang Utama',
        ]))->assertOk()
            ->assertJsonPath('data.nama_camera', 'Kamera Gerbang Utama')
            ->assertJsonPath('data.location', 'Gerbang Utama');

        $this->assertDatabaseHas('cctv', [
            'id' => $id,
            'source_id' => 12,
            'nama_camera' => 'Kamera Gerbang Utama',
        ]);
        $this->deleteJson(route('cctv.destroy', $id))
            ->assertOk()
            ->assertJsonPath('message', 'Data CCTV berhasil dihapus.');
        $this->assertDatabaseMissing('cctv', ['id' => $id]);
    }

    public function test_cctv_crud_is_admin_only_and_validates_stream_url_and_required_fields(): void
    {
        $admin = User::factory()->create(['roles' => 'admin']);
        $user = User::factory()->create(['roles' => 'user']);

        $this->actingAs($user)->get(route('cctv.index'))->assertRedirect();
        $this->getJson(route('cctv.data'))->assertStatus(302);

        $this->actingAs($admin)->postJson(route('cctv.store'), [
            'nama_camera' => 'Kamera Invalid',
            'location' => 'Gudang',
            'ip_address' => 'not-an-ip',
            'type_camera' => 'IP',
            'rtsp_url' => 'javascript:alert(1)',
            'latitude' => '0',
            'longitude' => '0',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['ip_address', 'rtsp_url']);

        $this->assertSame(0, Cctv::count());
    }

    public function test_rtsp_playback_registers_a_source_in_mediamtx_and_returns_its_hls_url(): void
    {
        $admin = User::factory()->create(['roles' => 'admin']);
        $cctv = Cctv::create([
            'nama_camera' => 'Kamera Gudang',
            'location' => 'Gudang',
            'ip_address' => '192.168.1.12',
            'type_camera' => 'IP Camera',
            'rtsp_url' => 'rtsp://camera-user:camera-password@192.168.1.12:554/stream1',
            'latitude' => '-6.2',
            'longitude' => '106.8',
        ]);
        config([
            'services.mediamtx.api_url' => 'http://mediamtx.test',
            'services.mediamtx.api_username' => 'guper-api',
            'services.mediamtx.api_password' => 'secret-api-password',
            'services.mediamtx.hls_url' => 'http://mediamtx.test:8888',
        ]);
        Http::fake([
            'http://mediamtx.test/v3/config/paths/patch/*' => Http::response(['error' => 'path not found'], 404),
            'http://mediamtx.test/v3/config/paths/add/*' => Http::response(['status' => 'ok']),
            'http://mediamtx.test/v3/config/paths/delete/*' => Http::response(['status' => 'ok']),
        ]);

        $this->actingAs($admin)
            ->getJson(route('cctv.playback', $cctv))
            ->assertOk()
            ->assertJsonPath('data.url', 'http://mediamtx.test:8888/cctv-'.$cctv->id.'/index.m3u8')
            ->assertJsonPath('data.type', 'hls')
            ->assertJsonMissingPath('data.rtsp_url');

        Http::assertSent(function (ClientRequest $request) use ($cctv): bool {
            return $request->method() === 'POST'
                && $request->url() === 'http://mediamtx.test/v3/config/paths/add/cctv-'.$cctv->id
                && $request['source'] === $cctv->rtsp_url
                && $request['sourceOnDemand'] === true
                && $request->hasHeader('Authorization');
        });

        $this->actingAs($admin)
            ->deleteJson(route('cctv.destroy', $cctv))
            ->assertOk();
        Http::assertSent(function (ClientRequest $request) use ($cctv): bool {
            return $request->method() === 'DELETE'
                && $request->url() === 'http://mediamtx.test/v3/config/paths/delete/cctv-'.$cctv->id;
        });
    }

    public function test_rtsp_playback_reports_missing_mediamtx_configuration(): void
    {
        $admin = User::factory()->create(['roles' => 'admin']);
        $cctv = Cctv::create([
            'nama_camera' => 'Kamera Gudang',
            'location' => 'Gudang',
            'ip_address' => '192.168.1.12',
            'type_camera' => 'IP Camera',
            'rtsp_url' => 'rtsp://192.168.1.12:554/stream1',
            'latitude' => '-6.2',
            'longitude' => '106.8',
        ]);
        config([
            'services.mediamtx.api_url' => null,
            'services.mediamtx.hls_url' => null,
        ]);

        $this->actingAs($admin)
            ->getJson(route('cctv.playback', $cctv))
            ->assertStatus(503)
            ->assertJsonPath('message', 'MediaMTX belum dikonfigurasi. Isi MEDIAMTX_API_URL dan MEDIAMTX_HLS_URL pada file .env.');
    }
}
