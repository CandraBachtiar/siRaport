<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMataPelajaranRequest;
use App\Http\Requests\UpdateMataPelajaranRequest;
use App\Models\MataPelajaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MataPelajaranController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $mataPelajaran = MataPelajaran::query()
            ->select(['id', 'kode', 'nama', 'kkm'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('kode', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%");
                });
            })
            ->orderBy('nama')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.mata-pelajaran.index', [
            'admin' => $request->user(),
            'mataPelajaran' => $mataPelajaran,
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.mata-pelajaran.create', [
            'admin' => $request->user(),
        ]);
    }

    public function store(StoreMataPelajaranRequest $request): RedirectResponse
    {
        MataPelajaran::create($request->validated());

        return redirect()
            ->route('admin.mata-pelajaran.index')
            ->with('success', 'Data mata pelajaran berhasil ditambahkan.');
    }

    public function edit(Request $request, MataPelajaran $mataPelajaran): View
    {
        return view('admin.mata-pelajaran.edit', [
            'admin' => $request->user(),
            'mataPelajaran' => $mataPelajaran,
        ]);
    }

    public function update(UpdateMataPelajaranRequest $request, MataPelajaran $mataPelajaran): RedirectResponse
    {
        $mataPelajaran->update($request->validated());

        return redirect()
            ->route('admin.mata-pelajaran.index')
            ->with('success', 'Data mata pelajaran berhasil diperbarui.');
    }

    public function destroy(MataPelajaran $mataPelajaran): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($mataPelajaran): bool {
            $lockedMataPelajaran = MataPelajaran::query()
                ->lockForUpdate()
                ->findOrFail($mataPelajaran->getKey());

            if (DB::table('pengampu')->where('mata_pelajaran_id', $lockedMataPelajaran->getKey())->exists()) {
                return false;
            }

            $lockedMataPelajaran->delete();

            return true;
        });

        if (! $deleted) {
            return redirect()
                ->route('admin.mata-pelajaran.index')
                ->with('error', 'Mata pelajaran tidak dapat dihapus karena masih digunakan pada data pengampu.');
        }

        return redirect()
            ->route('admin.mata-pelajaran.index')
            ->with('success', 'Data mata pelajaran berhasil dihapus.');
    }
}
