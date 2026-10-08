<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveDeskripsiRaporRequest;
use App\Models\Pengampu;
use App\Models\Siswa;
use App\Services\GuruDashboardService;
use App\Services\WaliKelasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeskripsiRaporController extends Controller
{
    public function __construct(
        private WaliKelasService $waliKelasService,
        private GuruDashboardService $dashboardService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $guru = $user->guru()->firstOrFail();
        $data = $this->waliKelasService->workspace(
            $guru,
            $request->integer('kelas_id') ?: null,
            $request->integer('tahun_ajaran_id') ?: null,
        );
        $studentId = $request->integer('siswa_id');
        $assignmentId = $request->integer('pengampu_id');
        $studentRow = $studentId > 0
            ? $data['studentRows']->firstWhere('student.id', $studentId)
            : $data['studentRows']->first();

        if ($studentId > 0 && $studentRow === null) {
            abort(404);
        }

        $subject = $studentRow === null
            ? null
            : ($assignmentId > 0
                ? $studentRow['subjects']->firstWhere('assignment.id', $assignmentId)
                : $studentRow['subjects']->first());

        if ($assignmentId > 0 && $subject === null) {
            abort(404);
        }

        return view('guru.wali.deskripsi.index', [
            'user' => $user,
            'guru' => $guru,
            'workspace' => 'wali',
            ...$data,
            ...$this->dashboardService->capabilities($guru),
            'studentRow' => $studentRow,
            'subject' => $subject,
        ]);
    }

    public function update(SaveDeskripsiRaporRequest $request, Pengampu $pengampu, Siswa $siswa): RedirectResponse
    {
        $summary = $this->waliKelasService->subjectSummary($pengampu, $siswa);

        if ($request->string('status')->toString() === 'tervalidasi' && ! $summary['isComplete']) {
            return back()->withInput()->with('error', 'Lengkapi seluruh nilai mata pelajaran sebelum memvalidasi deskripsi.');
        }

        $description = $this->waliKelasService->saveDescription(
            $pengampu,
            $siswa,
            $request->string('deskripsi_akhir')->trim()->toString(),
            $request->string('status')->toString(),
        );

        if ($description === null) {
            return back()->withInput()->with('error', 'Deskripsi belum dapat disimpan karena siswa belum memiliki nilai pada mata pelajaran ini.');
        }

        return redirect()->route('guru.wali.deskripsi.index', [
            'kelas_id' => $pengampu->kelas_id,
            'tahun_ajaran_id' => $pengampu->tahun_ajaran_id,
            'siswa_id' => $siswa->id,
            'pengampu_id' => $pengampu->id,
        ])->with('success', $description->status === 'tervalidasi'
            ? 'Deskripsi rapor berhasil divalidasi.'
            : 'Draf deskripsi rapor berhasil disimpan.');
    }
}
