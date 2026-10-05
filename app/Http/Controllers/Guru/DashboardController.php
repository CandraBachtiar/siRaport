<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $guru = $user->guru()->firstOrFail();
        $pengampu = $guru->pengampu()
            ->with(['mataPelajaran', 'kelas', 'tahunAjaran'])
            ->orderBy('id')
            ->get();

        return view('guru.dashboard', [
            'guru' => $guru,
            'user' => $user,
            'pengampu' => $pengampu,
        ]);
    }
}
