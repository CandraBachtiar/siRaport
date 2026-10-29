<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSiswaRequest;
use App\Http\Requests\UpdateSiswaRequest;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SiswaController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $kelasId = $request->integer('kelas_id');
        $tingkat = $request->query('tingkat');

        if ($search === '' && $kelasId === 0 && is_string($tingkat) && $tingkat !== '') {
            $classes = Kelas::query()
                ->select(['id', 'nama', 'tingkat', 'wali_kelas_id'])
                ->with(['waliKelas:id,user_id,nama', 'waliKelas.user:id,name'])
                ->withCount('siswa')
                ->where('tingkat', $tingkat)
                ->orderBy('nama')
                ->orderBy('id')
                ->get();

            return view('admin.siswa.index', [
                'admin' => $request->user(),
                'page' => 'classes',
                'classes' => $classes,
                'selectedTingkat' => $tingkat,
                'search' => '',
            ]);
        }

        if ($search !== '' || $kelasId > 0) {
            $selectedClass = $kelasId > 0
                ? Kelas::query()
                    ->select(['id', 'nama', 'tingkat', 'wali_kelas_id'])
                    ->with(['waliKelas:id,user_id,nama', 'waliKelas.user:id,name'])
                    ->withCount('siswa')
                    ->findOrFail($kelasId)
                : null;

            $siswa = Siswa::query()
                ->select(['id', 'kelas_id', 'nis', 'nisn', 'nama', 'jenis_kelamin'])
                ->with('kelas:id,nama,tingkat')
                ->when($search !== '', function (Builder $query) use ($search): void {
                    $pattern = '%'.$search.'%';

                    $query->where(function (Builder $query) use ($pattern): void {
                        $query->whereLike('nis', $pattern)
                            ->orWhereLike('nisn', $pattern)
                            ->orWhereLike('nama', $pattern);
                    });
                })
                ->when($kelasId > 0, fn (Builder $query): Builder => $query->where('kelas_id', $kelasId))
                ->orderBy('nama')
                ->orderBy('id')
                ->paginate(10)
                ->withQueryString();

            return view('admin.siswa.index', [
                'admin' => $request->user(),
                'page' => 'students',
                'selectedClass' => $selectedClass,
                'search' => $search,
                'siswa' => $siswa,
            ]);
        }

        $levels = Kelas::query()
            ->select(['id', 'nama', 'tingkat'])
            ->withCount('siswa')
            ->orderBy('tingkat')
            ->orderBy('nama')
            ->orderBy('id')
            ->get()
            ->groupBy('tingkat')
            ->map(fn (SupportCollection $classes, string $classLevel): array => [
                'tingkat' => $classLevel,
                'class_count' => $classes->count(),
                'student_count' => $classes->sum('siswa_count'),
            ])
            ->values();

        return view('admin.siswa.index', [
            'admin' => $request->user(),
            'page' => 'levels',
            'levels' => $levels,
            'search' => '',
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.siswa.create', [
            'admin' => $request->user(),
            'kelasList' => $this->kelasList(),
        ]);
    }

    public function store(StoreSiswaRequest $request): RedirectResponse
    {
        Siswa::create($request->validated());

        return redirect()
            ->route('admin.siswa.index')
            ->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function edit(Request $request, Siswa $siswa): View
    {
        return view('admin.siswa.edit', [
            'admin' => $request->user(),
            'kelasList' => $this->kelasList(),
            'siswa' => $siswa,
        ]);
    }

    public function update(UpdateSiswaRequest $request, Siswa $siswa): RedirectResponse
    {
        $siswa->update($request->validated());

        return redirect()
            ->route('admin.siswa.index')
            ->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Siswa $siswa): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($siswa): bool {
            $lockedSiswa = Siswa::query()
                ->lockForUpdate()
                ->findOrFail($siswa->getKey());

            if (DB::table('nilai')->where('siswa_id', $lockedSiswa->getKey())->exists()) {
                return false;
            }

            $lockedSiswa->delete();

            return true;
        });

        if (! $deleted) {
            return redirect()
                ->route('admin.siswa.index')
                ->with('error', 'Siswa tidak dapat dihapus karena masih memiliki data nilai.');
        }

        return redirect()
            ->route('admin.siswa.index')
            ->with('success', 'Data siswa berhasil dihapus.');
    }

    /**
     * @return Collection<int, Kelas>
     */
    private function kelasList(): Collection
    {
        return Kelas::query()
            ->select(['id', 'nama', 'tingkat'])
            ->orderBy('tingkat')
            ->orderBy('nama')
            ->orderBy('id')
            ->get();
    }
}
