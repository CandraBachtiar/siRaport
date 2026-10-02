<form method="POST" action="{{ $action }}" class="grid gap-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 sm:grid-cols-2">
        <div class="grid gap-2">
            <label for="nis" class="text-sm font-semibold text-slate-700">NIS</label>
            <input id="nis" name="nis" type="text" value="{{ old('nis', $siswa?->nis) }}" required autofocus autocomplete="off" placeholder="Masukkan NIS" class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('nis') ? 'border-red-400' : 'border-slate-300' }}">
            @error('nis')
                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <div class="flex items-center justify-between gap-3">
                <label for="nisn" class="text-sm font-semibold text-slate-700">NISN</label>
                <span class="text-xs text-slate-400">Opsional</span>
            </div>
            <input id="nisn" name="nisn" type="text" value="{{ old('nisn', $siswa?->nisn) }}" autocomplete="off" placeholder="Masukkan NISN" class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('nisn') ? 'border-red-400' : 'border-slate-300' }}">
            @error('nisn')
                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2 sm:col-span-2">
            <label for="nama" class="text-sm font-semibold text-slate-700">Nama Siswa</label>
            <input id="nama" name="nama" type="text" value="{{ old('nama', $siswa?->nama) }}" required autocomplete="name" placeholder="Masukkan nama lengkap siswa" class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('nama') ? 'border-red-400' : 'border-slate-300' }}">
            @error('nama')
                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="jenis_kelamin" class="text-sm font-semibold text-slate-700">Jenis Kelamin</label>
            <select id="jenis_kelamin" name="jenis_kelamin" required class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('jenis_kelamin') ? 'border-red-400' : 'border-slate-300' }}">
                <option value="">Pilih Jenis Kelamin</option>
                <option value="L" @selected(old('jenis_kelamin', $siswa?->jenis_kelamin) === 'L')>Laki-laki</option>
                <option value="P" @selected(old('jenis_kelamin', $siswa?->jenis_kelamin) === 'P')>Perempuan</option>
            </select>
            @error('jenis_kelamin')
                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="kelas_id" class="text-sm font-semibold text-slate-700">Kelas</label>
            <select id="kelas_id" name="kelas_id" required class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('kelas_id') ? 'border-red-400' : 'border-slate-300' }}">
                <option value="">Pilih Kelas</option>
                @foreach ($kelasList as $kelas)
                    <option value="{{ $kelas->id }}" @selected((int) old('kelas_id', $siswa?->kelas_id) === $kelas->id)>{{ $kelas->tingkat }} {{ $kelas->nama }}</option>
                @endforeach
            </select>
            @if ($kelasList->isEmpty())
                <p class="text-sm text-amber-700">Belum ada data kelas. Tambahkan kelas sebelum menyimpan siswa.</p>
            @endif
            @error('kelas_id')
                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="tempat_lahir" class="text-sm font-semibold text-slate-700">Tempat Lahir</label>
            <input id="tempat_lahir" name="tempat_lahir" type="text" value="{{ old('tempat_lahir', $siswa?->tempat_lahir) }}" placeholder="Masukkan tempat lahir" class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('tempat_lahir') ? 'border-red-400' : 'border-slate-300' }}">
            @error('tempat_lahir')
                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="tanggal_lahir" class="text-sm font-semibold text-slate-700">Tanggal Lahir</label>
            <input id="tanggal_lahir" name="tanggal_lahir" type="date" value="{{ old('tanggal_lahir', $siswa?->tanggal_lahir?->format('Y-m-d')) }}" class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('tanggal_lahir') ? 'border-red-400' : 'border-slate-300' }}">
            @error('tanggal_lahir')
                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2 sm:col-span-2">
            <label for="alamat" class="text-sm font-semibold text-slate-700">Alamat</label>
            <textarea id="alamat" name="alamat" rows="4" placeholder="Masukkan alamat siswa" class="rounded-xl border bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('alamat') ? 'border-red-400' : 'border-slate-300' }}">{{ old('alamat', $siswa?->alamat) }}</textarea>
            @error('alamat')
                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.siswa.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 px-5 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Batal</a>
        <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-emerald-600 px-6 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20">{{ $submitLabel }}</button>
    </div>
</form>
