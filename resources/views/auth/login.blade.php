<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Masuk ke panel administrasi EduRaport.">
    <title>Login Admin — EduRaport</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-5 py-10">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(23,155,96,0.12),_transparent_35%),radial-gradient(circle_at_bottom_right,_rgba(16,45,75,0.10),_transparent_40%)]"></div>

        <div class="relative grid w-full max-w-5xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10 lg:grid-cols-[1.05fr_0.95fr]">
            <section class="hidden bg-[#0b2945] p-12 text-white lg:flex lg:flex-col lg:justify-between">
                <a href="{{ url('/') }}" class="inline-flex w-fit items-center gap-3" aria-label="EduRaport, kembali ke beranda">
                    <span class="grid size-12 place-items-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-950/20">
                        <svg class="size-7" viewBox="0 0 40 40" aria-hidden="true"><path d="M10 12.5c4.1-1.3 7.4-.8 10 1.4v16c-2.6-2.2-5.9-2.7-10-1.4v-16Zm20 0c-4.1-1.3-7.4-.8-10 1.4v16c2.6-2.2 5.9-2.7 10-1.4v-16Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                    </span>
                    <span><strong class="block text-lg font-extrabold">EduRaport</strong><small class="text-xs text-slate-300">Panel Administrator</small></span>
                </a>

                <div class="max-w-md py-16">
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-400/10 px-3 py-1.5 text-xs font-bold tracking-widest text-emerald-300">
                        <span class="size-1.5 rounded-full bg-emerald-300"></span> AKSES ADMIN
                    </span>
                    <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight">Kelola data sekolah dalam satu ruang kerja.</h1>
                    <p class="mt-5 leading-7 text-slate-300">Masuk untuk mengakses dashboard administrasi EduRaport secara aman.</p>
                </div>

                <p class="text-sm text-slate-400">&copy; {{ date('Y') }} EduRaport. Sistem Rapor Digital.</p>
            </section>

            <section class="px-6 py-10 sm:px-12 sm:py-14">
                <a href="{{ url('/') }}" class="mb-10 inline-flex items-center gap-3 lg:hidden">
                    <span class="grid size-11 place-items-center rounded-xl bg-emerald-600 text-white">
                        <svg class="size-6" viewBox="0 0 40 40" aria-hidden="true"><path d="M10 12.5c4.1-1.3 7.4-.8 10 1.4v16c-2.6-2.2-5.9-2.7-10-1.4v-16Zm20 0c-4.1-1.3-7.4-.8-10 1.4v16c2.6-2.2 5.9-2.7 10-1.4v-16Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                    </span>
                    <strong class="text-lg font-extrabold text-[#102d4b]">EduRaport</strong>
                </a>

                <div class="mx-auto max-w-md">
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-600">Selamat datang</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-[#102d4b]">Login Admin</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-500">Gunakan akun administrator yang telah dibuat melalui seeder.</p>

                    <form method="POST" action="{{ route('login.store') }}" class="mt-9 grid gap-6">
                        @csrf

                        <div class="grid gap-2">
                            <label for="email" class="text-sm font-semibold text-slate-700">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="admin@localhost.test" class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('email') ? 'border-red-400' : 'border-slate-300' }}">
                            @error('email')
                                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-2">
                            <label for="password" class="text-sm font-semibold text-slate-700">Password</label>
                            <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Masukkan password" class="h-12 rounded-xl border bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('password') ? 'border-red-400' : 'border-slate-300' }}">
                            @error('password')
                                <p class="text-sm font-medium text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="mt-1 inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20">
                            Masuk
                            <svg class="size-4" viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h11m-4-4 4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </form>

                    <a href="{{ url('/') }}" class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-emerald-700">
                        <span aria-hidden="true">&larr;</span> Kembali ke beranda
                    </a>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
