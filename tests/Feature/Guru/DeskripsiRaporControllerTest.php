<?php

namespace Tests\Feature\Guru;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class DeskripsiRaporControllerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_wali_can_save_a_draft_description_for_a_student_in_own_class(): void
    {
        [$user, $student, $assignment, $score] = $this->descriptionContext();

        $response = $this->actingAs($user)->put(route('guru.wali.deskripsi.update', [$assignment, $student]), [
            'deskripsi_akhir' => 'Menunjukkan perkembangan yang baik dan perlu terus dipertahankan.',
            'status' => 'draft',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('success', 'Draf deskripsi rapor berhasil disimpan.');
        $this->assertDatabaseHas('deskripsi', [
            'nilai_id' => $score->id,
            'deskripsi_akhir' => 'Menunjukkan perkembangan yang baik dan perlu terus dipertahankan.',
            'status' => 'draft',
        ]);
    }

    public function test_description_page_displays_a_transparent_system_suggestion(): void
    {
        [$user, $student, $assignment] = $this->descriptionContext();

        $response = $this->actingAs($user)->get(route('guru.wali.deskripsi.index', [
            'kelas_id' => $assignment->kelas_id,
            'tahun_ajaran_id' => $assignment->tahun_ajaran_id,
            'siswa_id' => $student->id,
            'pengampu_id' => $assignment->id,
        ]));

        $response->assertOk()
            ->assertSee('Saran sistem — silakan tinjau sebelum disimpan.')
            ->assertSee('Menunjukkan capaian yang baik')
            ->assertSee('84,00');
    }

    public function test_wali_can_validate_a_description_when_all_scores_are_complete(): void
    {
        [$user, $student, $assignment, $score] = $this->descriptionContext();

        $response = $this->actingAs($user)->put(route('guru.wali.deskripsi.update', [$assignment, $student]), [
            'deskripsi_akhir' => 'Capaian pembelajaran sudah baik dan konsisten sepanjang semester.',
            'status' => 'tervalidasi',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('success', 'Deskripsi rapor berhasil divalidasi.');
        $this->assertDatabaseHas('deskripsi', ['nilai_id' => $score->id, 'status' => 'tervalidasi']);
    }

    public function test_wali_cannot_save_a_description_for_another_class(): void
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        Kelas::factory()->create(['wali_kelas_id' => $guru->id]);
        $otherStudent = Siswa::factory()->create();
        $otherAssignment = Pengampu::factory()->create(['kelas_id' => $otherStudent->kelas_id]);
        $score = Nilai::factory()->create([
            'siswa_id' => $otherStudent->id,
            'penilaian_id' => Penilaian::factory()->for($otherAssignment)->create()->id,
        ]);

        $response = $this->actingAs($user)->put(route('guru.wali.deskripsi.update', [$otherAssignment, $otherStudent]), [
            'deskripsi_akhir' => 'Deskripsi ini tidak boleh disimpan oleh wali kelas lain.',
            'status' => 'draft',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('deskripsi', ['nilai_id' => $score->id]);
    }

    public function test_incomplete_scores_cannot_be_validated_as_a_final_description(): void
    {
        [$user, $student, $assignment, $score] = $this->descriptionContext();
        Penilaian::factory()->for($assignment)->create(['urutan' => 2]);

        $response = $this->actingAs($user)
            ->from(route('guru.wali.deskripsi.index'))
            ->put(route('guru.wali.deskripsi.update', [$assignment, $student]), [
                'deskripsi_akhir' => 'Deskripsi ini belum boleh divalidasi karena nilai belum lengkap.',
                'status' => 'tervalidasi',
            ]);

        $response->assertRedirect(route('guru.wali.deskripsi.index'))
            ->assertSessionHas('error', 'Lengkapi seluruh nilai mata pelajaran sebelum memvalidasi deskripsi.');
        $this->assertDatabaseMissing('deskripsi', ['nilai_id' => $score->id]);
    }

    public function test_short_description_is_rejected_with_a_specific_message(): void
    {
        [$user, $student, $assignment, $score] = $this->descriptionContext();

        $response = $this->actingAs($user)
            ->from(route('guru.wali.deskripsi.index'))
            ->put(route('guru.wali.deskripsi.update', [$assignment, $student]), [
                'deskripsi_akhir' => 'Terlalu singkat',
                'status' => 'draft',
            ]);

        $response->assertRedirect(route('guru.wali.deskripsi.index'))
            ->assertSessionHasErrors(['deskripsi_akhir' => 'Deskripsi rapor minimal 20 karakter agar informatif.']);
        $this->assertDatabaseMissing('deskripsi', ['nilai_id' => $score->id]);
    }

    public function test_description_and_status_are_required(): void
    {
        [$user, $student, $assignment, $score] = $this->descriptionContext();

        $response = $this->actingAs($user)
            ->from(route('guru.wali.deskripsi.index'))
            ->put(route('guru.wali.deskripsi.update', [$assignment, $student]), []);

        $response->assertRedirect(route('guru.wali.deskripsi.index'))
            ->assertSessionHasErrors([
                'deskripsi_akhir' => 'Deskripsi rapor wajib diisi.',
                'status' => 'Pilih status deskripsi.',
            ]);
        $this->assertDatabaseMissing('deskripsi', ['nilai_id' => $score->id]);
    }

    public function test_invalid_description_status_is_rejected(): void
    {
        [$user, $student, $assignment, $score] = $this->descriptionContext();

        $response = $this->actingAs($user)
            ->from(route('guru.wali.deskripsi.index'))
            ->put(route('guru.wali.deskripsi.update', [$assignment, $student]), [
                'deskripsi_akhir' => 'Deskripsi ini cukup panjang tetapi statusnya tidak dikenali.',
                'status' => 'diterbitkan',
            ]);

        $response->assertRedirect(route('guru.wali.deskripsi.index'))
            ->assertSessionHasErrors(['status' => 'Status deskripsi tidak valid.']);
        $this->assertDatabaseMissing('deskripsi', ['nilai_id' => $score->id]);
    }

    public function test_description_longer_than_two_thousand_characters_is_rejected(): void
    {
        [$user, $student, $assignment, $score] = $this->descriptionContext();

        $response = $this->actingAs($user)
            ->from(route('guru.wali.deskripsi.index'))
            ->put(route('guru.wali.deskripsi.update', [$assignment, $student]), [
                'deskripsi_akhir' => str_repeat('a', 2001),
                'status' => 'draft',
            ]);

        $response->assertRedirect(route('guru.wali.deskripsi.index'))
            ->assertSessionHasErrors(['deskripsi_akhir' => 'Deskripsi rapor maksimal 2.000 karakter.']);
        $this->assertDatabaseMissing('deskripsi', ['nilai_id' => $score->id]);
    }

    /** @return array{User, Siswa, Pengampu, Nilai} */
    private function descriptionContext(): array
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create(['wali_kelas_id' => $guru->id]);
        $year = TahunAjaran::factory()->create(['aktif' => true]);
        $student = Siswa::factory()->for($class)->create();
        $subject = MataPelajaran::factory()->create(['kkm' => 75]);
        $assignment = Pengampu::factory()->create([
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $year->id,
            'mata_pelajaran_id' => $subject->id,
        ]);
        $score = Nilai::factory()->create([
            'siswa_id' => $student->id,
            'penilaian_id' => Penilaian::factory()->for($assignment)->create(['urutan' => 1])->id,
            'nilai' => 84,
        ]);

        return [$user, $student, $assignment, $score];
    }
}
