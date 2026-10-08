@extends('layouts.guru-workspace')

@section('title', 'Edit Penilaian')

@section('content')
    <section class="mx-auto max-w-3xl">
        <a href="{{ route('guru.penilaian.index', ['pengampu_id' => $assessment->pengampu_id]) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-emerald-700">&larr; Kembali ke Penilaian</a>
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8"><div class="border-b border-slate-200 pb-6"><p class="text-sm font-semibold text-emerald-600">Pembelajaran</p><h1 class="mt-2 text-2xl font-extrabold text-[#102d4b]">Edit Penilaian</h1><p class="mt-2 text-sm text-slate-500">Perbarui informasi penilaian tanpa mengubah konteks tugas mengajar.</p></div><div class="pt-6">@include('guru.penilaian._form', ['action' => route('guru.penilaian.update', $assessment), 'method' => 'PUT', 'submitLabel' => 'Simpan Perubahan'])</div></div>
    </section>
@endsection
