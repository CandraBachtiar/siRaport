<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GuruController;
use App\Http\Controllers\Admin\GuruImportController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\MataPelajaranController;
use App\Http\Controllers\Admin\PengampuController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\TahunAjaranController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Guru\AnalisisNilaiController;
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\Guru\DeskripsiRaporController;
use App\Http\Controllers\Guru\InputNilaiController;
use App\Http\Controllers\Guru\KelasMataPelajaranController;
use App\Http\Controllers\Guru\PenilaianController;
use App\Http\Controllers\Guru\RekapNilaiController;
use App\Http\Controllers\Guru\SiswaPerluPerhatianController;
use App\Http\Controllers\Guru\WaliKelasController;
use App\Http\Controllers\Guru\WaliKelasDashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');
Route::view('/tentang', 'pages.tentang')->name('tentang');
Route::view('/panduan', 'pages.panduan')->name('panduan');
Route::view('/bantuan', 'pages.bantuan')->name('bantuan');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'admin', 'cache.headers:no_store;no_cache;must_revalidate'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/guru/import', [GuruImportController::class, 'create'])->name('guru.import.create');
        Route::get('/guru/import/template', [GuruImportController::class, 'template'])->name('guru.template');
        Route::post('/guru/import/preview', [GuruImportController::class, 'preview'])->name('guru.import.preview');
        Route::post('/guru/import', [GuruImportController::class, 'import'])->name('guru.import.store');
        Route::resource('guru', GuruController::class)->except('show');
        Route::resource('siswa', SiswaController::class)->except('show');
        Route::resource('kelas', KelasController::class)
            ->except('show')
            ->parameters(['kelas' => 'kelas']);
        Route::resource('mata-pelajaran', MataPelajaranController::class)
            ->except('show')
            ->parameters(['mata-pelajaran' => 'mata_pelajaran']);
        Route::resource('tahun-ajaran', TahunAjaranController::class)
            ->except('show')
            ->parameters(['tahun-ajaran' => 'tahun_ajaran']);
        Route::resource('pengampu', PengampuController::class)->except('show');
    });

Route::middleware(['auth', 'guru', 'cache.headers:no_store;no_cache;must_revalidate'])
    ->prefix('guru')
    ->name('guru.')
    ->group(function (): void {
        Route::get('/dashboard', GuruDashboardController::class)->name('dashboard');
        Route::get('/mapel/dashboard', GuruDashboardController::class)
            ->middleware('guru.mapel')
            ->name('mapel.dashboard');
        Route::get('/wali-kelas/dashboard', WaliKelasDashboardController::class)
            ->middleware('guru.wali')
            ->name('wali.dashboard');

        Route::middleware('guru.wali')->prefix('wali-kelas')->name('wali.')->group(function (): void {
            Route::get('/siswa', [WaliKelasController::class, 'students'])->name('siswa.index');
            Route::get('/siswa/{siswa}', [WaliKelasController::class, 'student'])->name('siswa.show');
            Route::get('/perkembangan', [WaliKelasController::class, 'progress'])->name('perkembangan.index');
            Route::get('/siswa-perlu-perhatian', [WaliKelasController::class, 'attention'])->name('perhatian.index');
            Route::get('/deskripsi-rapor', [DeskripsiRaporController::class, 'index'])->name('deskripsi.index');
            Route::put('/deskripsi-rapor/{pengampu}/{siswa}', [DeskripsiRaporController::class, 'update'])->name('deskripsi.update');
            Route::get('/rapor', [WaliKelasController::class, 'reports'])->name('rapor.index');
            Route::get('/rapor/{siswa}', [WaliKelasController::class, 'report'])->name('rapor.show');
            Route::get('/rapor/{siswa}/cetak', [WaliKelasController::class, 'print'])->name('rapor.print');
            Route::get('/profil', [WaliKelasController::class, 'profile'])->name('profil.show');
        });

        Route::middleware('guru.mapel')->group(function (): void {
            Route::get('/kelas-mata-pelajaran', [KelasMataPelajaranController::class, 'index'])->name('kelas-mapel.index');
            Route::get('/kelas-mata-pelajaran/{pengampu}', [KelasMataPelajaranController::class, 'show'])->name('kelas-mapel.show');
            Route::resource('penilaian', PenilaianController::class)->except('show');
            Route::get('/input-nilai', [InputNilaiController::class, 'index'])->name('nilai.index');
            Route::put('/input-nilai/{penilaian}', [InputNilaiController::class, 'update'])->name('nilai.update');
            Route::get('/analisis-nilai', AnalisisNilaiController::class)->name('analisis.index');
            Route::get('/siswa-perlu-perhatian', SiswaPerluPerhatianController::class)->name('perhatian.index');
            Route::get('/rekap-nilai', RekapNilaiController::class)->name('rekap-nilai.index');
        });
    });
