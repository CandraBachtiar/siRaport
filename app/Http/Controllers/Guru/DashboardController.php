<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Services\GuruDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private GuruDashboardService $dashboardService) {}

    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $guru = $user->guru()->firstOrFail();
        $capabilities = $this->dashboardService->capabilities($guru);

        if (! $capabilities['canAccessMapel'] && $capabilities['canAccessWaliKelas']) {
            return to_route('guru.wali.dashboard');
        }

        return view('guru.mapel-dashboard', [
            'guru' => $guru,
            'user' => $user,
            'workspace' => 'mapel',
            ...$this->dashboardService->mapelDashboard($guru),
        ]);
    }
}
