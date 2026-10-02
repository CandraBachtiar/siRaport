<form method="POST" action="{{ $action }}" class="grid gap-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 sm:grid-cols-2">
        <div class="grid gap-2">
            <label for="tingkat" class="text-sm font-semibold text-slate-700">Tingkat</label>
            <input id="tingkat" name="tingkat" type="text" value="{{ old('tingkat', $kelas?->tingkat) }}" required autofocus autocomplete="off" placeholder="Contoh: VII" class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('tingkat') ? 'border-red-400' : 'border-slate-300' }}">
            @error('tingkat')
                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="nama" class="text-sm font-semibold text-slate-700">Nama/Rombel</label>
            <input id="nama" name="nama" type="text" value="{{ old('nama', $kelas?->nama) }}" required autocomplete="off" placeholder="Contoh: A" class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('nama') ? 'border-red-400' : 'border-slate-300' }}">
            @error('nama')
                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2 sm:col-span-2">
            <div class="flex items-center justify-between gap-3">
                <label for="wali_kelas_id" class="text-sm font-semibold text-slate-700">Wali Kelas</label>
                <span class="text-xs text-slate-400">Opsional</span>
            </div>
            <select id="wali_kelas_id" name="wali_kelas_id" class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('wali_kelas_id') ? 'border-red-400' : 'border-slate-300' }}">
                <option value="">Belum Ditentukan</option>
                @foreach ($guruList as $guru)
                    <option value="{{ $guru->id }}" @selected((int) old('wali_kelas_id', $kelas?->wali_kelas_id) === $guru->id)>{{ $guru->user->name }}{{ $guru->nip ? ' — '.$guru->nip : '' }}</option>
                @endforeach
            </select>
            @if ($guruList->isEmpty())
                <p class="text-sm text-amber-700">Belum ada data guru yang dapat dipilih sebagai wali kelas.</p>
            @endif
            @error('wali_kelas_id')
                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.kelas.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 px-5 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Batal</a>
        <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-emerald-600 px-6 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20">{{ $submitLabel }}</button>
    </div>
</form>
