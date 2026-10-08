<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePenilaianRequest;
use App\Http\Requests\UpdatePenilaianRequest;
use App\Models\Pengampu;
use App\Models\Penilaian;
use App\Services\GuruDashboardService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PenilaianController extends Controller
{
    public function __construct(private GuruDashboardService $dashboardService) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Penilaian::class);
        $user = $request->user();
        $guru = $user->guru()->firstOrFail();
        $assignmentId = $request->integer('pengampu_id');
        $assignments = $this->assignments($guru->id);

        if ($assignmentId > 0 && $assignments->doesntContain('id', $assignmentId)) {
            abort(404);
        }

        $assessments = Penilaian::query()
            ->whereHas('pengampu', fn (Builder $query): Builder => $query->where('guru_id', $guru->id))
            ->when($assignmentId > 0, fn (Builder $query): Builder => $query->where('pengampu_id', $assignmentId))
            ->with([
                'pengampu:id,guru_id,kelas_id,mata_pelajaran_id,tahun_ajaran_id',
                'pengampu.kelas:id,nama,tingkat',
                'pengampu.mataPelajaran:id,kode,nama',
                'pengampu.tahunAjaran:id,tahun,semester',
            ])
            ->withCount('nilai')
            ->orderByDesc('tanggal')
            ->orderBy('urutan')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        return view('guru.penilaian.index', [
            'user' => $user,
            'guru' => $guru,
            'workspace' => 'mapel',
            'assignments' => $assignments,
            'assessments' => $assessments,
            'assignmentId' => $assignmentId,
            ...$this->dashboardService->capabilities($guru),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Penilaian::class);
        $user = $request->user();
        $guru = $user->guru()->firstOrFail();
        $assignments = $this->assignments($guru->id);
        $selectedAssignmentId = $request->integer('pengampu_id');

        if ($selectedAssignmentId > 0 && $assignments->doesntContain('id', $selectedAssignmentId)) {
            abort(404);
        }

        return view('guru.penilaian.create', [
            'user' => $user,
            'guru' => $guru,
            'workspace' => 'mapel',
            'assignments' => $assignments,
            'selectedAssignmentId' => $selectedAssignmentId,
            ...$this->dashboardService->capabilities($guru),
        ]);
    }

    public function store(StorePenilaianRequest $request): RedirectResponse
    {
        $assessment = Penilaian::query()->create($request->validated());

        return redirect()
            ->route('guru.penilaian.index', ['pengampu_id' => $assessment->pengampu_id])
            ->with('success', 'Penilaian berhasil ditambahkan.');
    }

    public function edit(Request $request, Penilaian $penilaian): View
    {
        Gate::authorize('update', $penilaian);
        $user = $request->user();
        $guru = $user->guru()->firstOrFail();
        $penilaian->load(['pengampu.kelas', 'pengampu.mataPelajaran', 'pengampu.tahunAjaran']);

        return view('guru.penilaian.edit', [
            'user' => $user,
            'guru' => $guru,
            'workspace' => 'mapel',
            'assessment' => $penilaian,
            ...$this->dashboardService->capabilities($guru),
        ]);
    }

    public function update(UpdatePenilaianRequest $request, Penilaian $penilaian): RedirectResponse
    {
        $penilaian->update($request->validated());

        return redirect()
            ->route('guru.penilaian.index', ['pengampu_id' => $penilaian->pengampu_id])
            ->with('success', 'Penilaian berhasil diperbarui.');
    }

    public function destroy(Request $request, Penilaian $penilaian): RedirectResponse
    {
        Gate::authorize('delete', $penilaian);

        $deleted = DB::transaction(function () use ($penilaian, $request): bool {
            $lockedAssessment = Penilaian::query()
                ->with('pengampu:id,guru_id')
                ->lockForUpdate()
                ->findOrFail($penilaian->id);
            Gate::forUser($request->user())->authorize('delete', $lockedAssessment);

            if ($lockedAssessment->nilai()->exists()) {
                return false;
            }

            $lockedAssessment->delete();

            return true;
        });

        if (! $deleted) {
            return back()->with('error', 'Penilaian tidak dapat dihapus karena sudah memiliki nilai siswa.');
        }

        return redirect()->route('guru.penilaian.index')->with('success', 'Penilaian berhasil dihapus.');
    }

    /** @return Collection<int, Pengampu> */
    private function assignments(int $guruId): Collection
    {
        return Pengampu::query()
            ->where('guru_id', $guruId)
            ->with(['kelas:id,nama,tingkat', 'mataPelajaran:id,kode,nama', 'tahunAjaran:id,tahun,semester,aktif'])
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('kelas_id')
            ->orderBy('mata_pelajaran_id')
            ->get();
    }
}
