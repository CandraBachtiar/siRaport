<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTahunAjaranRequest;
use App\Http\Requests\UpdateTahunAjaranRequest;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TahunAjaranController extends Controller
{
    public function index(Request $request): View
    {
        $tahunAjaran = TahunAjaran::query()
            ->orderByDesc('aktif')
            ->orderByDesc('tahun')
            ->orderBy('semester')
            ->orderBy('id')
            ->paginate(10);

        return view('admin.tahun-ajaran.index', [
            'admin' => $request->user(),
            'tahunAjaran' => $tahunAjaran,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.tahun-ajaran.create', ['admin' => $request->user()]);
    }

    public function store(StoreTahunAjaranRequest $request): RedirectResponse
    {
        $data = [...$request->validated(), 'aktif' => $request->boolean('aktif')];

        DB::transaction(function () use ($data): void {
            if ($data['aktif'] ?? false) {
                TahunAjaran::query()->where('aktif', true)->update(['aktif' => false]);
            }

            TahunAjaran::create($data);
        });

        return redirect()->route('admin.tahun-ajaran.index')->with('success', 'Data tahun ajaran berhasil ditambahkan.');
    }

    public function edit(Request $request, TahunAjaran $tahunAjaran): View
    {
        return view('admin.tahun-ajaran.edit', [
            'admin' => $request->user(),
            'tahunAjaran' => $tahunAjaran,
        ]);
    }

    public function update(UpdateTahunAjaranRequest $request, TahunAjaran $tahunAjaran): RedirectResponse
    {
        $data = [...$request->validated(), 'aktif' => $request->boolean('aktif')];

        DB::transaction(function () use ($data, $tahunAjaran): void {
            if ($data['aktif'] ?? false) {
                TahunAjaran::query()
                    ->where('aktif', true)
                    ->whereKeyNot($tahunAjaran->getKey())
                    ->update(['aktif' => false]);
            }

            $tahunAjaran->update($data);
        });

        return redirect()->route('admin.tahun-ajaran.index')->with('success', 'Data tahun ajaran berhasil diperbarui.');
    }

    public function destroy(TahunAjaran $tahunAjaran): RedirectResponse
    {
        $result = DB::transaction(function () use ($tahunAjaran): string {
            $lockedTahunAjaran = TahunAjaran::query()->lockForUpdate()->findOrFail($tahunAjaran->getKey());

            if ($lockedTahunAjaran->aktif) {
                return 'aktif';
            }

            $isInUse = DB::table('pengampu')->where('tahun_ajaran_id', $lockedTahunAjaran->getKey())->exists()
                || DB::table('nilai')->where('tahun_ajaran_id', $lockedTahunAjaran->getKey())->exists();

            if ($isInUse) {
                return 'digunakan';
            }

            $lockedTahunAjaran->delete();

            return 'deleted';
        });

        if ($result === 'aktif') {
            return redirect()->route('admin.tahun-ajaran.index')->with('error', 'Tahun ajaran aktif tidak dapat dihapus.');
        }

        if ($result === 'digunakan') {
            return redirect()->route('admin.tahun-ajaran.index')->with('error', 'Tahun ajaran tidak dapat dihapus karena masih digunakan pada data lain.');
        }

        return redirect()->route('admin.tahun-ajaran.index')->with('success', 'Data tahun ajaran berhasil dihapus.');
    }
}
