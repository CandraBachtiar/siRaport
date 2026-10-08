<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AcademicDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $guruUser = User::updateOrCreate(
                ['email' => 'sulaiman@gmail.com'],
                [
                    'name' => 'Sulaiman',
                    'password' => Hash::make('password'),
                    'role' => 'guru',
                ],
            );

            $guru = Guru::updateOrCreate(
                ['user_id' => $guruUser->id],
                [
                    'nip' => '2410182-122392',
                    'nama' => 'Sulaiman',
                    'no_hp' => null,
                    'alamat' => null,
                ],
            );

            $kelas = Kelas::updateOrCreate(
                ['nama' => 'A', 'tingkat' => 'V'],
                ['wali_kelas_id' => $guru->id],
            );

            Siswa::firstOrCreate(
                ['kelas_id' => $kelas->id, 'nama' => 'Badrul Azami'],
                [
                    'nis' => '23423123312',
                    'nisn' => '123124124123',
                    'jenis_kelamin' => 'L',
                    'tanggal_lahir' => '2010-09-10',
                    'tempat_lahir' => 'Bandung',
                    'alamat' => 'Jl. Pemuda III',
                ],
            );

            $mataPelajaran = MataPelajaran::updateOrCreate(
                ['kode' => '001'],
                ['nama' => 'Matematika', 'kkm' => 75],
            );

            $tahunAjaran = TahunAjaran::updateOrCreate(
                ['tahun' => '2025/2026', 'semester' => 'Ganjil'],
                ['aktif' => true],
            );

            TahunAjaran::query()
                ->whereKeyNot($tahunAjaran->id)
                ->where('aktif', true)
                ->update(['aktif' => false]);

            Pengampu::updateOrCreate(
                [
                    'guru_id' => $guru->id,
                    'mata_pelajaran_id' => $mataPelajaran->id,
                    'kelas_id' => $kelas->id,
                    'tahun_ajaran_id' => $tahunAjaran->id,
                ],
                [],
            );
        });
    }
}
