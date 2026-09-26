<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('admin.dashboard', [
            'admin' => $request->user(),
            'statistics' => [
                'guru' => DB::table('guru')->count(),
                'siswa' => DB::table('siswa')->count(),
                'kelas' => DB::table('kelas')->count(),
                'mata_pelajaran' => DB::table('mata_pelajaran')->count(),
            ],
        ]);
    }
}
