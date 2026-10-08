<?php

namespace Tests\Feature\Guru;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\User;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class KelasMataPelajaranControllerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_index_displays_only_authenticated_gurus_assignments(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $otherGuru = Guru::factory()->create();
        $ownSubject = MataPelajaran::factory()->create(['nama' => 'Matematika Saya']);
        $otherSubject = MataPelajaran::factory()->create(['nama' => 'IPA Guru Lain']);
        Pengampu::factory()->for($guru)->create(['mata_pelajaran_id' => $ownSubject->id]);
        Pengampu::factory()->for($otherGuru)->create(['mata_pelajaran_id' => $otherSubject->id]);

        $response = $this->actingAs($user)->get(route('guru.kelas-mapel.index'));

        $response->assertOk()
            ->assertSee('Matematika Saya')
            ->assertDontSee('IPA Guru Lain');
    }

    public function test_guru_cannot_open_another_gurus_assignment(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        Pengampu::factory()->for($guru)->create();
        $otherAssignment = Pengampu::factory()->create();

        $response = $this->actingAs($user)->get(route('guru.kelas-mapel.show', $otherAssignment));

        $response->assertForbidden();
    }

    public function test_detail_displays_students_assessments_and_real_summary(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create(['tingkat' => 'VIII', 'nama' => 'C']);
        $assignment = Pengampu::factory()->for($guru)->create(['kelas_id' => $class->id]);
        Siswa::factory()->count(2)->for($class)->sequence(
            ['nama' => 'Alya Putri'],
            ['nama' => 'Bima Sakti'],
        )->create();
        Penilaian::factory()->for($assignment)->create(['nama' => 'Tugas Pertama']);

        $response = $this->actingAs($user)->get(route('guru.kelas-mapel.show', $assignment));

        $response->assertOk()
            ->assertSee('VIII C')
            ->assertSee('Alya Putri')
            ->assertSee('Bima Sakti')
            ->assertSee('Tugas Pertama')
            ->assertSee('Jumlah Siswa');
    }
}
