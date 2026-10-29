<?php

namespace Tests\Feature\Admin;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class SiswaControllerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.siswa.index'));

        $response->assertRedirectToRoute('login');
    }

    public function test_guru_is_forbidden_from_data_siswa(): void
    {
        $guru = User::factory()->guru()->create();

        $response = $this->actingAs($guru)->get(route('admin.siswa.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_search_and_filter_siswa_list(): void
    {
        $admin = User::factory()->admin()->create();
        $kelasA = Kelas::factory()->create(['tingkat' => 'VII', 'nama' => 'A']);
        $kelasB = Kelas::factory()->create(['tingkat' => 'VIII', 'nama' => 'B']);
        Siswa::factory()->for($kelasA)->create([
            'nisn' => '0012345678',
            'nama' => 'Anisa Putri',
            'jenis_kelamin' => 'P',
        ]);
        Siswa::factory()->for($kelasB)->create([
            'nisn' => '0099999999',
            'nama' => 'Anisa Lain',
        ]);
        Siswa::factory()->for($kelasA)->create([
            'nisn' => '0088888888',
            'nama' => 'Budi Santoso',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.siswa.index', [
            'search' => 'Anisa',
            'kelas_id' => $kelasA->id,
        ]));

        $response->assertOk()
            ->assertSee('0012345678')
            ->assertSee('Anisa Putri')
            ->assertSee('Perempuan')
            ->assertSee('VII A')
            ->assertDontSee('Anisa Lain')
            ->assertDontSee('Budi Santoso');
    }

    public function test_student_overview_groups_existing_classes_by_saved_level_and_counts_students(): void
    {
        $admin = User::factory()->admin()->create();
        $firstClass = Kelas::factory()->create(['tingkat' => '1', 'nama' => 'A']);
        $secondClass = Kelas::factory()->create(['tingkat' => '1', 'nama' => 'B']);
        Kelas::factory()->create(['tingkat' => '2', 'nama' => 'A']);
        Siswa::factory()->count(2)->for($firstClass)->create();

        $this->actingAs($admin)->get(route('admin.siswa.index'))
            ->assertOk()
            ->assertSee('Tingkat Kelas')
            ->assertSee('1')
            ->assertSee('2 rombel')
            ->assertSee('2 siswa')
            ->assertSee(route('admin.siswa.index', ['tingkat' => '1']))
            ->assertSee(route('admin.siswa.index', ['tingkat' => '2']));

        $this->assertDatabaseCount('kelas', 3);
        $this->assertDatabaseCount('siswa', 2);
    }

    public function test_level_cards_show_all_rooms_wali_names_and_empty_class_counts(): void
    {
        $admin = User::factory()->admin()->create();
        $teacherA = $this->createTeacher('Anisa Puspitasari', 'anisa@example.test', 'NIP-ANISA');
        $teacherB = $this->createTeacher('Arif Kurniawan', 'arif@example.test', 'NIP-ARIF');
        $teacherC = $this->createTeacher('Ayu Kartika', 'ayu@example.test', 'NIP-AYU');
        $teacherD = $this->createTeacher('Bayu Aditya', 'bayu@example.test', 'NIP-BAYU');
        $classA = Kelas::factory()->create(['tingkat' => '1', 'nama' => 'A', 'wali_kelas_id' => $teacherA->id]);
        Kelas::factory()->create(['tingkat' => '1', 'nama' => 'B', 'wali_kelas_id' => $teacherB->id]);
        Kelas::factory()->create(['tingkat' => '1', 'nama' => 'C', 'wali_kelas_id' => $teacherC->id]);
        Kelas::factory()->create(['tingkat' => '1', 'nama' => 'D', 'wali_kelas_id' => $teacherD->id]);
        Siswa::factory()->count(2)->for($classA)->create();

        $this->actingAs($admin)->get(route('admin.siswa.index', ['tingkat' => '1']))
            ->assertOk()
            ->assertSee('Rombongan Belajar Tingkat 1')
            ->assertSee('1 A')
            ->assertSee('1 B')
            ->assertSee('1 C')
            ->assertSee('1 D')
            ->assertSee('Anisa Puspitasari')
            ->assertSee('Arif Kurniawan')
            ->assertSee('Ayu Kartika')
            ->assertSee('Bayu Aditya')
            ->assertSee('2 siswa')
            ->assertSee('0 siswa')
            ->assertSee(route('admin.siswa.index', ['kelas_id' => $classA->id]));

        $this->assertDatabaseCount('kelas', 4);
    }

    public static function globalStudentSearchTerms(): array
    {
        return [
            'name' => ['Nama Sasaran'],
            'nis' => ['NIS-SEARCH'],
            'nisn' => ['NISN-SEARCH'],
        ];
    }

    #[DataProvider('globalStudentSearchTerms')]
    public function test_global_search_finds_students_by_name_nis_or_nisn(string $term): void
    {
        $admin = User::factory()->admin()->create();
        $firstClass = Kelas::factory()->create(['tingkat' => '1', 'nama' => 'A']);
        $secondClass = Kelas::factory()->create(['tingkat' => '2', 'nama' => 'B']);
        Siswa::factory()->for($firstClass)->create(['nama' => 'Siswa Lain', 'nis' => 'NIS-LAIN', 'nisn' => 'NISN-LAIN']);
        $target = Siswa::factory()->for($secondClass)->create([
            'nama' => 'Nama Sasaran',
            'nis' => 'NIS-SEARCH',
            'nisn' => 'NISN-SEARCH',
        ]);

        $this->actingAs($admin)->get(route('admin.siswa.index', ['search' => $term]))
            ->assertOk()
            ->assertSee('Nama Sasaran')
            ->assertSee('NIS-SEARCH')
            ->assertSee('NISN-SEARCH')
            ->assertSee('2 B')
            ->assertDontSee('Siswa Lain');

        $this->assertDatabaseHas('siswa', ['id' => $target->id, 'kelas_id' => $secondClass->id]);
    }

    public function test_class_detail_shows_only_its_students_and_preserves_actions(): void
    {
        $admin = User::factory()->admin()->create();
        $kelasA = Kelas::factory()->create(['tingkat' => 'V', 'nama' => 'A']);
        $kelasB = Kelas::factory()->create(['tingkat' => 'V', 'nama' => 'B']);
        $studentA = Siswa::factory()->for($kelasA)->create(['nama' => 'Siswa Kelas A']);
        Siswa::factory()->for($kelasB)->create(['nama' => 'Siswa Kelas B']);

        $this->actingAs($admin)->get(route('admin.siswa.index', ['kelas_id' => $kelasA->id]))
            ->assertOk()
            ->assertSee('Data Siswa')
            ->assertSee('Tingkat V')
            ->assertSee('Siswa Kelas A')
            ->assertDontSee('Siswa Kelas B')
            ->assertSee('1 siswa')
            ->assertSee(route('admin.siswa.create'))
            ->assertSee(route('admin.siswa.import.create'))
            ->assertSee(route('admin.siswa.edit', $studentA))
            ->assertSee(route('admin.siswa.destroy', $studentA));

        $this->assertDatabaseCount('siswa', 2);
    }

    public function test_global_search_pagination_keeps_filters_and_changes_to_first_page(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create(['tingkat' => 'IV', 'nama' => 'A']);
        for ($index = 1; $index <= 12; $index++) {
            Siswa::factory()->for($kelas)->create([
                'nama' => 'Target Siswa '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'nis' => 'NIS-TARGET-'.$index,
            ]);
        }

        $firstPage = $this->actingAs($admin)->get(route('admin.siswa.index', ['search' => 'Target']));
        $firstPage->assertOk()
            ->assertSee('Jumlah hasil: 12 siswa')
            ->assertSee('Target Siswa 01')
            ->assertDontSee('Target Siswa 12')
            ->assertSee('search=Target')
            ->assertSee('page=2');

        $secondPage = $this->actingAs($admin)->get(route('admin.siswa.index', ['search' => 'Target', 'page' => 2]));
        $secondPage->assertOk()
            ->assertSee('Target Siswa 12')
            ->assertDontSee('Target Siswa 01')
            ->assertSee('value="Target"', false)
            ->assertSee('search=Target');

        $changedSearch = $this->actingAs($admin)->get(route('admin.siswa.index', ['search' => 'NIS-TARGET-12']));
        $changedSearch->assertOk()
            ->assertSee('Target Siswa 12')
            ->assertDontSee('Target Siswa 01')
            ->assertSee('value="NIS-TARGET-12"', false);
    }

    public function test_student_navigation_does_not_change_academic_records(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create(['tingkat' => 'VI', 'nama' => 'A']);
        $student = Siswa::factory()->for($kelas)->create(['nama' => 'Tetap Ada']);
        $studentCount = Siswa::query()->count();
        $classCount = Kelas::query()->count();

        $this->actingAs($admin)->get(route('admin.siswa.index'))
            ->assertOk();
        $this->actingAs($admin)->get(route('admin.siswa.index', ['tingkat' => 'VI']))
            ->assertOk();
        $this->actingAs($admin)->get(route('admin.siswa.index', ['kelas_id' => $kelas->id]))
            ->assertOk();

        $this->assertSame($studentCount, Siswa::query()->count());
        $this->assertSame($classCount, Kelas::query()->count());
        $this->assertDatabaseHas('siswa', ['id' => $student->id, 'nama' => 'Tetap Ada', 'kelas_id' => $kelas->id]);
    }

    public function test_admin_can_open_create_form_with_classes_from_database(): void
    {
        $admin = User::factory()->admin()->create();
        Kelas::factory()->create(['tingkat' => 'IX', 'nama' => 'C']);

        $response = $this->actingAs($admin)->get(route('admin.siswa.create'));

        $response->assertOk()
            ->assertSee('Tambah Siswa')
            ->assertSee('NIS')
            ->assertSee('NISN')
            ->assertSee('Nama Siswa')
            ->assertSee('Jenis Kelamin')
            ->assertSee('IX C');
    }

    public function test_admin_can_create_siswa_without_creating_a_user_account(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $payload = $this->validPayload($kelas);
        $payload['user_id'] = 999;
        $userCount = User::query()->count();

        $response = $this->actingAs($admin)->post(route('admin.siswa.store'), $payload);

        $response->assertRedirectToRoute('admin.siswa.index')
            ->assertSessionHas('success', 'Data siswa berhasil ditambahkan.');
        $this->assertDatabaseHas('siswa', [
            'kelas_id' => $kelas->id,
            'nis' => '20260001',
            'nisn' => '0011223344',
            'nama' => 'Nadia Rahma',
            'jenis_kelamin' => 'P',
            'tempat_lahir' => 'Makassar',
            'alamat' => 'Jalan Pendidikan No. 1',
        ]);
        $siswa = Siswa::query()->where('nis', '20260001')->firstOrFail();

        $this->assertSame('2012-05-14', $siswa->tanggal_lahir?->toDateString());
        $this->assertSame($userCount, User::query()->count());
    }

    public function test_create_siswa_requires_database_required_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->from(route('admin.siswa.create'))
            ->post(route('admin.siswa.store'), []);

        $response->assertRedirect(route('admin.siswa.create'))
            ->assertSessionHasErrors([
                'kelas_id' => 'Kelas wajib dipilih.',
                'nis' => 'NIS wajib diisi.',
                'nama' => 'Nama siswa wajib diisi.',
                'jenis_kelamin' => 'Jenis kelamin wajib dipilih.',
            ]);
    }

    public function test_create_siswa_rejects_duplicate_identifiers_and_invalid_references(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        Siswa::factory()->for($kelas)->create([
            'nis' => '20260001',
            'nisn' => '0011223344',
        ]);
        $payload = $this->validPayload($kelas);
        $payload['kelas_id'] = 99999;
        $payload['jenis_kelamin'] = 'X';

        $response = $this->actingAs($admin)
            ->from(route('admin.siswa.create'))
            ->post(route('admin.siswa.store'), $payload);

        $response->assertRedirect(route('admin.siswa.create'))
            ->assertSessionHasErrors([
                'kelas_id' => 'Kelas yang dipilih tidak valid.',
                'nis' => 'NIS sudah digunakan.',
                'nisn' => 'NISN sudah digunakan.',
                'jenis_kelamin' => 'Jenis kelamin yang dipilih tidak valid.',
            ]);
    }

    public function test_admin_can_update_siswa_without_self_duplicate_errors(): void
    {
        $admin = User::factory()->admin()->create();
        $kelasLama = Kelas::factory()->create(['tingkat' => 'VII', 'nama' => 'A']);
        $kelasBaru = Kelas::factory()->create(['tingkat' => 'VIII', 'nama' => 'B']);
        $siswa = Siswa::factory()->for($kelasLama)->create([
            'nis' => '20260001',
            'nisn' => '0011223344',
            'nama' => 'Nama Lama',
        ]);
        $payload = $this->validPayload($kelasBaru);
        $payload['nama'] = 'Nama Baru';

        $response = $this->actingAs($admin)->put(route('admin.siswa.update', $siswa), $payload);

        $response->assertRedirectToRoute('admin.siswa.index')
            ->assertSessionHas('success', 'Data siswa berhasil diperbarui.');
        $this->assertDatabaseHas('siswa', [
            'id' => $siswa->id,
            'kelas_id' => $kelasBaru->id,
            'nis' => '20260001',
            'nisn' => '0011223344',
            'nama' => 'Nama Baru',
        ]);
    }

    public function test_changing_student_class_preserves_existing_score_and_its_original_assignment(): void
    {
        $admin = User::factory()->admin()->create();
        $oldClass = Kelas::factory()->create();
        $newClass = Kelas::factory()->create(['nama' => 'B']);
        $student = Siswa::factory()->for($oldClass)->create();
        $assignment = Pengampu::factory()->create(['kelas_id' => $oldClass->id]);
        $assessment = Penilaian::factory()->for($assignment)->create();
        $score = Nilai::factory()->create([
            'siswa_id' => $student->id,
            'penilaian_id' => $assessment->id,
            'nilai' => 84,
        ]);
        $payload = $this->validPayload($newClass);
        $payload['nis'] = $student->nis;
        $payload['nisn'] = $student->nisn;
        $payload['nama'] = $student->nama;

        $this->actingAs($admin)->put(route('admin.siswa.update', $student), $payload)
            ->assertRedirectToRoute('admin.siswa.index');

        $this->assertDatabaseHas('siswa', ['id' => $student->id, 'kelas_id' => $newClass->id]);
        $this->assertDatabaseHas('nilai', [
            'id' => $score->id,
            'siswa_id' => $student->id,
            'penilaian_id' => $assessment->id,
            'nilai' => 84,
        ]);
        $this->assertSame($assessment->pengampu_id, $score->fresh()->penilaian->pengampu_id);
        $this->assertSame($oldClass->id, $score->fresh()->penilaian->pengampu->kelas_id);
    }

    public function test_guru_cannot_change_a_students_class(): void
    {
        $guru = User::factory()->guru()->create();
        $oldClass = Kelas::factory()->create();
        $newClass = Kelas::factory()->create();
        $student = Siswa::factory()->for($oldClass)->create();
        $payload = $this->validPayload($newClass);
        $payload['nis'] = $student->nis;
        $payload['nisn'] = $student->nisn;
        $payload['nama'] = $student->nama;

        $this->actingAs($guru)->put(route('admin.siswa.update', $student), $payload)
            ->assertForbidden();

        $this->assertDatabaseHas('siswa', ['id' => $student->id, 'kelas_id' => $oldClass->id]);
    }

    public function test_admin_can_delete_siswa_without_nilai(): void
    {
        $admin = User::factory()->admin()->create();
        $siswa = Siswa::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.siswa.destroy', $siswa));

        $response->assertRedirectToRoute('admin.siswa.index')
            ->assertSessionHas('success', 'Data siswa berhasil dihapus.');
        $this->assertModelMissing($siswa);
    }

    public function test_siswa_with_nilai_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $siswa = Siswa::factory()->create();
        Nilai::factory()->create([
            'siswa_id' => $siswa->id,
            'penilaian_id' => Penilaian::factory(),
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.siswa.destroy', $siswa));

        $response->assertRedirectToRoute('admin.siswa.index')
            ->assertSessionHas('error', 'Siswa tidak dapat dihapus karena masih memiliki data nilai.');
        $this->assertModelExists($siswa);
    }

    public function test_database_restricts_deleting_a_student_referenced_by_a_score(): void
    {
        $student = Siswa::factory()->create();
        $assessment = Penilaian::factory()->create();
        Nilai::factory()->create([
            'siswa_id' => $student->id,
            'penilaian_id' => $assessment->id,
        ]);

        $this->expectException(QueryException::class);

        $student->delete();
    }

    public function test_siswa_list_escapes_user_data(): void
    {
        $admin = User::factory()->admin()->create();
        $dangerousName = '<script>alert("xss")</script>';
        $siswa = Siswa::factory()->create(['nama' => $dangerousName]);

        $response = $this->actingAs($admin)->get(route('admin.siswa.index', ['kelas_id' => $siswa->kelas_id]));

        $response->assertSee($dangerousName)
            ->assertDontSee($dangerousName, false);
    }

    /**
     * @return array<string, int|string>
     */
    private function validPayload(Kelas $kelas): array
    {
        return [
            'kelas_id' => $kelas->id,
            'nis' => '20260001',
            'nisn' => '0011223344',
            'nama' => 'Nadia Rahma',
            'jenis_kelamin' => 'P',
            'tanggal_lahir' => '2012-05-14',
            'tempat_lahir' => 'Makassar',
            'alamat' => 'Jalan Pendidikan No. 1',
        ];
    }

    private function createTeacher(string $name, string $email, string $nip): Guru
    {
        $user = User::factory()->guru()->create([
            'name' => $name,
            'email' => $email,
        ]);

        return Guru::factory()->for($user)->create([
            'nama' => $name,
            'nip' => $nip,
        ]);
    }
}
