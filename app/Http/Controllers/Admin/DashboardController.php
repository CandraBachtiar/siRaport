<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $activeSchoolYear = TahunAjaran::query()
            ->where('aktif', true)
            ->orderByDesc('id')
            ->first(['id', 'tahun', 'semester']);
        $guruCount = DB::table('guru')->count();
        $siswaCount = DB::table('siswa')->count();
        $kelasCount = DB::table('kelas')->count();
        $mataPelajaranCount = DB::table('mata_pelajaran')->count();
        $pengampuCount = DB::table('pengampu')->count();

        return view('admin.dashboard', [
            'admin' => $request->user(),
            'statistics' => [
                'guru' => $guruCount,
                'siswa' => $siswaCount,
                'kelas' => $kelasCount,
                'mata_pelajaran' => $mataPelajaranCount,
                'pengampu' => $pengampuCount,
                'active_school_year' => $activeSchoolYear,
            ],
            'academicChecklist' => [
                [
                    'label' => 'Tahun ajaran aktif sudah tersedia',
                    'complete' => $activeSchoolYear !== null,
                    'route' => 'admin.tahun-ajaran.create',
                    'action' => 'Buat Tahun Ajaran',
                ],
                [
                    'label' => 'Data guru sudah tersedia',
                    'complete' => $guruCount > 0,
                    'route' => 'admin.guru.create',
                    'action' => 'Tambah Guru',
                ],
                [
                    'label' => 'Kelas sudah dibuat',
                    'complete' => $kelasCount > 0,
                    'route' => 'admin.kelas.create',
                    'action' => 'Buat Kelas',
                ],
                [
                    'label' => 'Data siswa sudah tersedia',
                    'complete' => $siswaCount > 0,
                    'route' => 'admin.siswa.create',
                    'action' => 'Tambah Siswa',
                ],
                [
                    'label' => 'Mata pelajaran sudah tersedia',
                    'complete' => $mataPelajaranCount > 0,
                    'route' => 'admin.mata-pelajaran.create',
                    'action' => 'Tambah Mata Pelajaran',
                ],
                [
                    'label' => 'Pengampu sudah ditentukan',
                    'complete' => $pengampuCount > 0,
                    'route' => 'admin.pengampu.create',
                    'action' => 'Atur Pengampu',
                ],
            ],
        ]);
    }
}
