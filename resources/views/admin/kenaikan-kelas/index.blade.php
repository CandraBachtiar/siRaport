@extends('layouts.admin-v2')

@section('title', 'Kenaikan Kelas')

@section('content')
    @php
        $totalSiswa = $rows->count();
        $totalNaik = $rows->where('decision', 'naik')->count();
        $totalTidakNaik = $rows->where('decision', 'tidak_naik')->count();
        $totalBelum = $rows->whereNull('decision')->count();

        $tingkatKelas = [
            '1' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4,
            '5' => 5,
            '6' => 6,
            'I' => 1,
            'II' => 2,
            'III' => 3,
            'IV' => 4,
            'V' => 5,
            'VI' => 6,
        ];

        $getTingkat = function ($value) use ($tingkatKelas) {
            $value = strtoupper(trim((string) $value));
            $value = preg_replace('/^KELAS\s+/', '', $value);

            return $tingkatKelas[$value] ?? null;
        };
    @endphp

    <div class="mx-auto max-w-7xl space-y-6">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 sm:text-3xl">
                Kenaikan Kelas
            </h1>

            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                Kelola penempatan siswa pada tahun ajaran berikutnya
                berdasarkan keputusan kenaikan kelas yang dicatat oleh
                wali kelas, tanpa mengubah riwayat akademik sebelumnya.
            </p>
        </div>

        <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Siswa Ditampilkan
                </p>

                <p class="mt-2 text-3xl font-extrabold text-slate-900">
                    {{ $totalSiswa }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Naik Kelas
                </p>

                <p class="mt-2 text-3xl font-extrabold text-emerald-600">
                    {{ $totalNaik }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Tidak Naik Kelas
                </p>

                <p class="mt-2 text-3xl font-extrabold text-red-600">
                    {{ $totalTidakNaik }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Belum Ditetapkan
                </p>

                <p class="mt-2 text-3xl font-extrabold text-amber-600">
                    {{ $totalBelum }}
                </p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">
                Filter Data Siswa
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Pilih semester genap, kelas, dan tanggal akhir
                penempatan siswa.
            </p>

            <form method="GET"
                action="{{ route('admin.keputusan-kenaikan.index') }}"
                class="mt-5">

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label for="tahun_ajaran_id"
                            class="mb-2 block text-sm font-semibold text-slate-700">
                            Tahun Ajaran
                        </label>

                        <select id="tahun_ajaran_id"
                            name="tahun_ajaran_id"
                            required
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">

                            <option value="">Pilih tahun ajaran</option>

                            @foreach ($years as $item)
                                <option value="{{ $item->id }}"
                                    @selected((int) request('tahun_ajaran_id') === (int) $item->id)>
                                    {{ $item->tahun }} - {{ $item->semester }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="kelas_id"
                            class="mb-2 block text-sm font-semibold text-slate-700">
                            Kelas
                        </label>

                        <select id="kelas_id"
                            name="kelas_id"
                            required
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">

                            <option value="">Pilih kelas</option>

                            @foreach ($classes as $item)
                                <option value="{{ $item->id }}"
                                    @selected((int) request('kelas_id') === (int) $item->id)>
                                    {{ $item->tingkat }} {{ $item->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="tanggal_akhir"
                            class="mb-2 block text-sm font-semibold text-slate-700">
                            Tanggal Akhir Penempatan
                        </label>

                        <input type="date"
                            id="tanggal_akhir"
                            name="tanggal_akhir"
                            value="{{ $date }}"
                            max="{{ now()->toDateString() }}"
                            required
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    </div>

                    <div>
                        <label for="status"
                            class="mb-2 block text-sm font-semibold text-slate-700">
                            Status Keputusan
                        </label>

                        <select id="status"
                            name="status"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">

                            <option value="semua"
                                @selected($status === 'semua')>
                                Semua Status
                            </option>

                            <option value="naik"
                                @selected($status === 'naik')>
                                Naik Kelas
                            </option>

                            <option value="tidak_naik"
                                @selected($status === 'tidak_naik')>
                                Tidak Naik Kelas
                            </option>

                            <option value="belum"
                                @selected($status === 'belum')>
                                Belum Ditetapkan
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap gap-3">
                    <button type="submit"
                        class="rounded-xl bg-raporku-navy-deep px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">
                        Tampilkan Data
                    </button>

                    <a href="{{ route('admin.keputusan-kenaikan.index') }}"
                        class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        Reset Filter
                    </a>
                </div>
            </form>
        </div>

        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
            <h2 class="text-sm font-bold text-blue-900">
                Petunjuk Pengelolaan Kenaikan Kelas
            </h2>

            <p class="mt-2 text-sm leading-relaxed text-blue-800">
                Keputusan kenaikan kelas dicatat oleh wali kelas
                berdasarkan hasil rapat atau keputusan sekolah.
                Administrator hanya dapat melihat keputusan tersebut
                dan mengelola penempatan siswa pada tahun ajaran berikutnya.
                Konfirmasi penempatan dilakukan setelah semester ganjil
                tahun ajaran berikutnya diaktifkan. Siswa yang tidak naik
                kelas tetap menggunakan identitas yang sama dan ditempatkan
                pada tingkat kelas yang sama.
            </p>
        </div>

        @if ($warning)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4"
                role="alert">

                <p class="text-sm font-medium text-amber-800">
                    {{ $warning }}
                </p>
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-6">
                <h2 class="text-lg font-bold text-slate-900">
                    Daftar Kenaikan Kelas
                </h2>

                @if ($year && $class && $date)
                    <p class="mt-1 text-sm text-slate-500">
                        Kelas {{ $class->tingkat }} {{ $class->nama }}
                        &middot; {{ $year->tahun }}
                        {{ $year->semester }}
                    </p>
                @else
                    <p class="mt-1 text-sm text-slate-500">
                        Pilih filter untuk menampilkan daftar siswa.
                    </p>
                @endif
            </div>

            @if (! $year || ! $class || ! $date)
                <div class="px-6 py-14 text-center">
                    <h3 class="font-bold text-slate-700">
                        Belum Ada Data Ditampilkan
                    </h3>

                    <p class="mt-2 text-sm text-slate-500">
                        Pilih tahun ajaran, kelas, dan tanggal akhir
                        penempatan terlebih dahulu.
                    </p>
                </div>
            @elseif ($rows->isEmpty())
                <div class="px-6 py-14 text-center">
                    <h3 class="font-bold text-slate-700">
                        Data Siswa Tidak Ditemukan
                    </h3>

                    <p class="mt-2 text-sm text-slate-500">
                        Periksa riwayat penempatan siswa atau
                        sesuaikan filter yang dipilih.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[850px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-600">
                            <tr>
                                <th scope="col" class="px-5 py-4">
                                    No.
                                </th>

                                <th scope="col" class="px-5 py-4">
                                    Nama Siswa
                                </th>

                                <th scope="col" class="px-5 py-4">
                                    Keputusan Kenaikan Kelas
                                </th>

                                <th scope="col" class="px-5 py-4">
                                    Penempatan
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @foreach ($rows as $row)
                                @php
                                    $student = $row['student'];
                                    $decision = $row['decision'];
                                    $targets = $row['targets'];
                                    $hasPlacement = $targets->isNotEmpty();
                                    $sourceLevel = $getTingkat($class->tingkat);

                                    $expectedLevel = $sourceLevel === null
                                        ? null
                                        : match ($decision) {
                                            'naik' => $sourceLevel + 1,
                                            'tidak_naik' => $sourceLevel,
                                            default => null,
                                        };

                                    $targetClasses = $classes->filter(
                                        fn ($item) =>
                                            $expectedLevel !== null
                                            && $getTingkat($item->tingkat) === $expectedLevel
                                    );
                                @endphp

                                <tr class="align-top hover:bg-slate-50/50">
                                    <td class="px-5 py-5 text-slate-500">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td class="px-5 py-5">
                                        <p class="font-bold text-slate-800">
                                            {{ $student->nama }}
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            NIS: {{ $student->nis ?? '-' }}
                                        </p>

                                        <a href="{{ route('admin.siswa.riwayat.index', $student->id) }}"
                                            class="mt-2 inline-block text-xs font-semibold text-blue-600 hover:underline">
                                            Lihat Riwayat Kelas
                                        </a>
                                    </td>

                                    <td class="px-5 py-5">
                                        @if ($decision === 'naik')
                                            <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                                                Naik Kelas
                                            </span>
                                        @elseif ($decision === 'tidak_naik')
                                            <span class="inline-flex rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-700">
                                                Tidak Naik Kelas
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">
                                                Belum Ditetapkan
                                            </span>
                                        @endif

                                        @if ($hasPlacement)
                                            <p class="mt-2 text-xs text-slate-500">
                                                Penempatan berikutnya sudah tercatat.
                                            </p>
                                        @elseif ($row['conflict'])
                                            <p class="mt-2 text-xs text-red-600">
                                                Riwayat penempatan belum valid.
                                            </p>
                                        @elseif (! in_array($decision, ['naik', 'tidak_naik'], true))
                                            <p class="mt-2 text-xs text-slate-500">
                                                Menunggu keputusan wali kelas.
                                            </p>
                                        @else
                                            <p class="mt-2 text-xs text-slate-500">
                                                Keputusan sudah tercatat.
                                            </p>
                                        @endif
                                    </td>

                                    <td class="px-5 py-5">
                                        @if ($hasPlacement)
                                            <div class="space-y-2">
                                                @foreach ($targets as $target)
                                                    <div class="rounded-lg bg-emerald-50 p-3">
                                                        <p class="text-xs font-bold text-emerald-700">
                                                            Penempatan Tercatat
                                                        </p>

                                                        <p class="mt-1 font-semibold text-slate-800">
                                                            Kelas {{ $target->kelas?->tingkat }}
                                                            {{ $target->kelas?->nama }}
                                                        </p>

                                                        <p class="mt-1 text-xs text-slate-600">
                                                            {{ $target->tahunAjaran?->tahun }}
                                                            {{ $target->tahunAjaran?->semester }}
                                                        </p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif ($row['conflict'])
                                            <p class="text-sm text-red-600">
                                                Perbaiki riwayat penempatan terlebih dahulu.
                                            </p>
                                        @elseif (! in_array($decision, ['naik', 'tidak_naik'], true))
                                            <p class="text-sm text-slate-500">
                                                Penempatan belum dapat dilakukan
                                                karena keputusan wali kelas
                                                belum tersedia.
                                            </p>
                                        @elseif ($expectedLevel === 7)
                                            <p class="text-sm text-blue-700">
                                                Kelulusan kelas VI dikelola secara terpisah.
                                            </p>
                                        @elseif ($expectedLevel === null)
                                            <p class="text-sm text-red-600">
                                                Tingkat kelas asal tidak dikenali.
                                            </p>
                                        @elseif (! $nextYear)
                                            <p class="text-sm text-amber-700">
                                                Tahun ajaran berikutnya belum tersedia.
                                            </p>
                                        @elseif (! $nextYear->aktif)
                                            <p class="text-sm text-amber-700">
                                                Aktifkan semester ganjil
                                                {{ $nextYear->tahun }}
                                                terlebih dahulu.
                                            </p>
                                        @elseif ($targetClasses->isEmpty())
                                            <p class="text-sm text-amber-700">
                                                Kelas tujuan belum tersedia
                                                pada master data kelas.
                                            </p>
                                        @else
                                            <form method="POST"
                                                action="{{ route('admin.keputusan-kenaikan.place') }}"
                                                class="space-y-3">

                                                @csrf

                                                <input type="hidden"
                                                    name="siswa_id"
                                                    value="{{ $student->id }}">

                                                <input type="hidden"
                                                    name="tahun_ajaran_id"
                                                    value="{{ $year->id }}">

                                                <input type="hidden"
                                                    name="kelas_id"
                                                    value="{{ $class->id }}">

                                                <input type="hidden"
                                                    name="tanggal_akhir"
                                                    value="{{ $date }}">

                                                <p class="text-xs font-semibold text-slate-500">
                                                    {{ $nextYear->tahun }} - Ganjil
                                                </p>

                                                <div>
                                                    <label for="kelas_tujuan_{{ $student->id }}"
                                                        class="mb-1 block text-xs font-semibold text-slate-700">
                                                        Kelas Tujuan
                                                    </label>

                                                    <select id="kelas_tujuan_{{ $student->id }}"
                                                        name="kelas_tujuan_id"
                                                        required
                                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">

                                                        <option value="">
                                                            Pilih kelas tujuan
                                                        </option>

                                                        @foreach ($targetClasses as $targetClass)
                                                            <option value="{{ $targetClass->id }}">
                                                                {{ $targetClass->tingkat }}
                                                                {{ $targetClass->nama }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label for="mulai_pada_{{ $student->id }}"
                                                        class="mb-1 block text-xs font-semibold text-slate-700">
                                                        Tanggal Mulai Penempatan
                                                    </label>

                                                    <input type="date"
                                                        id="mulai_pada_{{ $student->id }}"
                                                        name="mulai_pada"
                                                        min="{{ \Illuminate\Support\Carbon::parse($date)->addDay()->toDateString() }}"
                                                        max="{{ now()->toDateString() }}"
                                                        required
                                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                                </div>

                                                <div>
                                                    <label for="bukti_{{ $student->id }}"
                                                        class="mb-1 block text-xs font-semibold text-slate-700">
                                                        Referensi Bukti
                                                    </label>

                                                    <textarea id="bukti_{{ $student->id }}"
                                                        name="referensi_bukti"
                                                        rows="2"
                                                        minlength="3"
                                                        maxlength="1000"
                                                        required
                                                        placeholder="Contoh: Berita acara rapat kenaikan kelas"
                                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                                                </div>

                                                <label class="flex items-start gap-2 text-xs leading-relaxed text-slate-600">
                                                    <input type="checkbox"
                                                        name="konfirmasi"
                                                        value="1"
                                                        required
                                                        class="mt-0.5 rounded border-slate-300 text-emerald-600">

                                                    <span>
                                                        Saya telah memeriksa keputusan
                                                        kenaikan kelas dan memastikan
                                                        kelas tujuan siswa sudah sesuai.
                                                    </span>
                                                </label>

                                                <button type="submit"
                                                    class="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-800">
                                                    Konfirmasi Penempatan
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection