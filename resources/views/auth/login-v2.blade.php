<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Masuk ke RaporKu menggunakan akun administrator atau guru dari sekolah Anda.">
    <meta name="theme-color" content="#0f2f4f">
    <title>Masuk — {{ config('app.name', 'RaporKu') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/raporku.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[linear-gradient(135deg,#f8fafc_0%,#f1f5f9_58%,#e9eef3_100%)] text-raporku-ink antialiased">
    <x-skip-link />
    <main id="main-content" tabindex="-1" class="grid min-h-screen place-items-center px-3 py-4 sm:px-6 sm:py-8 lg:px-8">
        <div class="grid w-full max-w-[60rem] overflow-hidden rounded-[20px] border border-slate-200/90 bg-white shadow-[0_24px_70px_rgba(15,47,79,0.14)] lg:grid-cols-2">
        <section class="relative flex flex-col justify-between overflow-hidden bg-raporku-navy-deep px-6 py-8 text-white sm:px-10 sm:py-10 lg:px-12">

            <x-brand :href="route('home')" subtitle="Portal Sekolah" :on-dark="true" class="relative z-10 w-fit" aria-label="RaporKu, kembali ke beranda" />

            <div class="relative z-10 max-w-xl py-8 sm:py-12">
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-400/10 px-3 py-1.5 text-xs font-extrabold uppercase tracking-[0.14em] text-emerald-200">
                    <span class="size-2 rounded-full bg-emerald-300"></span> Sistem Rapor Digital
                </span>
                <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-[-0.035em] text-white xl:text-5xl">Kelola nilai. Pantau perkembangan. Susun rapor lebih mudah.</h1>
                <p class="mt-5 max-w-lg text-base leading-7 text-slate-200">Satu ruang kerja untuk administrator, guru mata pelajaran, dan wali kelas.</p>

                <div class="mt-10 max-w-lg rounded-2xl border border-white/10 bg-white/[0.06] p-5">
                    <div class="flex items-center gap-4">
                        <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-emerald-400/15 text-emerald-200">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 3h10l4 4v14H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8"/><path d="M14 3v5h5M7 13h8M7 17h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        </span>
                        <div><h2 class="text-sm font-bold text-white">Data akademik dalam satu alur</h2><p class="mt-1 text-xs leading-5 text-slate-300">Akses menu dan data sesuai tanggung jawab akun sekolah Anda.</p></div>
                    </div>
                </div>
            </div>

            <p class="relative z-10 text-xs text-slate-400">&copy; {{ date('Y') }} RaporKu. Sistem rapor digital sekolah.</p>
        </section>

        <section class="flex items-center justify-center bg-white px-6 py-8 sm:px-10 sm:py-12 lg:px-12">
            <div class="w-full max-w-md">
                <x-brand :href="route('home')" subtitle="Sistem Rapor Digital" class="mb-10 w-fit lg:hidden" aria-label="RaporKu, kembali ke beranda" />

                <p class="rk-eyebrow">Selamat datang</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-raporku-navy sm:text-4xl">Masuk ke RaporKu</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">Gunakan akun sekolah Anda untuk melanjutkan.</p>

                <form method="POST" action="{{ route('login.store') }}" class="mt-9 grid gap-6" data-loading-form>
                    @csrf

                    <div class="grid gap-2">
                        <label for="email" class="text-sm font-bold text-slate-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="nama@sekolah.sch.id" class="rk-field" @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
                        @error('email')
                            <p id="email-error" class="flex items-start gap-2 text-sm font-semibold text-red-600" role="alert"><span aria-hidden="true">!</span>{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <label for="password" class="text-sm font-bold text-slate-700">Password</label>
                            <button type="button" class="rounded-md px-2 py-1 text-xs font-bold text-emerald-700 hover:bg-emerald-50" aria-label="Tampilkan password" aria-controls="password" aria-pressed="false" data-password-toggle><span data-password-label>Tampilkan</span></button>
                        </div>
                        <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Masukkan password" class="rk-field" @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
                        @error('password')
                            <p id="password-error" class="flex items-start gap-2 text-sm font-semibold text-red-600" role="alert"><span aria-hidden="true">!</span>{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="rk-button rk-button-primary min-h-12 w-full disabled:cursor-not-allowed disabled:opacity-70">
                        <span data-submit-label>Masuk</span>
                        <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h11m-4-4 4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </form>

                <a href="{{ route('home') }}" class="mt-8 inline-flex min-h-11 items-center gap-2 rounded-lg text-sm font-bold text-slate-600 transition hover:text-emerald-700">
                    <span aria-hidden="true">←</span> Kembali ke Beranda
                </a>

                <div class="mt-8 rounded-xl border border-blue-200 bg-blue-50 p-4 text-xs leading-5 text-blue-900">
                    <strong>Kesulitan masuk?</strong> Hubungi administrator sekolah untuk memeriksa email dan status akun Anda.
                </div>
            </div>
        </section>
        </div>
    </main>
</body>
</html>
