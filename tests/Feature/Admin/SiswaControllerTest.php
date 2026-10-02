<?php

namespace Tests\Feature\Admin;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
        DB::table('nilai')->insert(['siswa_id' => $siswa->id]);

        $response = $this->actingAs($admin)->delete(route('admin.siswa.destroy', $siswa));

        $response->assertRedirectToRoute('admin.siswa.index')
            ->assertSessionHas('error', 'Siswa tidak dapat dihapus karena masih memiliki data nilai.');
        $this->assertModelExists($siswa);
    }

    public function test_siswa_list_escapes_user_data(): void
    {
        $admin = User::factory()->admin()->create();
        $dangerousName = '<script>alert("xss")</script>';
        Siswa::factory()->create(['nama' => $dangerousName]);

        $response = $this->actingAs($admin)->get(route('admin.siswa.index'));

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
}
