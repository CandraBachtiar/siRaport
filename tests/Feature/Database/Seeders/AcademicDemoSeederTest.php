<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Database\Seeders\AcademicDemoSeeder;
use Tests\Concerns\CreatesAdminTestSchema;
use Tests\TestCase;

class AcademicDemoSeederTest extends TestCase
{
    use CreatesAdminTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminTestSchema();
    }

    public function test_seeder_creates_the_academic_demo_dataset_without_duplicates(): void
    {
        $this->seed(AcademicDemoSeeder::class);
        $this->seed(AcademicDemoSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'sulaiman@gmail.com')->count());
        $this->assertSame(1, Guru::query()->where('nip', '2410182-122392')->count());
        $this->assertSame(1, Kelas::query()->where('nama', 'A')->where('tingkat', 'V')->count());
        $this->assertSame(1, Siswa::query()->where('nama', 'Badrul Azami')->count());
        $this->assertSame(1, MataPelajaran::query()->where('kode', '001')->count());
        $this->assertSame(1, TahunAjaran::query()->where('tahun', '2025/2026')->where('semester', 'Ganjil')->count());
        $this->assertSame(1, Pengampu::query()->count());
    }

    public function test_seeder_creates_valid_academic_relationships_and_one_active_year(): void
    {
        $previousYear = TahunAjaran::create([
            'tahun' => '2024/2025',
            'semester' => 'Genap',
            'aktif' => true,
        ]);

        $this->seed(AcademicDemoSeeder::class);

        $guru = Guru::query()->where('nip', '2410182-122392')->firstOrFail();
        $kelas = Kelas::query()->where('nama', 'A')->where('tingkat', 'V')->firstOrFail();
        $pengampu = Pengampu::query()->with(['guru.user', 'mataPelajaran', 'kelas', 'tahunAjaran'])->firstOrFail();

        $this->assertSame('sulaiman@gmail.com', $guru->user->email);
        $this->assertSame($guru->id, $kelas->wali_kelas_id);
        $this->assertSame($guru->id, $pengampu->guru_id);
        $this->assertSame('Sulaiman', $pengampu->guru->nama);
        $this->assertSame('Matematika', $pengampu->mataPelajaran->nama);
        $this->assertSame('A', $pengampu->kelas->nama);
        $this->assertSame('V', $pengampu->kelas->tingkat);
        $this->assertSame('2025/2026', $pengampu->tahunAjaran->tahun);
        $this->assertSame('Ganjil', $pengampu->tahunAjaran->semester);
        $this->assertFalse($previousYear->fresh()->aktif);
        $this->assertSame(1, TahunAjaran::query()->where('aktif', true)->count());
    }
}
