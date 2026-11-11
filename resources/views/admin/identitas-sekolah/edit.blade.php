@extends('layouts.admin')

@section('title', 'Identitas Sekolah')

@section('content')
<section class="mx-auto max-w-4xl"><h1 class="text-3xl font-extrabold text-[#102d4b]">Identitas Sekolah</h1><p class="mt-2 text-sm text-slate-500">Informasi yang tersedia akan digunakan pada dokumen rapor.</p>
    <form method="POST" action="{{ route('admin.identitas-sekolah.update') }}" class="mt-6 grid gap-4 rounded-2xl border border-slate-200 bg-white p-6">@csrf @method('PUT')
        @foreach (['nama_sekolah' => 'Nama Sekolah', 'npsn' => 'NPSN', 'alamat' => 'Alamat', 'nama_kepala_sekolah' => 'Nama Kepala Sekolah', 'nip_kepala_sekolah' => 'NIP Kepala Sekolah'] as $field => $label)
            <div class="grid gap-2"><label for="{{ $field }}" class="text-sm font-bold text-slate-700">{{ $label }}</label>@if ($field === 'alamat')<textarea id="{{ $field }}" name="{{ $field }}" rows="3" class="rk-field">{{ old($field, $school?->$field) }}</textarea>@else<input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $school?->$field) }}" @required($field === 'nama_sekolah') class="rk-field">@endif @error($field)<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
        @endforeach
        <button class="rk-button rk-button-primary">Simpan Identitas</button>
    </form>
    <form method="POST" action="{{ route('admin.keputusan-kenaikan.store') }}" class="mt-6 grid gap-4 rounded-2xl border border-slate-200 bg-white p-6">
        <h2 class="font-bold">Catat Keputusan Kenaikan Kelas (Semester Genap)</h2><p class="text-sm text-slate-500">Keputusan diisi admin berdasarkan pengesahan pihak sekolah, bukan dihitung dari nilai.</p>
        @csrf<label class="grid gap-2 text-sm">Siswa<select name="siswa_id" class="rk-field" required>@foreach ($students as $student)<option value="{{ $student->id }}">{{ $student->nama }} · kelas ID {{ $student->kelas_id }}</option>@endforeach</select></label>
        <label class="grid gap-2 text-sm">Tahun ajaran<select name="tahun_ajaran_id" class="rk-field" required>@foreach ($schoolYears as $year)<option value="{{ $year->id }}">{{ $year->tahun }} · Genap</option>@endforeach</select></label>
        <label class="grid gap-2 text-sm">Keputusan<select name="keputusan_kenaikan" class="rk-field" required><option value="naik">Naik</option><option value="tidak_naik">Tidak naik</option></select></label>
        <button class="rk-button rk-button-primary">Simpan Keputusan</button>
    </form>
</section>
@endsection
