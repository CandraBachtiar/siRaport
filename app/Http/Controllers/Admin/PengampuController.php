<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePengampuRequest;
use App\Http\Requests\UpdatePengampuRequest;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengampu;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PengampuController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $guruId = $request->integer('guru_id');
        $mataPelajaranId = $request->integer('mata_pelajaran_id');
        $kelasId = $request->integer('kelas_id');
        $tahunAjaranId = $request->integer('tahun_ajaran_id');

        $pengampu = Pengampu::query()
            ->select(['id', 'guru_id', 'mata_pelajaran_id', 'kelas_id', 'tahun_ajaran_id'])
            ->with(['guru:id,nama,user_id', 'guru.user:id,name', 'mataPelajaran:id,kode,nama', 'kelas:id,nama,tingkat', 'tahunAjaran:id,tahun,semester,aktif'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('guru', fn (Builder $query): Builder => $query->where('nama', 'like', "%{$search}%"))
                        ->orWhereHas('mataPelajaran', fn (Builder $query): Builder => $query->where('nama', 'like', "%{$search}%"))
                        ->orWhereHas('kelas', fn (Builder $query): Builder => $query->where('nama', 'like', "%{$search}%")->orWhere('tingkat', 'like', "%{$search}%"))
                        ->orWhereHas('tahunAjaran', fn (Builder $query): Builder => $query->where('tahun', 'like', "%{$search}%"));
                });
            })
            ->when($guruId > 0, fn (Builder $query): Builder => $query->where('guru_id', $guruId))
            ->when($mataPelajaranId > 0, fn (Builder $query): Builder => $query->where('mata_pelajaran_id', $mataPelajaranId))
            ->when($kelasId > 0, fn (Builder $query): Builder => $query->where('kelas_id', $kelasId))
            ->when($tahunAjaranId > 0, fn (Builder $query): Builder => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->orderBy('tahun_ajaran_id')
            ->orderBy('kelas_id')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.pengampu.index', [
            'admin' => $request->user(),
            'guruList' => $this->guruList(),
            'kelasList' => $this->kelasList(),
            'mataPelajaranList' => $this->mataPelajaranList(),
            'pengampu' => $pengampu,
            'search' => $search,
            'tahunAjaranId' => $tahunAjaranId,
            'guruId' => $guruId,
            'mataPelajaranId' => $mataPelajaranId,
            'kelasId' => $kelasId,
            'tahunAjaranList' => $this->tahunAjaranList(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.pengampu.create', [
            'admin' => $request->user(),
            'guruList' => $this->guruList(),
            'kelasList' => $this->kelasList(),
            'mataPelajaranList' => $this->mataPelajaranList(),
            'tahunAjaranList' => $this->tahunAjaranList(),
        ]);
    }

    public function store(StorePengampuRequest $request): RedirectResponse
    {
        Pengampu::create($request->validated());

        return redirect()->route('admin.pengampu.index')->with('success', 'Data pengampu berhasil ditambahkan.');
    }

    public function edit(Request $request, Pengampu $pengampu): View
    {
        return view('admin.pengampu.edit', [
            'admin' => $request->user(),
            'guruList' => $this->guruList(),
            'kelasList' => $this->kelasList(),
            'mataPelajaranList' => $this->mataPelajaranList(),
            'pengampu' => $pengampu,
            'tahunAjaranList' => $this->tahunAjaranList(),
        ]);
    }

    public function update(UpdatePengampuRequest $request, Pengampu $pengampu): RedirectResponse
    {
        $pengampu->update($request->validated());

        return redirect()->route('admin.pengampu.index')->with('success', 'Data pengampu berhasil diperbarui.');
    }

    public function destroy(Pengampu $pengampu): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($pengampu): bool {
            $lockedPengampu = Pengampu::query()->lockForUpdate()->findOrFail($pengampu->getKey());

            if (Schema::hasTable('penilaian') && DB::table('penilaian')->where('pengampu_id', $lockedPengampu->getKey())->exists()) {
                return false;
            }

            $lockedPengampu->delete();

            return true;
        });

        if (! $deleted) {
            return redirect()->route('admin.pengampu.index')->with('error', 'Data pengampu tidak dapat dihapus karena masih memiliki penilaian atau nilai.');
        }

        return redirect()->route('admin.pengampu.index')->with('success', 'Data pengampu berhasil dihapus.');
    }

    /** @return Collection<int, Guru> */
    private function guruList(): Collection
    {
        return Guru::query()->select(['id', 'user_id', 'nama'])->with('user:id,name')->orderBy('nama')->orderBy('id')->get();
    }

    /** @return Collection<int, MataPelajaran> */
    private function mataPelajaranList(): Collection
    {
        return MataPelajaran::query()->select(['id', 'kode', 'nama'])->orderBy('nama')->orderBy('id')->get();
    }

    /** @return Collection<int, Kelas> */
    private function kelasList(): Collection
    {
        return Kelas::query()->select(['id', 'nama', 'tingkat'])->orderBy('tingkat')->orderBy('nama')->orderBy('id')->get();
    }

    /** @return Collection<int, TahunAjaran> */
    private function tahunAjaranList(): Collection
    {
        return TahunAjaran::query()->select(['id', 'tahun', 'semester', 'aktif'])->orderByDesc('aktif')->orderByDesc('tahun')->orderBy('semester')->orderBy('id')->get();
    }
}
