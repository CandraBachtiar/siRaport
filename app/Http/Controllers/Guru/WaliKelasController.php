<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Services\GuruDashboardService;
use App\Services\WaliKelasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WaliKelasController extends Controller
{
    public function __construct(
        private WaliKelasService $waliKelasService,
        private GuruDashboardService $dashboardService,
    ) {}

    public function students(Request $request): View
    {
        $data = $this->viewData($request);
        $search = Str::lower($request->string('q')->trim()->toString());
        $rows = $data['studentRows']
            ->when($search !== '', fn ($items) => $items->filter(function (array $row) use ($search): bool {
                $student = $row['student'];

                return Str::contains(Str::lower($student->nama.' '.$student->nis.' '.$student->nisn), $search);
            }))
            ->values();

        return view('guru.wali.siswa.index', [
            ...$data,
            'rows' => $rows,
            'search' => $request->string('q')->trim()->toString(),
        ]);
    }

    public function student(Request $request, Siswa $siswa): View
    {
        $data = $this->viewData($request, $siswa->kelas_id);
        $studentRow = $data['studentRows']->firstWhere('student.id', $siswa->id);

        if ($studentRow === null) {
            abort(404);
        }

        return view('guru.wali.siswa.show', [...$data, 'studentRow' => $studentRow]);
    }

    public function progress(Request $request): View
    {
        $data = $this->viewData($request);
        $studentId = $request->integer('siswa_id');
        $studentRow = $studentId > 0
            ? $data['studentRows']->firstWhere('student.id', $studentId)
            : $data['studentRows']->first();

        if ($studentId > 0 && $studentRow === null) {
            abort(404);
        }

        return view('guru.wali.perkembangan.index', [...$data, 'studentRow' => $studentRow]);
    }

    public function attention(Request $request): View
    {
        return view('guru.wali.perhatian.index', $this->viewData($request));
    }

    public function reports(Request $request): View
    {
        return view('guru.wali.rapor.index', $this->viewData($request));
    }

    public function report(Request $request, Siswa $siswa): View
    {
        $data = $this->viewData($request, $siswa->kelas_id);
        $studentRow = $data['studentRows']->firstWhere('student.id', $siswa->id);

        if ($studentRow === null) {
            abort(404);
        }

        return view('guru.wali.rapor.show', [...$data, 'studentRow' => $studentRow]);
    }

    public function print(Request $request, Siswa $siswa): View|RedirectResponse
    {
        $data = $this->viewData($request, $siswa->kelas_id);
        $studentRow = $data['studentRows']->firstWhere('student.id', $siswa->id);

        if ($studentRow === null) {
            abort(404);
        }

        if (! $studentRow['readyToPrint']) {
            return redirect()->route('guru.wali.rapor.show', [
                'siswa' => $siswa,
                'tahun_ajaran_id' => $data['selectedSchoolYear']?->id,
            ])->with('error', 'Lengkapi nilai dan validasi seluruh deskripsi sebelum mencetak rapor.');
        }

        return view('guru.wali.rapor.print', [...$data, 'studentRow' => $studentRow]);
    }

    public function profile(Request $request): View
    {
        return view('guru.wali.profil.show', $this->viewData($request));
    }

    /** @return array<string, mixed> */
    private function viewData(Request $request, ?int $forcedClassId = null): array
    {
        $user = $request->user();
        $guru = $user->guru()->firstOrFail();

        return [
            'user' => $user,
            'guru' => $guru,
            'workspace' => 'wali',
            ...$this->waliKelasService->workspace(
                $guru,
                $forcedClassId ?? ($request->integer('kelas_id') ?: null),
                $request->integer('tahun_ajaran_id') ?: null,
            ),
            ...$this->dashboardService->capabilities($guru),
        ];
    }
}
