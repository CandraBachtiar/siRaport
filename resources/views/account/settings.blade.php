@extends($user->role === 'admin' ? 'layouts.admin-v2' : 'layouts.guru-workspace')

@section('title', 'Pengaturan Akun')

@section('content')
    <section class="mx-auto max-w-4xl">
        <div>
            <p class="text-sm font-semibold text-emerald-600">Akun Saya</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Pengaturan Akun</h1>
            <p class="mt-2 text-base leading-7 text-slate-600">Lihat identitas akun dan perbarui password Anda.</p>
        </div>

        <div class="mt-7 grid gap-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-[#102d4b]">Identitas Akun</h2>
                <dl class="mt-5 grid gap-4">
                    <div>
                        <dt class="text-sm font-semibold text-slate-500">Nama</dt>
                        <dd class="mt-1 break-words text-base font-semibold text-slate-800">{{ $user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-semibold text-slate-500">Email</dt>
                        <dd class="mt-1 break-all text-base font-semibold text-slate-800">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-semibold text-slate-500">Peran</dt>
                        <dd class="mt-1 text-base font-semibold text-slate-800">{{ $user->role === 'admin' ? 'Administrator' : 'Guru' }}</dd>
                    </div>
                </dl>
                <p class="mt-5 border-t border-slate-100 pt-4 text-sm leading-6 text-slate-500">Untuk mengubah nama atau email, hubungi administrator sekolah.</p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-[#102d4b]">Ubah Password</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">Masukkan password saat ini, lalu buat password baru dan konfirmasikan.</p>

                <form method="POST" action="{{ route('akun.password.update') }}" class="mt-5 grid gap-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-2">
                        <label for="current_password" class="text-base font-semibold text-slate-700">Password saat ini</label>
                        <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="rk-field" @if ($errors->has('current_password')) aria-invalid="true" aria-describedby="current_password-error" @endif>
                        @error('current_password')<p id="current_password-error" class="text-sm font-medium text-red-700" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-2">
                        <label for="password" class="text-base font-semibold text-slate-700">Password baru</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password" class="rk-field" @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
                        <p class="text-sm leading-6 text-slate-500">Ikuti persyaratan password yang ditampilkan jika validasi meminta perbaikan.</p>
                        @error('password')<p id="password-error" class="text-sm font-medium text-red-700" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-2">
                        <label for="password_confirmation" class="text-base font-semibold text-slate-700">Konfirmasi password baru</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="rk-field">
                    </div>

                    <button type="submit" class="rk-button rk-button-primary w-full sm:w-fit">Simpan Password Baru</button>
                </form>
            </article>
        </div>
    </section>
@endsection
