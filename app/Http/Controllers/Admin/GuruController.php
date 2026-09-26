<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGuruRequest;
use App\Http\Requests\UpdateGuruRequest;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class GuruController extends Controller
{
    public function index(Request $request): View
    {
        $gurus = Guru::query()
            ->select(['id', 'user_id', 'nip', 'nama'])
            ->with('user:id,name,email')
            ->orderBy('nama')
            ->orderBy('id')
            ->paginate(10);

        return view('admin.guru.index', [
            'admin' => $request->user(),
            'gurus' => $gurus,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.guru.create', [
            'admin' => $request->user(),
        ]);
    }

    public function store(StoreGuruRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'guru',
            ]);

            $user->guru()->create([
                'nip' => $data['nip'],
                'nama' => $data['name'],
            ]);
        });

        return redirect()
            ->route('admin.guru.index')
            ->with('success', 'Data guru berhasil ditambahkan.');
    }

    public function edit(Request $request, Guru $guru): View
    {
        return view('admin.guru.edit', [
            'admin' => $request->user(),
            'guru' => $guru->load('user'),
        ]);
    }

    public function update(UpdateGuruRequest $request, Guru $guru): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $guru): void {
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
            ];

            if (! empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            $guru->user->update($userData);
            $guru->update([
                'nip' => $data['nip'],
                'nama' => $data['name'],
            ]);
        });

        return redirect()
            ->route('admin.guru.index')
            ->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroy(Guru $guru): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($guru): bool {
            $lockedGuru = Guru::query()
                ->with('user')
                ->lockForUpdate()
                ->findOrFail($guru->getKey());

            $isInUse = DB::table('pengampu')->where('guru_id', $lockedGuru->getKey())->exists()
                || DB::table('kelas')->where('wali_kelas_id', $lockedGuru->getKey())->exists();

            if ($isInUse) {
                return false;
            }

            $user = $lockedGuru->user;

            $lockedGuru->delete();
            $user->delete();

            return true;
        });

        if (! $deleted) {
            return redirect()
                ->route('admin.guru.index')
                ->with('error', 'Guru tidak dapat dihapus karena masih digunakan pada data lain.');
        }

        return redirect()
            ->route('admin.guru.index')
            ->with('success', 'Data guru berhasil dihapus.');
    }
}
