@if ($errors->any())
    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
        <p class="font-bold">Periksa kembali data penilaian.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="grid gap-5" data-loading-form>
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    @if ($assessment === null)
        <div class="grid gap-2">
            <label for="pengampu_id" class="text-sm font-bold text-slate-700">Tugas Mengajar <span class="text-red-500">*</span></label>
            <select id="pengampu_id" name="pengampu_id" required class="rk-field" aria-describedby="pengampu-help" data-assessment-assignment>
                <option value="">Pilih kelas dan mata pelajaran</option>
                @foreach ($assignments as $assignment)
                    <option value="{{ $assignment->id }}" @selected((int) old('pengampu_id', $selectedAssignmentId) === $assignment->id)>{{ $assignment->mataPelajaran->nama }} · {{ $assignment->kelas->tingkat }} {{ $assignment->kelas->nama }} · {{ $assignment->tahunAjaran->tahun }} — Semester {{ $assignment->tahunAjaran->semester }}</option>
                @endforeach
            </select>
            <p id="pengampu-help" class="text-xs text-slate-500">Penilaian hanya dapat dibuat untuk tugas mengajar Anda.</p>
            @error('pengampu_id')<p class="text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
    @else
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
            <strong class="block">{{ $assessment->pengampu->mataPelajaran->nama }} · {{ $assessment->pengampu->kelas->tingkat }} {{ $assessment->pengampu->kelas->nama }}</strong>
            <span>{{ $assessment->pengampu->tahunAjaran->tahun }} — Semester {{ $assessment->pengampu->tahunAjaran->semester }}</span>
            <p class="mt-2 text-xs">Tugas mengajar tidak dapat dipindahkan setelah penilaian dibuat untuk mencegah nilai terhubung ke kelas yang salah.</p>
        </div>
    @endif

    <div class="grid gap-2">
        <label for="tujuan_pembelajaran_id" class="text-sm font-bold text-slate-700">Tujuan Pembelajaran <span class="text-red-500">*</span></label>
        <select id="tujuan_pembelajaran_id" name="tujuan_pembelajaran_id" class="rk-field" aria-describedby="tujuan-pembelajaran-help" data-assessment-goals @if ($assessment === null || $assessment->tujuan_pembelajaran_id !== null) required @endif>
            <option value="">{{ $assessment === null ? 'Pilih TP' : ($assessment->tujuan_pembelajaran_id ? 'Pilih TP' : 'Belum dipetakan') }}</option>
            @foreach ($assignments as $goalAssignment)
                <optgroup label="{{ $goalAssignment->mataPelajaran->nama }} · {{ $goalAssignment->kelas->tingkat }} {{ $goalAssignment->kelas->nama }} · {{ $goalAssignment->tahunAjaran->tahun }} — Semester {{ $goalAssignment->tahunAjaran->semester }}" data-assessment-goal-group="{{ $goalAssignment->id }}">
                    @foreach ($goalAssignment->tujuanPembelajaran as $tujuanPembelajaran)
                        <option value="{{ $tujuanPembelajaran->id }}" @selected((int) old('tujuan_pembelajaran_id', $assessment?->tujuan_pembelajaran_id) === $tujuanPembelajaran->id)>TP {{ $tujuanPembelajaran->urutan }} · {{ str($tujuanPembelajaran->tujuan)->limit(110) }} (KKTP {{ number_format((float) $tujuanPembelajaran->kktp, 2, ',', '.') }})</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        <p id="tujuan-pembelajaran-help" class="text-xs leading-5 text-slate-500">Pilih TP dalam tugas mengajar ini. Penilaian lama yang belum diketahui kaitannya tetap belum dipetakan sampai Anda memilih TP.</p>
        @error('tujuan_pembelajaran_id')<p class="text-xs font-semibold text-red-600" role="alert">{{ $message }}</p>@enderror
        @if ($assessment?->tujuan_pembelajaran_id === null)
            <p class="text-xs font-semibold text-amber-700">Penilaian ini belum dipetakan ke TP. Sistem tidak menentukan hubungan secara otomatis.</p>
        @endif
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div class="grid gap-2 sm:col-span-2"><label for="nama" class="text-sm font-bold text-slate-700">Nama Penilaian <span class="text-red-500">*</span></label><input id="nama" name="nama" type="text" required maxlength="255" value="{{ old('nama', $assessment?->nama) }}" placeholder="Contoh: Tugas Persamaan Linear" class="rk-field" @error('nama') aria-invalid="true" @enderror>@error('nama')<p class="text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
        <div class="grid gap-2">
            <label for="jenis" class="text-sm font-bold text-slate-700">Bentuk kegiatan <span class="text-red-500">*</span></label>
            <select id="jenis" name="jenis" required class="rk-field"><option value="">Pilih bentuk kegiatan</option>@foreach (['tugas' => 'Tugas', 'ulangan_harian' => 'Ulangan Harian', 'uts' => 'UTS', 'uas' => 'UAS'] as $value => $label)<option value="{{ $value }}" @selected(old('jenis', $assessment?->jenis) === $value)>{{ $label }}</option>@endforeach</select>
            <p class="text-xs leading-5 text-slate-500">Menjelaskan kegiatan yang dilakukan, misalnya tugas, ulangan harian, UTS, atau UAS. Pilihan ini tidak otomatis menentukan fungsi asesmen.</p>
            @error('jenis')<p class="text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-2">
            <label for="kategori_asesmen" class="text-sm font-bold text-slate-700">Fungsi asesmen @if ($assessment === null || $assessment->kategori_asesmen !== null)<span class="text-red-500">*</span>@endif</label>
            <select id="kategori_asesmen" name="kategori_asesmen" @required($assessment === null || $assessment?->kategori_asesmen !== null) class="rk-field"><option value="">Belum diklasifikasikan</option>@foreach (App\Models\Penilaian::KATEGORI_LABELS as $value => $label)<option value="{{ $value }}" @selected(old('kategori_asesmen', $assessment?->kategori_asesmen) === $value)>{{ $label }}</option>@endforeach</select>
            <p class="text-xs leading-5 text-slate-500">Pilih fungsi untuk pengolahan: formatif menjadi umpan balik; sumatif Lingkup Materi, Penilaian Tengah Semester (PTS), dan Asesmen Akhir Semester (AAS) saat ini direkap terpisah. Kategori penilaian lama tidak ditentukan otomatis dari bentuk kegiatannya. Pastikan kedua pilihan sesuai maksud asesmen. Kombinasi yang tidak biasa, seperti UAS dengan kategori formatif, belum dibatasi otomatis dan perlu mengikuti keputusan sekolah.</p>
            @error('kategori_asesmen')<p class="text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-2"><label for="tanggal" class="text-sm font-bold text-slate-700">Tanggal <span class="text-red-500">*</span></label><input id="tanggal" name="tanggal" type="date" required value="{{ old('tanggal', $assessment?->tanggal?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="rk-field">@error('tanggal')<p class="text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
        <div class="grid gap-2"><label for="urutan" class="text-sm font-bold text-slate-700">Urutan <span class="text-red-500">*</span></label><input id="urutan" name="urutan" type="number" min="1" max="999" required value="{{ old('urutan', $assessment?->urutan ?? 1) }}" class="rk-field"><p class="text-xs text-slate-500">Menentukan urutan pada rekap dan analisis.</p>@error('urutan')<p class="text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
        <div class="grid gap-2"><label for="bobot" class="text-sm font-bold text-slate-700">Bobot penilaian <span class="font-normal text-slate-400">(informasi saja, opsional)</span></label><div class="relative"><input id="bobot" name="bobot" type="number" min="0.01" max="100" step="0.01" value="{{ old('bobot', $assessment?->bobot) }}" placeholder="Contoh: 20" class="rk-field pr-12"><span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm font-bold text-slate-400">%</span></div><p class="text-xs leading-5 text-slate-500">Bobot ini hanya catatan pada penilaian; tidak memengaruhi rekap hasil TP maupun simulasi nilai akhir. Jika sekolah memakai pembobotan simulasi, bobot TP dimasukkan terpisah di halaman Rekap Nilai.</p>@error('bobot')<p class="text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><a href="{{ route('guru.penilaian.index') }}" class="rk-button rk-button-secondary">Batal</a><button type="submit" class="rk-button rk-button-primary"><span data-submit-label>{{ $submitLabel }}</span></button></div>
</form>
