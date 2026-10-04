<form method="POST" action="{{ $action }}" class="grid gap-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 sm:grid-cols-2">
        <div class="grid gap-2">
            <label for="tahun" class="text-sm font-semibold text-slate-700">Tahun Ajaran</label>
            <input id="tahun" name="tahun" type="text" value="{{ old('tahun', $tahunAjaran?->tahun) }}" required autofocus autocomplete="off" placeholder="Contoh: 2025/2026" class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('tahun') ? 'border-red-400' : 'border-slate-300' }}">
            @error('tahun')<p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-2">
            <label for="semester" class="text-sm font-semibold text-slate-700">Semester</label>
            <select id="semester" name="semester" required class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('semester') ? 'border-red-400' : 'border-slate-300' }}">
                <option value="">Pilih semester</option>
                @foreach (['Ganjil', 'Genap'] as $semester)
                    <option value="{{ $semester }}" @selected(old('semester', $tahunAjaran?->semester) === $semester)>{{ $semester }}</option>
                @endforeach
            </select>
            @error('semester')<p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>@enderror
        </div>
    </div>

    <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <input name="aktif" type="checkbox" value="1" @checked(old('aktif', $tahunAjaran?->aktif)) class="mt-0.5 size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
        <span><strong class="block text-sm font-bold text-slate-700">Jadikan tahun ajaran aktif</strong><small class="mt-1 block text-xs leading-5 text-slate-500">Mengaktifkan periode ini akan menonaktifkan periode aktif sebelumnya.</small></span>
    </label>
    @error('aktif')<p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>@enderror

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.tahun-ajaran.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 px-5 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Batal</a>
        <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-emerald-600 px-6 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20">{{ $submitLabel }}</button>
    </div>
</form>
