@if ($errors->any())
    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
        <p class="font-bold">Periksa kembali data tujuan pembelajaran.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="grid gap-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-900">
        <strong class="block">{{ $assignment->mataPelajaran->nama }} · {{ $assignment->kelas->tingkat }} {{ $assignment->kelas->nama }}</strong>
        <span>{{ $assignment->tahunAjaran->tahun }} · Semester {{ $assignment->tahunAjaran->semester }}</span>
        <p class="mt-2">TP dan KKTP ini hanya berlaku untuk tugas mengajar tersebut.</p>
    </div>

    <div class="grid gap-5 sm:grid-cols-[10rem_minmax(0,1fr)]">
        <div class="grid content-start gap-2">
            <label for="urutan" class="text-base font-semibold text-slate-700">Urutan TP</label>
            <input id="urutan" name="urutan" type="number" min="1" max="999" step="1" required value="{{ old('urutan', $tujuan?->urutan ?? ($nextOrder ?? 1)) }}" class="rk-field" @if ($errors->has('urutan')) aria-invalid="true" aria-describedby="urutan-error" @endif>
            @error('urutan')<p id="urutan-error" class="text-sm font-medium text-red-700" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-2">
            <label for="tujuan" class="text-base font-semibold text-slate-700">Rumusan Tujuan Pembelajaran</label>
            <textarea id="tujuan" name="tujuan" rows="5" maxlength="5000" required class="rk-field min-h-36" aria-describedby="tujuan-help @if ($errors->has('tujuan')) tujuan-error @endif" @if ($errors->has('tujuan')) aria-invalid="true" @endif>{{ old('tujuan', $tujuan?->tujuan) }}</textarea>
            <p id="tujuan-help" class="text-sm leading-6 text-slate-500">Tuliskan kemampuan yang diharapkan dikuasai siswa setelah mengikuti pembelajaran.</p>
            @error('tujuan')<p id="tujuan-error" class="text-sm font-medium text-red-700" role="alert">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid max-w-sm gap-2">
        <label for="kktp" class="text-base font-semibold text-slate-700">KKTP Angka</label>
        <div class="relative">
            <input id="kktp" name="kktp" type="number" min="0" max="100" step="0.01" required value="{{ old('kktp', $tujuan?->kktp) }}" class="rk-field pr-14" @if ($errors->has('kktp')) aria-invalid="true" aria-describedby="kktp-help kktp-error" @endif>
            <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm font-bold text-slate-500">dari 100</span>
        </div>
        <p id="kktp-help" class="text-sm leading-6 text-slate-500">KKTP adalah batas untuk menilai ketercapaian TP ini, bukan bobot atau nilai akhir mata pelajaran.</p>
        @error('kktp')<p id="kktp-error" class="text-sm font-medium text-red-700" role="alert">{{ $message }}</p>@enderror
    </div>

    <div class="grid max-w-sm gap-2">
        <label for="metode_rekap" class="text-base font-semibold text-slate-700">Metode rekap tugas pada TP</label>
        <select id="metode_rekap" name="metode_rekap" required class="rk-field">
            <option value="">Pilih metode rekap</option>
            @foreach (App\Models\TujuanPembelajaran::METODE_REKAP_LABELS as $value => $label)
                <option value="{{ $value }}" @selected(old('metode_rekap', $tujuan?->metode_rekap) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="text-sm leading-6 text-slate-500">Pilihan guru ini mengolah nilai di setiap kategori sumatif secara terpisah untuk TP ini. Pilihan nilai tertinggi berasal dari kebutuhan rekap guru, bukan rumus nilai akhir yang diwajibkan panduan. Asesmen formatif tetap menjadi umpan balik.</p>
        @error('metode_rekap')<p class="text-sm font-medium text-red-700" role="alert">{{ $message }}</p>@enderror
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
        <a href="{{ route('guru.tujuan-pembelajaran.index', $assignment) }}" class="rk-button rk-button-secondary">Batal</a>
        <button type="submit" class="rk-button rk-button-primary">{{ $submitLabel }}</button>
    </div>
</form>
