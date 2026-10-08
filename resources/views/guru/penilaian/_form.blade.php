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
            <select id="pengampu_id" name="pengampu_id" required class="rk-field" aria-describedby="pengampu-help">
                <option value="">Pilih kelas dan mata pelajaran</option>
                @foreach ($assignments as $assignment)
                    <option value="{{ $assignment->id }}" @selected((int) old('pengampu_id', $selectedAssignmentId) === $assignment->id)>{{ $assignment->mataPelajaran->nama }} · {{ $assignment->kelas->tingkat }} {{ $assignment->kelas->nama }} · {{ $assignment->tahunAjaran->tahun }} {{ $assignment->tahunAjaran->semester }}</option>
                @endforeach
            </select>
            <p id="pengampu-help" class="text-xs text-slate-500">Penilaian hanya dapat dibuat untuk tugas mengajar Anda.</p>
            @error('pengampu_id')<p class="text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
    @else
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
            <strong class="block">{{ $assessment->pengampu->mataPelajaran->nama }} · {{ $assessment->pengampu->kelas->tingkat }} {{ $assessment->pengampu->kelas->nama }}</strong>
            <span>{{ $assessment->pengampu->tahunAjaran->tahun }} · {{ $assessment->pengampu->tahunAjaran->semester }}</span>
            <p class="mt-2 text-xs">Tugas mengajar tidak dapat dipindahkan setelah penilaian dibuat untuk mencegah nilai terhubung ke kelas yang salah.</p>
        </div>
    @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div class="grid gap-2 sm:col-span-2"><label for="nama" class="text-sm font-bold text-slate-700">Nama Penilaian <span class="text-red-500">*</span></label><input id="nama" name="nama" type="text" required maxlength="255" value="{{ old('nama', $assessment?->nama) }}" placeholder="Contoh: Tugas Persamaan Linear" class="rk-field" @error('nama') aria-invalid="true" @enderror>@error('nama')<p class="text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
        <div class="grid gap-2"><label for="jenis" class="text-sm font-bold text-slate-700">Jenis <span class="text-red-500">*</span></label><select id="jenis" name="jenis" required class="rk-field"><option value="">Pilih jenis</option>@foreach (['tugas' => 'Tugas', 'ulangan_harian' => 'Ulangan Harian', 'uts' => 'UTS', 'uas' => 'UAS'] as $value => $label)<option value="{{ $value }}" @selected(old('jenis', $assessment?->jenis) === $value)>{{ $label }}</option>@endforeach</select>@error('jenis')<p class="text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
        <div class="grid gap-2"><label for="tanggal" class="text-sm font-bold text-slate-700">Tanggal <span class="text-red-500">*</span></label><input id="tanggal" name="tanggal" type="date" required value="{{ old('tanggal', $assessment?->tanggal?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="rk-field">@error('tanggal')<p class="text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
        <div class="grid gap-2"><label for="urutan" class="text-sm font-bold text-slate-700">Urutan <span class="text-red-500">*</span></label><input id="urutan" name="urutan" type="number" min="1" max="999" required value="{{ old('urutan', $assessment?->urutan ?? 1) }}" class="rk-field"><p class="text-xs text-slate-500">Menentukan urutan pada rekap dan analisis.</p>@error('urutan')<p class="text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
        <div class="grid gap-2"><label for="bobot" class="text-sm font-bold text-slate-700">Bobot <span class="font-normal text-slate-400">(opsional)</span></label><div class="relative"><input id="bobot" name="bobot" type="number" min="0.01" max="100" step="0.01" value="{{ old('bobot', $assessment?->bobot) }}" placeholder="Contoh: 20" class="rk-field pr-12"><span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm font-bold text-slate-400">%</span></div><p class="text-xs text-slate-500">Rekap memakai bobot jika seluruh penilaian memiliki bobot.</p>@error('bobot')<p class="text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><a href="{{ route('guru.penilaian.index') }}" class="rk-button rk-button-secondary">Batal</a><button type="submit" class="rk-button rk-button-primary"><span data-submit-label>{{ $submitLabel }}</span></button></div>
</form>
