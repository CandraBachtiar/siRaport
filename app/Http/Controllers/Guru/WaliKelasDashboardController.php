<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Services\GuruDashboardService;
use App\Services\WaliKelasService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WaliKelasDashboardController extends Controller
{
    public function __construct(
        private GuruDashboardService $dashboardService,
        private WaliKelasService $waliKelasService,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $guru = $user->guru()->firstOrFail();

        return view('guru.wali-kelas-dashboard', [
            'guru' => $guru,
            'user' => $user,
            'workspace' => 'wali',
            ...$this->waliKelasService->workspace(
                $guru,
                $request->integer('kelas_id') ?: null,
                $request->integer('tahun_ajaran_id') ?: null,
            ),
            ...$this->dashboardService->capabilities($guru),
        ]);
    }
}
