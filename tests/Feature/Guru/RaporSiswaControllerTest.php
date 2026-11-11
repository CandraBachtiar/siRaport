<?php

namespace Tests\Feature\Guru;

use App\Models\DataRaporSiswa;
use App\Models\Deskripsi;
use App\Models\EkstrakurikulerSiswa;
use App\Models\Guru;
use App\Models\IdentitasSekolah;
use App\Models\Kelas;
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

class RaporSiswaControllerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminTestSchema();
    }

    public function test_admin_can_create_and_update_school_identity(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = ['nama_sekolah' => 'SD Contoh', 'npsn' => '12345678', 'alamat' => 'Jalan Sekolah', 'nama_kepala_sekolah' => 'Kepala Tercatat', 'nip_kepala_sekolah' => '001'];

        $this->actingAs($admin)->put(route('admin.identitas-sekolah.update'), $payload)->assertSessionHas('success');
        $this->actingAs($admin)->put(route('admin.identitas-sekolah.update'), [...$payload, 'nama_sekolah' => 'SD Diperbarui'])->assertSessionHas('success');
        $this->assertDatabaseCount('identitas_sekolah', 1);
        $this->assertDatabaseHas('identitas_sekolah', ['nama_sekolah' => 'SD Diperbarui']);
    }

    public function test_guru_cannot_manage_admin_only_school_identity_or_promotion_decision(): void
    {
        [$user, $student, $year] = $this->waliContext();

        $this->actingAs($user)->put(route('admin.identitas-sekolah.update'), ['nama_sekolah' => 'Tidak Diizinkan'])
            ->assertForbidden();
        $this->actingAs($user)->post(route('admin.keputusan-kenaikan.store'), [
            'tahun_ajaran_id' => $year->id,
            'siswa_id' => $student->id,
            'keputusan_kenaikan' => 'naik',
        ])->assertForbidden();
        $this->assertDatabaseCount('identitas_sekolah', 0);
        $this->assertDatabaseCount('data_rapor_siswa', 0);
    }

    public function test_admin_can_record_promotion_only_for_genap(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Siswa::factory()->create();
        $year = TahunAjaran::factory()->create(['semester' => 'Genap']);
        Pengampu::factory()->create(['kelas_id' => $student->kelas_id, 'tahun_ajaran_id' => $year->id]);

        $this->actingAs($admin)->post(route('admin.keputusan-kenaikan.store'), [
            'tahun_ajaran_id' => $year->id,
            'siswa_id' => $student->id,
            'keputusan_kenaikan' => 'naik',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('data_rapor_siswa', [
            'siswa_id' => $student->id,
            'tahun_ajaran_id' => $year->id,
            'keputusan_kenaikan' => 'naik',
            'keputusan_oleh' => $admin->id,
        ]);

        $ganjil = TahunAjaran::factory()->create(['semester' => 'Ganjil']);
        Pengampu::factory()->create(['kelas_id' => $student->kelas_id, 'tahun_ajaran_id' => $ganjil->id]);
        $this->actingAs($admin)->post(route('admin.keputusan-kenaikan.store'), [
            'tahun_ajaran_id' => $ganjil->id,
            'siswa_id' => $student->id,
            'keputusan_kenaikan' => 'naik',
        ])->assertStatus(422);
    }

    public function test_wali_can_save_period_data_and_extracurricular_only_for_own_class(): void
    {
        [$user, $student, $year] = $this->waliContext();
        $this->actingAs($user)->put(route('guru.wali.rapor.data.update', $student), [
            'tahun_ajaran_id' => $year->id,
            'sakit' => 2,
            'izin' => null,
            'tanpa_keterangan' => 0,
            'catatan_wali_kelas' => 'Catatan yang diisi wali kelas.',
            'ekstrakurikuler' => [
                ['nama_kegiatan' => 'Pramuka', 'keterangan' => 'Aktif'],
                ['nama_kegiatan' => '', 'keterangan' => ''],
            ],
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('data_rapor_siswa', ['siswa_id' => $student->id, 'tahun_ajaran_id' => $year->id, 'sakit' => 2, 'izin' => null]);
        $this->assertDatabaseHas('ekstrakurikuler_siswa', ['siswa_id' => $student->id, 'tahun_ajaran_id' => $year->id, 'nama_kegiatan' => 'Pramuka']);
        $this->assertSame(1, DataRaporSiswa::query()->count());
        $this->assertSame(1, EkstrakurikulerSiswa::query()->count());
    }

    public function test_partial_report_update_preserves_saved_extracurricular_activities(): void
    {
        [$user, $student, $year] = $this->waliContext();
        EkstrakurikulerSiswa::query()->create([
            'siswa_id' => $student->id,
            'tahun_ajaran_id' => $year->id,
            'nama_kegiatan' => 'Pramuka',
            'keterangan' => 'Aktif',
        ]);

        $this->actingAs($user)->put(route('guru.wali.rapor.data.update', $student), [
            'tahun_ajaran_id' => $year->id,
            'sakit' => 1,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('ekstrakurikuler_siswa', [
            'siswa_id' => $student->id,
            'tahun_ajaran_id' => $year->id,
            'nama_kegiatan' => 'Pramuka',
            'keterangan' => 'Aktif',
        ]);
    }

    public function test_wali_cannot_save_negative_attendance_or_access_another_class(): void
    {
        [$user, $student, $year] = $this->waliContext();
        $payload = ['tahun_ajaran_id' => $year->id, 'sakit' => -1];
        $this->actingAs($user)->put(route('guru.wali.rapor.data.update', $student), $payload)->assertSessionHasErrors('sakit');

        $otherStudent = Siswa::factory()->create();
        $otherYear = TahunAjaran::factory()->create();
        $this->actingAs($user)->get(route('guru.wali.rapor.data.edit', ['siswa' => $otherStudent, 'tahun_ajaran_id' => $otherYear->id]))->assertForbidden();
    }

    public function test_report_preview_displays_saved_school_and_student_period_data_without_fabricating_blanks(): void
    {
        [$user, $student, $year] = $this->waliContext();
        IdentitasSekolah::query()->create([
            'nama_sekolah' => 'SD Tersimpan',
            'npsn' => '12345678',
            'alamat' => 'Jl. Pendidikan',
            'nama_kepala_sekolah' => 'Kepala Sekolah Tercatat',
            'nip_kepala_sekolah' => 'NIP-001',
        ]);
        $assignment = Pengampu::query()->where('kelas_id', $student->kelas_id)->where('tahun_ajaran_id', $year->id)->firstOrFail();
        $goal = TujuanPembelajaran::factory()->for($assignment)->create();
        $assessment = Penilaian::factory()->for($assignment)->create(['tujuan_pembelajaran_id' => $goal->id]);
        $score = Nilai::factory()->create(['siswa_id' => $student->id, 'penilaian_id' => $assessment->id, 'nilai' => 88]);
        $longDescription = str_repeat('Capaian pembelajaran terverifikasi dan menunjukkan perkembangan yang konsisten. ', 22);
        $description = Deskripsi::factory()->for($score)->create(['status' => 'tervalidasi', 'deskripsi_akhir' => $longDescription]);
        $description->update(['validasi_fingerprint' => app(WaliKelasService::class)->subjectSummary($assignment, $student)['academicFingerprint']]);
        DataRaporSiswa::query()->create([
            'siswa_id' => $student->id,
            'tahun_ajaran_id' => $year->id,
            'sakit' => 2,
            'catatan_wali_kelas' => 'Catatan periode ini.',
        ]);
        EkstrakurikulerSiswa::query()->create([
            'siswa_id' => $student->id,
            'tahun_ajaran_id' => $year->id,
            'nama_kegiatan' => 'Pramuka',
            'keterangan' => 'Aktif',
        ]);
        $otherStudent = Siswa::factory()->for($student->kelas)->create();
        EkstrakurikulerSiswa::query()->create([
            'siswa_id' => $otherStudent->id,
            'tahun_ajaran_id' => $year->id,
            'nama_kegiatan' => 'Kegiatan Milik Siswa Lain',
        ]);
        $otherYear = TahunAjaran::factory()->create(['semester' => 'Ganjil']);
        Pengampu::factory()->create(['kelas_id' => $student->kelas_id, 'tahun_ajaran_id' => $otherYear->id]);
        EkstrakurikulerSiswa::query()->create([
            'siswa_id' => $student->id,
            'tahun_ajaran_id' => $otherYear->id,
            'nama_kegiatan' => 'Kegiatan Periode Lain',
        ]);

        $this->actingAs($user)->get(route('guru.wali.rapor.show', ['siswa' => $student, 'tahun_ajaran_id' => $year->id]))
            ->assertOk()
            ->assertSee($student->nama)
            ->assertSee($student->nis)
            ->assertSee($student->kelas->nama)
            ->assertSee($year->tahun)
            ->assertSee($year->semester)
            ->assertSee($user->guru()->firstOrFail()->nama)
            ->assertSee($user->guru()->firstOrFail()->nip)
            ->assertSee('SD Tersimpan')
            ->assertSee('12345678')
            ->assertSee('Jl. Pendidikan')
            ->assertSee('Kepala Sekolah Tercatat')
            ->assertSee('NIP-001')
            ->assertSee($longDescription)
            ->assertSee('Pramuka')
            ->assertSee('Sakit: 2')
            ->assertSee('Izin: Belum diisi')
            ->assertSee('Tanpa keterangan: Belum diisi')
            ->assertSee('Catatan periode ini.')
            ->assertSee('Belum ditetapkan')
            ->assertSee('Cetak Lengkap')
            ->assertSee('Cetak Isi')
            ->assertDontSee('Kegiatan Milik Siswa Lain')
            ->assertDontSee('Kegiatan Periode Lain');

        $print = $this->actingAs($user)->get(route('guru.wali.rapor.print', ['siswa' => $student, 'tahun_ajaran_id' => $year->id, 'format' => 'lengkap']));
        $print->assertOk()
            ->assertSee('data-report-cover')
            ->assertSee($student->nis)
            ->assertSee($year->tahun)
            ->assertSee('Orang Tua/Wali,')
            ->assertSee('SD Tersimpan')
            ->assertSee('Pramuka')
            ->assertSee('Nilai Akhir')
            ->assertSee('Belum ditetapkan')
            ->assertSee($longDescription)
            ->assertDontSee('KKM')
            ->assertDontSee('KKTP')
            ->assertSee('Kepala Sekolah Tercatat')
            ->assertSee('NIP-001')
            ->assertDontSee('Kegiatan Milik Siswa Lain')
            ->assertDontSee('Kegiatan Periode Lain');
        $this->assertSame(1, substr_count($print->getContent(), 'Pramuka: Aktif'));

        $inside = $this->actingAs($user)->get(route('guru.wali.rapor.print', ['siswa' => $student, 'tahun_ajaran_id' => $year->id, 'format' => 'isi']));
        $inside->assertOk()
            ->assertDontSee('data-report-cover')
            ->assertSee($longDescription)
            ->assertSee('SD Tersimpan')
            ->assertSee('Pramuka: Aktif')
            ->assertSee('Nilai Akhir')
            ->assertDontSee('KKM')
            ->assertDontSee('KKTP');

        $this->assertSame(1, DataRaporSiswa::query()->count());
        $this->assertSame(3, EkstrakurikulerSiswa::query()->count());
    }

    public function test_report_preview_hides_promotion_decision_for_ganjil_even_if_legacy_data_has_one(): void
    {
        [$user, $student, $year] = $this->waliContext('Ganjil');
        DataRaporSiswa::query()->create([
            'siswa_id' => $student->id,
            'tahun_ajaran_id' => $year->id,
            'keputusan_kenaikan' => 'naik',
        ]);

        $this->actingAs($user)->get(route('guru.wali.rapor.show', ['siswa' => $student, 'tahun_ajaran_id' => $year->id]))
            ->assertOk()
            ->assertDontSee('Keputusan Kenaikan Kelas')
            ->assertDontSee('Naik kelas');
    }

    public function test_wali_cannot_preview_or_print_student_outside_own_class(): void
    {
        [$user, , $year] = $this->waliContext();
        $otherStudent = Siswa::factory()->create();

        $this->actingAs($user)->get(route('guru.wali.rapor.show', ['siswa' => $otherStudent, 'tahun_ajaran_id' => $year->id]))
            ->assertNotFound();
        $this->actingAs($user)->get(route('guru.wali.rapor.print', ['siswa' => $otherStudent, 'tahun_ajaran_id' => $year->id]))
            ->assertNotFound();
    }

    public function test_print_rejects_unknown_format_parameter(): void
    {
        [$user, $student, $year] = $this->waliContext();

        $this->actingAs($user)->get(route('guru.wali.rapor.print', [
            'siswa' => $student,
            'tahun_ajaran_id' => $year->id,
            'format' => 'pdf-unduhan',
        ]))->assertRedirect()->assertSessionHasErrors('format');
    }

    public function test_preview_labels_unavailable_report_data_without_substituting_sample_values(): void
    {
        [$user, $student, $year] = $this->waliContext();

        $this->actingAs($user)->get(route('guru.wali.rapor.show', ['siswa' => $student, 'tahun_ajaran_id' => $year->id]))
            ->assertOk()
            ->assertSee('Belum tersedia')
            ->assertSee('Nama belum tersedia')
            ->assertSee('Sakit: Belum diisi')
            ->assertSee('Izin: Belum diisi')
            ->assertDontSee('SD Tersimpan')
            ->assertDontSee('Nama Wali Contoh');
    }

    /** @return array{User, Siswa, TahunAjaran} */
    private function waliContext(string $semester = 'Genap'): array
    {
        $user = User::factory()->guru()->create();
        $guru = Guru::factory()->for($user)->create();
        $class = Kelas::factory()->create(['wali_kelas_id' => $guru->id]);
        $student = Siswa::factory()->for($class)->create();
        $year = TahunAjaran::factory()->create(['semester' => $semester]);
        Pengampu::factory()->create(['kelas_id' => $class->id, 'tahun_ajaran_id' => $year->id]);

        return [$user, $student, $year];
    }
}
