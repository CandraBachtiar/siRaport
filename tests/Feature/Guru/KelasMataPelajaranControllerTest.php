<?php

namespace Tests\Feature\Guru;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\TujuanPembelajaran;
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
            ->assertSee('Buka Ruang Kelas')
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
            ->assertSee('Lihat Penilaian')
            ->assertSee('Jumlah Siswa')
            ->assertSee('Belum ada TP')
            ->assertSee('Mulai dengan menyiapkan TP dan KKTP. Buat penilaian setelah TP tersedia.')
            ->assertDontSee('>KKM</p>', false)
            ->assertSee(route('guru.tujuan-pembelajaran.index', $assignment), false)
            ->assertSee(route('guru.penilaian.create', ['pengampu_id' => $assignment->id]), false)
            ->assertSee(route('guru.nilai.index', [
                'tahun_ajaran_id' => $assignment->tahun_ajaran_id,
                'mata_pelajaran_id' => $assignment->mata_pelajaran_id,
                'kelas_id' => $assignment->kelas_id,
            ]));
    }

    public function test_detail_counts_tp_with_kktp_and_emphasizes_assessment_when_tp_exist(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $assignment = Pengampu::factory()->for($guru)->create();
        TujuanPembelajaran::factory()->count(3)->for($assignment)->sequence(
            ['urutan' => 1, 'kktp' => 70],
            ['urutan' => 2, 'kktp' => 75],
            ['urutan' => 3, 'kktp' => 80],
        )->create();

        $response = $this->actingAs($user)->get(route('guru.kelas-mapel.show', $assignment));

        $response->assertOk()
            ->assertSee('TP dengan KKTP')
            ->assertSee('3 dari 3 TP')
            ->assertSee('TP tersedia. Langkah berikutnya: buat penilaian.')
            ->assertSee('class="rk-button rk-button-primary">+ Buat Penilaian</a>', false);
    }

    public function test_detail_emphasizes_input_when_an_assessment_exists(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $assignment = Pengampu::factory()->for($guru)->create();
        TujuanPembelajaran::factory()->for($assignment)->create();
        Penilaian::factory()->for($assignment)->create();

        $response = $this->actingAs($user)->get(route('guru.kelas-mapel.show', $assignment));

        $response->assertOk()
            ->assertSee('Penilaian tersedia. Lanjutkan dengan menginput atau melengkapi nilai.')
            ->assertSee('class="rk-button rk-button-primary">Input Nilai</a>', false);
    }
}
