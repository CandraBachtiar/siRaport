<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTujuanPembelajaranRequest;
use App\Http\Requests\UpdateTujuanPembelajaranRequest;
use App\Models\Pengampu;
use App\Models\TujuanPembelajaran;
use App\Services\GuruDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TujuanPembelajaranController extends Controller
{
    public function __construct(private GuruDashboardService $dashboardService) {}

    public function index(Request $request, Pengampu $pengampu): View
    {
        Gate::authorize('view', $pengampu);
        $pengampu->load(['kelas:id,nama,tingkat', 'mataPelajaran:id,kode,nama', 'tahunAjaran:id,tahun,semester']);
        $tujuanPembelajaran = $pengampu->tujuanPembelajaran()->withCount('penilaian')->get();

        return view('guru.tujuan-pembelajaran.index', [
            ...$this->viewData($request, $pengampu),
            'tujuanPembelajaran' => $tujuanPembelajaran,
            'unmappedAssessmentCount' => $pengampu->penilaian()->whereNull('tujuan_pembelajaran_id')->count(),
        ]);
    }

    public function create(Request $request, Pengampu $pengampu): View
    {
        Gate::authorize('view', $pengampu);
        $pengampu->load(['kelas:id,nama,tingkat', 'mataPelajaran:id,kode,nama', 'tahunAjaran:id,tahun,semester']);

        return view('guru.tujuan-pembelajaran.create', [
            ...$this->viewData($request, $pengampu),
            'tujuan' => null,
            'nextOrder' => (int) $pengampu->tujuanPembelajaran()->max('urutan') + 1,
        ]);
    }

    public function store(StoreTujuanPembelajaranRequest $request, Pengampu $pengampu): RedirectResponse
    {
        $pengampu->tujuanPembelajaran()->create($request->validated());

        return to_route('guru.tujuan-pembelajaran.index', $pengampu)
            ->with('success', 'Tujuan pembelajaran berhasil ditambahkan.');
    }

    public function edit(Request $request, Pengampu $pengampu, TujuanPembelajaran $tujuan_pembelajaran): View
    {
        $tujuanPembelajaran = $this->scopedTujuanPembelajaran($pengampu, $tujuan_pembelajaran);
        Gate::authorize('update', $tujuanPembelajaran);
        $pengampu->load(['kelas:id,nama,tingkat', 'mataPelajaran:id,kode,nama', 'tahunAjaran:id,tahun,semester']);

        return view('guru.tujuan-pembelajaran.edit', [
            ...$this->viewData($request, $pengampu),
            'tujuan' => $tujuanPembelajaran,
        ]);
    }

    public function update(
        UpdateTujuanPembelajaranRequest $request,
        Pengampu $pengampu,
        TujuanPembelajaran $tujuan_pembelajaran,
    ): RedirectResponse {
        $tujuanPembelajaran = $this->scopedTujuanPembelajaran($pengampu, $tujuan_pembelajaran);
        $tujuanPembelajaran->update($request->validated());

        return to_route('guru.tujuan-pembelajaran.index', $pengampu)
            ->with('success', 'Tujuan pembelajaran berhasil diperbarui.');
    }

    public function destroy(Request $request, Pengampu $pengampu, TujuanPembelajaran $tujuan_pembelajaran): RedirectResponse
    {
        $tujuanPembelajaran = $this->scopedTujuanPembelajaran($pengampu, $tujuan_pembelajaran);
        Gate::authorize('delete', $tujuanPembelajaran);

        $deleted = DB::transaction(function () use ($pengampu, $request, $tujuanPembelajaran): bool {
            $lockedTujuan = TujuanPembelajaran::query()->lockForUpdate()->findOrFail($tujuanPembelajaran->id);
            abort_unless($lockedTujuan->pengampu_id === $pengampu->id, 404);
            Gate::forUser($request->user())->authorize('delete', $lockedTujuan);

            if ($lockedTujuan->penilaian()->exists()) {
                return false;
            }

            $lockedTujuan->delete();

            return true;
        });

        if (! $deleted) {
            return back()->with('error', 'TP tidak dapat dihapus karena sudah digunakan oleh penilaian.');
        }

        return to_route('guru.tujuan-pembelajaran.index', $pengampu)
            ->with('success', 'Tujuan pembelajaran berhasil dihapus.');
    }

    /** @return array<string, mixed> */
    private function viewData(Request $request, Pengampu $pengampu): array
    {
        $user = $request->user();
        $guru = $user->guru()->firstOrFail();

        return [
            'user' => $user,
            'guru' => $guru,
            'workspace' => 'mapel',
            'assignment' => $pengampu,
            ...$this->dashboardService->capabilities($guru),
        ];
    }

    private function scopedTujuanPembelajaran(Pengampu $pengampu, TujuanPembelajaran $tujuanPembelajaran): TujuanPembelajaran
    {
        abort_unless($tujuanPembelajaran->pengampu_id === $pengampu->id, 404);

        return $tujuanPembelajaran;
    }
}
