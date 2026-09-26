<form method="POST" action="{{ $action }}" class="grid gap-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 sm:grid-cols-2">
        <div class="grid gap-2 sm:col-span-2">
            <label for="name" class="text-sm font-semibold text-slate-700">Nama Guru</label>
            <input id="name" name="name" type="text" value="{{ old('name', $guru?->user->name) }}" required autofocus autocomplete="name" placeholder="Masukkan nama lengkap guru" class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('name') ? 'border-red-400' : 'border-slate-300' }}">
            @error('name')
                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="nip" class="text-sm font-semibold text-slate-700">NIP</label>
            <input id="nip" name="nip" type="text" value="{{ old('nip', $guru?->nip) }}" required autocomplete="off" placeholder="Masukkan NIP" class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('nip') ? 'border-red-400' : 'border-slate-300' }}">
            @error('nip')
                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2">
            <label for="email" class="text-sm font-semibold text-slate-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $guru?->user->email) }}" required autocomplete="email" placeholder="guru@sekolah.test" class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('email') ? 'border-red-400' : 'border-slate-300' }}">
            @error('email')
                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-2 sm:col-span-2">
            <div class="flex items-center justify-between gap-3">
                <label for="password" class="text-sm font-semibold text-slate-700">Password</label>
                @if ($guru)
                    <span class="text-xs text-slate-400">Opsional — kosongkan untuk mempertahankan password lama</span>
                @endif
            </div>
            <input id="password" name="password" type="password" @required(! $guru) autocomplete="new-password" placeholder="Minimal 8 karakter" class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('password') ? 'border-red-400' : 'border-slate-300' }}">
            @error('password')
                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.guru.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 px-5 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Batal</a>
        <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-emerald-600 px-6 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20">{{ $submitLabel }}</button>
    </div>
</form>
