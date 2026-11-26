@extends('layouts.guru-workspace')

@section('title', 'Data Rapor '.$student->nama)

@section('content')
    <section class="mx-auto max-w-4xl">
        <h1 class="text-3xl font-extrabold text-[#102d4b]">
            Data Rapor: {{ $student->nama }}
        </h1>

        <p class="mt-2 text-sm text-slate-500">
            NIS {{ $student->nis }} ·
            NISN {{ $student->nisn ?: 'Belum tersedia' }}
        </p>

        <form method="GET" class="mt-5">
            <label for="tahun_ajaran_id" class="text-sm font-bold">
                Periode
            </label>

            <select id="tahun_ajaran_id"
                name="tahun_ajaran_id"
                onchange="this.form.submit()"
                class="rk-field mt-2">

                @foreach ($years as $yearOption)
                    <option value="{{ $yearOption->id }}"
                        @selected($yearOption->id === $year->id)>
                        {{ $yearOption->tahun }} · {{ $yearOption->semester }}
                    </option>
                @endforeach
            </select>
        </form>

        <form method="POST"
            action="{{ route('guru.wali.rapor.data.update', ['siswa' => $student, 'tahun_ajaran_id' => $year->id]) }}"
            class="mt-5 grid gap-6 rounded-2xl border border-slate-200 bg-white p-6">

            @csrf
            @method('PUT')

            <input type="hidden"
                name="tahun_ajaran_id"
                value="{{ $year->id }}">

            <div class="grid gap-4">
                <div>
                    <h2 class="text-lg font-bold text-[#102d4b]">
                        Kokurikuler
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Deskripsikan perkembangan dan capaian siswa
                        dalam kegiatan kokurikuler selama semester ini.
                    </p>
                </div>

                <div class="grid gap-2">
                    <label for="kokurikuler" class="text-sm font-bold">
                        Deskripsi Kokurikuler
                    </label>

                    <textarea id="kokurikuler"
                        name="kokurikuler"
                        rows="5"
                        maxlength="3000"
                        placeholder="Masukkan deskripsi kegiatan dan perkembangan siswa..."
                        aria-describedby="kokurikuler_help{{ $errors->has('kokurikuler') ? ' kokurikuler_error' : '' }}"
                        @error('kokurikuler')
                            aria-invalid="true"
                        @enderror
                        class="rk-field">{{ old('kokurikuler', $record?->kokurikuler) }}</textarea>

                    <p id="kokurikuler_help"
                        class="text-xs text-slate-500">
                        Opsional, maksimal 3.000 karakter.
                    </p>

                    @error('kokurikuler')
                        <p id="kokurikuler_error"
                            class="text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-4 border-t border-slate-200 pt-6">
                <div>
                    <h2 class="text-lg font-bold text-[#102d4b]">
                        Ekstrakurikuler
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Catat kegiatan ekstrakurikuler yang diikuti
                        siswa beserta keterangan atau capaiannya.
                    </p>
                </div>

                @php
                    $activityRows = old(
                        'ekstrakurikuler',
                        $activities->map(fn ($activity) => [
                            'nama_kegiatan' => $activity->nama_kegiatan,
                            'keterangan' => $activity->keterangan,
                        ])->values()->all()
                    );

                    $activityRows = is_array($activityRows)
                        ? array_values(array_filter($activityRows, 'is_array'))
                        : [];

                    if (count($activityRows) < 20) {
                        $activityRows[] = [
                            'nama_kegiatan' => '',
                            'keterangan' => '',
                        ];
                    }
                @endphp

                @foreach ($activityRows as $index => $activity)
                    <div class="grid gap-3 rounded-xl bg-slate-50 p-4">
                        <div class="grid gap-2">
                            <label for="nama_kegiatan_{{ $index }}"
                                class="text-sm font-bold">
                                Nama Kegiatan
                            </label>

                            <input id="nama_kegiatan_{{ $index }}"
                                name="ekstrakurikuler[{{ $index }}][nama_kegiatan]"
                                type="text"
                                maxlength="150"
                                value="{{ $activity['nama_kegiatan'] ?? '' }}"
                                placeholder="Contoh: Pramuka"
                                @error("ekstrakurikuler.{$index}.nama_kegiatan")
                                    aria-invalid="true"
                                    aria-describedby="nama_kegiatan_error_{{ $index }}"
                                @enderror
                                class="rk-field">

                            @error("ekstrakurikuler.{$index}.nama_kegiatan")
                                <p id="nama_kegiatan_error_{{ $index }}"
                                    class="text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="grid gap-2">
                            <label for="keterangan_{{ $index }}"
                                class="text-sm font-bold">
                                Keterangan atau Capaian
                            </label>

                            <textarea id="keterangan_{{ $index }}"
                                name="ekstrakurikuler[{{ $index }}][keterangan]"
                                rows="2"
                                maxlength="1000"
                                placeholder="Masukkan keterangan kegiatan..."
                                @error("ekstrakurikuler.{$index}.keterangan")
                                    aria-invalid="true"
                                    aria-describedby="keterangan_error_{{ $index }}"
                                @enderror
                                class="rk-field">{{ $activity['keterangan'] ?? '' }}</textarea>

                            @error("ekstrakurikuler.{$index}.keterangan")
                                <p id="keterangan_error_{{ $index }}"
                                    class="text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                @endforeach

                @error('ekstrakurikuler')
                    <p class="text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror

                <p class="text-xs text-slate-500">
                    Kosongkan nama dan keterangan kegiatan jika
                    ingin menghapus kegiatan tersebut.
                    Maksimal 20 kegiatan.
                </p>
            </div>

            <div class="grid gap-4 border-t border-slate-200 pt-6">
                <div>
                    <h2 class="text-lg font-bold text-[#102d4b]">
                        Ketidakhadiran
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Masukkan jumlah hari ketidakhadiran siswa
                        selama semester ini. Isi 0 jika tidak ada
                        ketidakhadiran. Kolom kosong berarti
                        data belum diisi.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ([
                        'sakit' => 'Sakit',
                        'izin' => 'Izin',
                        'tanpa_keterangan' => 'Tanpa Keterangan'
                    ] as $field => $label)

                        <div class="grid gap-2">
                            <label for="{{ $field }}"
                                class="text-sm font-bold">
                                {{ $label }}
                            </label>

                            <input id="{{ $field }}"
                                name="{{ $field }}"
                                type="number"
                                min="0"
                                max="65535"
                                step="1"
                                value="{{ old($field, $record?->$field) }}"
                                @error($field)
                                    aria-invalid="true"
                                    aria-describedby="{{ $field }}_error"
                                @enderror
                                class="rk-field">

                            @error($field)
                                <p id="{{ $field }}_error"
                                    class="text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-4 border-t border-slate-200 pt-6">
                <div>
                    <h2 class="text-lg font-bold text-[#102d4b]">
                        Catatan Wali Kelas
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Berikan catatan mengenai perkembangan,
                        sikap, dan saran untuk siswa.
                    </p>
                </div>

                <div class="grid gap-2">
                    <label for="catatan_wali_kelas"
                        class="text-sm font-bold">
                        Catatan
                    </label>

                    <textarea id="catatan_wali_kelas"
                        name="catatan_wali_kelas"
                        rows="4"
                        maxlength="3000"
                        placeholder="Masukkan catatan wali kelas..."
                        @error('catatan_wali_kelas')
                            aria-invalid="true"
                            aria-describedby="catatan_wali_kelas_error"
                        @enderror
                        class="rk-field">{{ old('catatan_wali_kelas', $record?->catatan_wali_kelas) }}</textarea>

                    @error('catatan_wali_kelas')
                        <p id="catatan_wali_kelas_error"
                            class="text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            @if ($year->semester === 'Genap')
                <div class="grid gap-4 border-t border-slate-200 pt-6">
                    <div>
                        <h2 class="text-lg font-bold text-[#102d4b]">
                            Keputusan Kenaikan Kelas
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Catat keputusan kenaikan kelas berdasarkan
                            hasil rapat dan keputusan sekolah.
                        </p>
                    </div>

                    @if ($promotionLocked)
                        @if ($record?->keputusan_kenaikan === 'naik')
                            <div class="rounded-xl bg-emerald-50 p-4">
                                <p class="text-sm font-bold text-emerald-700">
                                    Naik Kelas
                                </p>
                            </div>
                        @elseif ($record?->keputusan_kenaikan === 'tidak_naik')
                            <div class="rounded-xl bg-red-50 p-4">
                                <p class="text-sm font-bold text-red-700">
                                    Tidak Naik Kelas
                                </p>
                            </div>
                        @else
                            <div class="rounded-xl bg-amber-50 p-4">
                                <p class="text-sm font-bold text-amber-800">
                                    Belum Ditetapkan
                                </p>
                            </div>
                        @endif

                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-sm font-bold text-slate-700">
                                Keputusan Tidak Dapat Diubah
                            </p>

                            @if (! $year->aktif)
                                <p class="mt-1 text-sm text-slate-500">
                                    Periode yang dipilih sudah tidak aktif.
                                </p>
                            @else
                                <p class="mt-1 text-sm text-slate-500">
                                    Riwayat penempatan belum memenuhi
                                    ketentuan atau penempatan pada tahun
                                    ajaran berikutnya sudah tercatat.
                                    Hubungi administrator untuk
                                    memeriksa riwayat penempatan siswa.
                                </p>
                            @endif
                        </div>
                    @else
                        @php
                            $decision = old(
                                'keputusan_kenaikan',
                                $record?->keputusan_kenaikan
                            );
                        @endphp

                        <fieldset class="grid gap-3"
                            aria-describedby="keputusan_help{{ $errors->has('keputusan_kenaikan') ? ' keputusan_error' : '' }}">

                            <legend class="mb-3 text-sm font-bold">
                                Pilih Keputusan
                            </legend>

                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 hover:bg-slate-50">
                                <input type="radio"
                                    name="keputusan_kenaikan"
                                    value=""
                                    @checked($decision === null || $decision === '')
                                    class="mt-1 text-slate-600">

                                <div>
                                    <p class="text-sm font-bold text-slate-700">
                                        Belum Ditetapkan
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Keputusan kenaikan kelas
                                        belum ditetapkan oleh sekolah.
                                    </p>
                                </div>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 hover:bg-emerald-50">
                                <input type="radio"
                                    name="keputusan_kenaikan"
                                    value="naik"
                                    @checked($decision === 'naik')
                                    class="mt-1 text-emerald-600">

                                <div>
                                    <p class="text-sm font-bold text-emerald-700">
                                        Naik Kelas
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Siswa dinyatakan naik ke tingkat
                                        kelas berikutnya.
                                        Kelulusan kelas VI dikelola
                                        secara terpisah.
                                    </p>
                                </div>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 hover:bg-red-50">
                                <input type="radio"
                                    name="keputusan_kenaikan"
                                    value="tidak_naik"
                                    @checked($decision === 'tidak_naik')
                                    class="mt-1 text-red-600">

                                <div>
                                    <p class="text-sm font-bold text-red-700">
                                        Tidak Naik Kelas
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Siswa tetap berada pada tingkat
                                        kelas yang sama pada tahun
                                        ajaran berikutnya.
                                    </p>
                                </div>
                            </label>
                        </fieldset>

                        <p id="keputusan_help"
                            class="text-xs text-slate-500">
                            Pastikan keputusan yang dipilih sesuai
                            dengan hasil rapat kenaikan kelas.
                        </p>
                    @endif

                    @error('keputusan_kenaikan')
                        <p id="keputusan_error"
                            class="text-sm text-red-600"
                            role="alert">
                            {{ $message }}
                        </p>
                    @enderror

                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                        <p class="text-sm leading-relaxed text-blue-800">
                            Penyimpanan keputusan kenaikan kelas tidak
                            langsung mengubah kelas aktif siswa.
                            Administrator akan mengelola penempatan
                            siswa pada tahun ajaran berikutnya
                            berdasarkan keputusan yang telah dicatat.
                        </p>
                    </div>
                </div>
            @endif

            <div class="flex justify-end border-t border-slate-200 pt-6">
                <button type="submit"
                    class="rk-button rk-button-primary">
                    Simpan Data Rapor
                </button>
            </div>
        </form>
    </section>
@endsection