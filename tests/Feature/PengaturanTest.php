<?php

namespace Tests\Feature;

use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengaturanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_and_update_settings_when_no_settings_record_exists(): void
    {
        $admin = User::factory()->create(['roles' => 'admin']);

        $this->actingAs($admin)
            ->get(route('pengaturan.index'))
            ->assertOk()
            ->assertSee(route('pengaturan.update', 1), false);

        $this->assertDatabaseCount('pengaturans', 1);

        $this->put(route('pengaturan.update', 1), [
            'nama_sekolah' => 'Guper Kantor',
        ])->assertRedirect(route('pengaturan.index'))
            ->assertSessionHas('success', 'Pengaturan berhasil diperbarui.');

        $this->assertDatabaseHas('pengaturans', [
            'id' => 1,
            'name' => 'Guper Kantor',
        ]);
    }
}
