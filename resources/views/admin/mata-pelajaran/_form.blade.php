<form method="POST" action="{{ $action }}" class="grid gap-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 sm:grid-cols-2">
        <div class="grid gap-2">
            <label for="kode" class="text-sm font-semibold text-slate-700">Kode Mata Pelajaran</label>
            <input id="kode" name="kode" type="text" value="{{ old('kode', $mataPelajaran?->kode) }}" required autofocus autocomplete="off" placeholder="Contoh: MTK" class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('kode') ? 'border-red-400' : 'border-slate-300' }}">
            @error('kode')
                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="kkm" class="text-sm font-semibold text-slate-700">KKM</label>
            <input id="kkm" name="kkm" type="number" min="0" max="100" step="0.01" value="{{ old('kkm', $mataPelajaran?->kkm ?? '75.00') }}" required placeholder="75.00" class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('kkm') ? 'border-red-400' : 'border-slate-300' }}">
            @error('kkm')
                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2 sm:col-span-2">
            <label for="nama" class="text-sm font-semibold text-slate-700">Nama Mata Pelajaran</label>
            <input id="nama" name="nama" type="text" value="{{ old('nama', $mataPelajaran?->nama) }}" required autocomplete="off" placeholder="Masukkan nama mata pelajaran" class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('nama') ? 'border-red-400' : 'border-slate-300' }}">
            @error('nama')
                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.mata-pelajaran.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 px-5 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Batal</a>
        <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-emerald-600 px-6 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20">{{ $submitLabel }}</button>
    </div>
</form>
