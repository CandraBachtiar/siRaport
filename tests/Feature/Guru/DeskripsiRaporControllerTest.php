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
use App\Models\TujuanPembelajaran;
use App\Models\User;
use App\Services\WaliKelasService;
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
            ->assertSee('Capaian terkuat: TP 1')
            ->assertSee('Seluruh capaian sumatif yang lengkap telah mencapai KKTP; tidak ada bagian yang ditandai belum tuntas.')
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
        $this->assertDatabaseHas('deskripsi', [
            'nilai_id' => $score->id,
            'status' => 'tervalidasi',
            'validasi_fingerprint' => app(WaliKelasService::class)->subjectSummary($assignment, $student)['academicFingerprint'],
        ]);
    }

    public function test_changed_score_invalidates_validation_and_keeps_saved_text(): void
    {
        [$user, $student, $assignment, $score] = $this->descriptionContext();
        $this->actingAs($user)->put(route('guru.wali.deskripsi.update', [$assignment, $student]), [
            'deskripsi_akhir' => 'Deskripsi lama yang tetap dipertahankan untuk ditinjau kembali.',
            'status' => 'tervalidasi',
        ]);

        $score->update(['nilai' => 60]);
        $this->actingAs($user)->get(route('guru.wali.rapor.show', [
            'siswa' => $student,
            'tahun_ajaran_id' => $assignment->tahun_ajaran_id,
        ]))->assertOk()
            ->assertSee('Draf deskripsi tersimpan; perlu ditinjau ulang.')
            ->assertSee('Deskripsi lama yang tetap dipertahankan untuk ditinjau kembali.');

        $response = $this->actingAs($user)->get(route('guru.wali.rapor.print', [
            'siswa' => $student,
            'tahun_ajaran_id' => $assignment->tahun_ajaran_id,
        ]));

        $response->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('deskripsi', [
            'nilai_id' => $score->id,
            'status' => 'tervalidasi',
            'deskripsi_akhir' => 'Deskripsi lama yang tetap dipertahankan untuk ditinjau kembali.',
        ]);
    }

    public function test_unrelated_assessment_name_change_does_not_invalidate_fingerprint(): void
    {
        [$user, $student, $assignment, $score] = $this->descriptionContext();
        $this->actingAs($user)->put(route('guru.wali.deskripsi.update', [$assignment, $student]), [
            'deskripsi_akhir' => 'Deskripsi valid tetap tersimpan setelah nama tugas berubah.',
            'status' => 'tervalidasi',
        ]);
        $assessment = $score->penilaian;
        $assessment->update(['nama' => 'Nama tugas diperbarui']);

        $summary = app(WaliKelasService::class)->subjectSummary($assignment, $student);

        $this->assertTrue($summary['descriptionIsCurrent']);
    }

    public function test_tp_content_change_invalidates_and_preserves_the_description_text(): void
    {
        [$user, $student, $assignment, $score, $goal] = $this->validatedDescription();
        $goal->update(['tujuan' => 'Menyelesaikan soal cerita dengan strategi yang tepat']);

        $this->assertPrintBlockedAndTextPreserved($user, $student, $assignment, $score);
    }

    public function test_kktp_change_invalidates_previous_validation(): void
    {
        [$user, $student, $assignment, $score, $goal] = $this->validatedDescription();
        $goal->update(['kktp' => 80]);

        $this->assertPrintBlockedAndTextPreserved($user, $student, $assignment, $score);
    }

    public function test_assessment_category_change_invalidates_previous_validation(): void
    {
        [$user, $student, $assignment, $score] = $this->validatedDescription();
        $score->penilaian->update(['kategori_asesmen' => Penilaian::KATEGORI_PTS]);

        $this->assertPrintBlockedAndTextPreserved($user, $student, $assignment, $score);
    }

    public function test_recap_method_change_invalidates_previous_validation(): void
    {
        [$user, $student, $assignment, $score, $goal] = $this->validatedDescription();
        $goal->update(['metode_rekap' => TujuanPembelajaran::METODE_NILAI_TERTINGGI]);

        $this->assertPrintBlockedAndTextPreserved($user, $student, $assignment, $score);
    }

    public function test_other_student_score_does_not_invalidate_current_student(): void
    {
        [$user, $student, $assignment, $score] = $this->validatedDescription();
        $otherStudent = Siswa::factory()->for($student->kelas)->create();
        Nilai::factory()->create(['siswa_id' => $otherStudent->id, 'penilaian_id' => $score->penilaian_id, 'nilai' => 40]);

        $this->assertTrue(app(WaliKelasService::class)->subjectSummary($assignment, $student)['descriptionIsCurrent']);
    }

    public function test_formative_score_change_does_not_invalidate_sumative_description(): void
    {
        [$user, $student, $assignment, $score, $goal] = $this->validatedDescription();
        $formativeAssessment = Penilaian::factory()->for($assignment)->create([
            'tujuan_pembelajaran_id' => $goal->id,
            'kategori_asesmen' => Penilaian::KATEGORI_FORMATIF,
            'urutan' => 2,
        ]);
        $formativeScore = Nilai::factory()->create([
            'siswa_id' => $student->id,
            'penilaian_id' => $formativeAssessment->id,
            'nilai' => 55,
        ]);
        $this->actingAs($user)->put(route('guru.wali.deskripsi.update', [$assignment, $student]), [
            'deskripsi_akhir' => 'Deskripsi valid berdasarkan capaian sumatif TP yang lengkap.',
            'status' => 'tervalidasi',
        ]);
        $formativeScore->update(['nilai' => 10]);

        $this->assertTrue(app(WaliKelasService::class)->subjectSummary($assignment, $student)['descriptionIsCurrent']);
    }

    public function test_legacy_validated_description_without_fingerprint_is_blocked_from_print(): void
    {
        [$user, $student, $assignment, $score] = $this->validatedDescription();
        $score->deskripsi()->update(['validasi_fingerprint' => null]);

        $this->assertPrintBlockedAndTextPreserved($user, $student, $assignment, $score);
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
        $goal = $assignment->tujuanPembelajaran()->firstOrFail();
        Penilaian::factory()->for($assignment)->create([
            'tujuan_pembelajaran_id' => $goal->id,
            'kategori_asesmen' => Penilaian::KATEGORI_LINGKUP_MATERI,
            'urutan' => 2,
        ]);

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

    public function test_unmapped_legacy_assessment_blocks_final_validation_and_is_explained(): void
    {
        [$user, $student, $assignment] = $this->descriptionContext();
        $legacyAssessment = Penilaian::factory()->for($assignment)->create([
            'tujuan_pembelajaran_id' => null,
            'urutan' => 2,
        ]);
        $legacyScore = Nilai::factory()->create([
            'siswa_id' => $student->id,
            'penilaian_id' => $legacyAssessment->id,
            'nilai' => 90,
        ]);

        $index = $this->actingAs($user)->get(route('guru.wali.deskripsi.index', [
            'kelas_id' => $assignment->kelas_id,
            'tahun_ajaran_id' => $assignment->tahun_ajaran_id,
            'siswa_id' => $student->id,
            'pengampu_id' => $assignment->id,
        ]));
        $index->assertOk()->assertSee('belum dipetakan ke TP atau kategori');

        $response = $this->actingAs($user)->put(route('guru.wali.deskripsi.update', [$assignment, $student]), [
            'deskripsi_akhir' => 'Capaian pembelajaran sudah baik dan konsisten sepanjang semester.',
            'status' => 'tervalidasi',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('error', 'Lengkapi seluruh nilai mata pelajaran sebelum memvalidasi deskripsi.');
        $this->assertDatabaseMissing('deskripsi', ['nilai_id' => $legacyScore->id]);
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
        $goal = TujuanPembelajaran::factory()->for($assignment)->create([
            'urutan' => 1,
            'kktp' => 75,
            'metode_rekap' => TujuanPembelajaran::METODE_RATA_RATA,
        ]);
        $score = Nilai::factory()->create([
            'siswa_id' => $student->id,
            'penilaian_id' => Penilaian::factory()->for($assignment)->create([
                'tujuan_pembelajaran_id' => $goal->id,
                'kategori_asesmen' => Penilaian::KATEGORI_LINGKUP_MATERI,
                'urutan' => 1,
            ])->id,
            'nilai' => 84,
        ]);

        return [$user, $student, $assignment, $score];
    }

    /** @return array{User, Siswa, Pengampu, Nilai, TujuanPembelajaran} */
    private function validatedDescription(): array
    {
        [$user, $student, $assignment, $score] = $this->descriptionContext();
        $this->actingAs($user)->put(route('guru.wali.deskripsi.update', [$assignment, $student]), [
            'deskripsi_akhir' => 'Deskripsi lama dipertahankan agar wali kelas dapat meninjaunya kembali.',
            'status' => 'tervalidasi',
        ])->assertSessionHas('success', 'Deskripsi rapor berhasil divalidasi.');

        return [$user, $student, $assignment, $score, $assignment->tujuanPembelajaran()->firstOrFail()];
    }

    private function assertPrintBlockedAndTextPreserved(User $user, Siswa $student, Pengampu $assignment, Nilai $score): void
    {
        foreach (['lengkap', 'isi'] as $format) {
            $this->actingAs($user)->get(route('guru.wali.rapor.print', [
                'siswa' => $student,
                'tahun_ajaran_id' => $assignment->tahun_ajaran_id,
                'format' => $format,
            ]))->assertRedirect()->assertSessionHas('error');
        }

        $this->assertDatabaseHas('deskripsi', [
            'nilai_id' => $score->id,
            'status' => 'tervalidasi',
            'deskripsi_akhir' => 'Deskripsi lama dipertahankan agar wali kelas dapat meninjaunya kembali.',
        ]);
    }
}
