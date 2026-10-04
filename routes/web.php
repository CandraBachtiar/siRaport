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
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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
