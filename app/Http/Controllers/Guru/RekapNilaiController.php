<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Pengampu;
use App\Models\Siswa;
use App\Services\GuruDashboardService;
use App\Services\GuruScoreService;
use App\Services\NilaiAkhirSimulasiService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RekapNilaiController extends Controller
{
    public function __construct(
        private GuruDashboardService $dashboardService,
        private GuruScoreService $scoreService,
        private NilaiAkhirSimulasiService $finalScoreSimulationService,
    ) {}

    public function __invoke(Request $request): View
    {
        $simulationOptions = $request->validate([
            'metode_simulasi' => ['nullable', 'string', Rule::in(array_keys(NilaiAkhirSimulasiService::METODE_LABELS))],
            'bobot_tp' => ['sometimes', 'array'],
            'bobot_tp.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        $user = $request->user();
        $guru = $user->guru()->firstOrFail();
        $assignments = Pengampu::query()
            ->where('guru_id', $guru->id)
            ->with(['kelas:id,nama,tingkat', 'mataPelajaran:id,kode,nama', 'tahunAjaran:id,tahun,semester,aktif'])
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('kelas_id')
            ->orderBy('mata_pelajaran_id')
            ->get();
        $assignmentId = $request->integer('pengampu_id');
        $selectedAssignment = $assignmentId > 0
            ? $assignments->firstWhere('id', $assignmentId)
            : $assignments->first(fn (Pengampu $assignment): bool => (bool) $assignment->tahunAjaran?->aktif) ?? $assignments->first();

        if ($assignmentId > 0 && $selectedAssignment === null) {
            abort(404);
        }

        $recap = $selectedAssignment === null
            ? ['students' => collect(), 'assessments' => collect(), 'goalSummaries' => collect(), 'calculationMode' => 'Rekap terpisah per TP dan kategori asesmen']
            : $this->scoreService->recap($selectedAssignment);
        $simulationMethod = $simulationOptions['metode_simulasi'] ?? null;
        $simulationWeights = $simulationOptions['bobot_tp'] ?? [];
        $simulations = $recap['students']->mapWithKeys(fn (Siswa $student): array => [
            $student->id => $this->finalScoreSimulationService->simulate(
                $recap['goalSummaries'],
                $recap['assessments'],
                $student->id,
                $simulationMethod,
                $simulationWeights,
            ),
        ]);

        return view('guru.rekap-nilai.index', [
            'user' => $user,
            'guru' => $guru,
            'workspace' => 'mapel',
            'assignments' => $assignments,
            'selectedAssignment' => $selectedAssignment,
            'simulationMethod' => $simulationMethod,
            'simulationMethods' => NilaiAkhirSimulasiService::METODE_LABELS,
            'simulationWeights' => $simulationWeights,
            'simulations' => $simulations,
            ...$recap,
            ...$this->dashboardService->capabilities($guru),
        ]);
    }
}
