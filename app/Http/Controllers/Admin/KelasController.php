<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKelasRequest;
use App\Http\Requests\UpdateKelasRequest;
use App\Models\Guru;
use App\Models\Kelas;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KelasController extends Controller
{
    public function index(Request $request): View
    {
        $kelas = Kelas::query()
            ->select(['id', 'nama', 'tingkat', 'wali_kelas_id'])
            ->with([
                'waliKelas:id,user_id,nama',
                'waliKelas.user:id,name',
            ])
            ->withCount('siswa')
            ->orderBy('tingkat')
            ->orderBy('nama')
            ->orderBy('id')
            ->paginate(10);

        return view('admin.kelas.index', [
            'admin' => $request->user(),
            'kelas' => $kelas,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.kelas.create', [
            'admin' => $request->user(),
            'guruList' => $this->guruList(),
        ]);
    }

    public function store(StoreKelasRequest $request): RedirectResponse
    {
        Kelas::create($request->validated());

        return redirect()
            ->route('admin.kelas.index')
            ->with('success', 'Data kelas berhasil ditambahkan.');
    }

    public function edit(Request $request, Kelas $kelas): View
    {
        return view('admin.kelas.edit', [
            'admin' => $request->user(),
            'guruList' => $this->guruList(),
            'kelas' => $kelas,
        ]);
    }

    public function update(UpdateKelasRequest $request, Kelas $kelas): RedirectResponse
    {
        $kelas->update($request->validated());

        return redirect()
            ->route('admin.kelas.index')
            ->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(Kelas $kelas): RedirectResponse
    {
        $result = DB::transaction(function () use ($kelas): string {
            $lockedKelas = Kelas::query()
                ->lockForUpdate()
                ->findOrFail($kelas->getKey());

            if ($lockedKelas->siswa()->exists()) {
                return 'siswa';
            }

            if (DB::table('pengampu')->where('kelas_id', $lockedKelas->getKey())->exists()) {
                return 'pengampu';
            }

            $lockedKelas->delete();

            return 'deleted';
        });

        if ($result === 'siswa') {
            return redirect()
                ->route('admin.kelas.index')
                ->with('error', 'Kelas tidak dapat dihapus karena masih memiliki siswa.');
        }

        if ($result === 'pengampu') {
            return redirect()
                ->route('admin.kelas.index')
                ->with('error', 'Kelas tidak dapat dihapus karena masih digunakan pada data pengampu.');
        }

        return redirect()
            ->route('admin.kelas.index')
            ->with('success', 'Data kelas berhasil dihapus.');
    }

    /**
     * @return Collection<int, Guru>
     */
    private function guruList(): Collection
    {
        return Guru::query()
            ->select(['id', 'user_id', 'nip', 'nama'])
            ->with('user:id,name')
            ->orderBy('nama')
            ->orderBy('id')
            ->get();
    }
}
