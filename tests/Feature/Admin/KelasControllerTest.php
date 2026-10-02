<?php

namespace Tests\Feature\Admin;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class KelasControllerTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.kelas.index'));

        $response->assertRedirectToRoute('login');
    }

    public function test_guru_is_forbidden_from_data_kelas(): void
    {
        $guru = User::factory()->guru()->create();

        $response = $this->actingAs($guru)->get(route('admin.kelas.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_classes_with_homeroom_teacher_and_student_count(): void
    {
        $admin = User::factory()->admin()->create();
        $guruUser = User::factory()->guru()->create(['name' => 'Budi Santoso']);
        $guru = Guru::factory()->for($guruUser)->create(['nama' => 'Budi Santoso']);
        $kelas = Kelas::factory()->for($guru, 'waliKelas')->create([
            'tingkat' => 'VII',
            'nama' => 'A',
        ]);
        Siswa::factory()->count(2)->for($kelas)->create();

        $response = $this->actingAs($admin)->get(route('admin.kelas.index'));

        $response->assertOk()
            ->assertSee('VII A')
            ->assertSee('Budi Santoso')
            ->assertSee('2 siswa');
    }

    public function test_admin_can_open_create_form_with_gurus_from_database(): void
    {
        $admin = User::factory()->admin()->create();
        $guruUser = User::factory()->guru()->create(['name' => 'Siti Rahma']);
        Guru::factory()->for($guruUser)->create(['nip' => '198701012012022001']);

        $response = $this->actingAs($admin)->get(route('admin.kelas.create'));

        $response->assertOk()
            ->assertSee('Tambah Kelas')
            ->assertSee('Tingkat')
            ->assertSee('Nama/Rombel')
            ->assertSee('Siti Rahma')
            ->assertSee('198701012012022001');
    }

    public function test_admin_can_create_class_without_creating_a_new_guru_or_user(): void
    {
        $admin = User::factory()->admin()->create();
        $guru = Guru::factory()->create();
        $userCount = User::query()->count();
        $guruCount = Guru::query()->count();

        $response = $this->actingAs($admin)->post(route('admin.kelas.store'), [
            'tingkat' => 'VIII',
            'nama' => 'B',
            'wali_kelas_id' => $guru->id,
            'user_id' => 999,
        ]);

        $response->assertRedirectToRoute('admin.kelas.index')
            ->assertSessionHas('success', 'Data kelas berhasil ditambahkan.');
        $this->assertDatabaseHas('kelas', [
            'tingkat' => 'VIII',
            'nama' => 'B',
            'wali_kelas_id' => $guru->id,
        ]);
        $this->assertSame($userCount, User::query()->count());
        $this->assertSame($guruCount, Guru::query()->count());
    }

    public function test_admin_can_create_class_without_a_homeroom_teacher(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.kelas.store'), [
            'tingkat' => 'IX',
            'nama' => 'C',
            'wali_kelas_id' => '',
        ]);

        $response->assertRedirectToRoute('admin.kelas.index');
        $this->assertDatabaseHas('kelas', [
            'tingkat' => 'IX',
            'nama' => 'C',
            'wali_kelas_id' => null,
        ]);
    }

    public function test_create_class_requires_actual_database_required_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->from(route('admin.kelas.create'))
            ->post(route('admin.kelas.store'), []);

        $response->assertRedirect(route('admin.kelas.create'))
            ->assertSessionHasErrors([
                'tingkat' => 'Tingkat kelas wajib diisi.',
                'nama' => 'Nama kelas wajib diisi.',
            ]);
    }

    public function test_create_class_rejects_an_unknown_homeroom_teacher(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->from(route('admin.kelas.create'))
            ->post(route('admin.kelas.store'), [
                'tingkat' => 'VII',
                'nama' => 'A',
                'wali_kelas_id' => 99999,
            ]);

        $response->assertRedirect(route('admin.kelas.create'))
            ->assertSessionHasErrors([
                'wali_kelas_id' => 'Wali kelas yang dipilih tidak valid.',
            ]);
        $this->assertDatabaseMissing('kelas', [
            'tingkat' => 'VII',
            'nama' => 'A',
        ]);
    }

    public function test_admin_can_update_class_and_homeroom_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $guruLama = Guru::factory()->create();
        $guruBaru = Guru::factory()->create();
        $kelas = Kelas::factory()->for($guruLama, 'waliKelas')->create([
            'tingkat' => 'VII',
            'nama' => 'A',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.kelas.update', $kelas), [
            'tingkat' => 'VIII',
            'nama' => 'B',
            'wali_kelas_id' => $guruBaru->id,
        ]);

        $response->assertRedirectToRoute('admin.kelas.index')
            ->assertSessionHas('success', 'Data kelas berhasil diperbarui.');
        $this->assertDatabaseHas('kelas', [
            'id' => $kelas->id,
            'tingkat' => 'VIII',
            'nama' => 'B',
            'wali_kelas_id' => $guruBaru->id,
        ]);
    }

    public function test_update_class_rejects_an_unknown_homeroom_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $guru = Guru::factory()->create();
        $kelas = Kelas::factory()->for($guru, 'waliKelas')->create([
            'tingkat' => 'VII',
            'nama' => 'A',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.kelas.edit', $kelas))
            ->put(route('admin.kelas.update', $kelas), [
                'tingkat' => 'VIII',
                'nama' => 'B',
                'wali_kelas_id' => 99999,
            ]);

        $response->assertRedirect(route('admin.kelas.edit', $kelas))
            ->assertSessionHasErrors([
                'wali_kelas_id' => 'Wali kelas yang dipilih tidak valid.',
            ]);
        $this->assertDatabaseHas('kelas', [
            'id' => $kelas->id,
            'tingkat' => 'VII',
            'nama' => 'A',
            'wali_kelas_id' => $guru->id,
        ]);
    }

    public function test_admin_can_delete_unused_class(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.kelas.destroy', $kelas));

        $response->assertRedirectToRoute('admin.kelas.index')
            ->assertSessionHas('success', 'Data kelas berhasil dihapus.');
        $this->assertModelMissing($kelas);
    }

    public function test_class_with_students_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->for($kelas)->create();

        $response = $this->actingAs($admin)->delete(route('admin.kelas.destroy', $kelas));

        $response->assertRedirectToRoute('admin.kelas.index')
            ->assertSessionHas('error', 'Kelas tidak dapat dihapus karena masih memiliki siswa.');
        $this->assertModelExists($kelas);
        $this->assertModelExists($siswa);
    }

    public function test_class_used_by_pengampu_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $guru = Guru::factory()->create();
        $kelas = Kelas::factory()->create();
        DB::table('pengampu')->insert([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.kelas.destroy', $kelas));

        $response->assertRedirectToRoute('admin.kelas.index')
            ->assertSessionHas('error', 'Kelas tidak dapat dihapus karena masih digunakan pada data pengampu.');
        $this->assertModelExists($kelas);
    }

    public function test_class_list_escapes_user_data(): void
    {
        $admin = User::factory()->admin()->create();
        $dangerousName = '<script>alert("xss")</script>';
        $guruUser = User::factory()->guru()->create(['name' => $dangerousName]);
        $guru = Guru::factory()->for($guruUser)->create(['nama' => $dangerousName]);
        Kelas::factory()->for($guru, 'waliKelas')->create(['nama' => $dangerousName]);

        $response = $this->actingAs($admin)->get(route('admin.kelas.index'));

        $response->assertSee($dangerousName)
            ->assertDontSee($dangerousName, false);
    }
}
