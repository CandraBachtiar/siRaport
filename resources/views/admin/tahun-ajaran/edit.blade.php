@extends('layouts.admin')

@section('title', 'Edit Tahun Ajaran')

@section('content')
    <section class="mx-auto max-w-3xl">
        <a href="{{ route('admin.tahun-ajaran.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-emerald-700">&larr; Kembali ke Data Tahun Ajaran</a>
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/[0.03] sm:p-8">
            <div class="border-b border-slate-200 pb-6">
                <p class="text-sm font-semibold text-emerald-600">Data Tahun Ajaran</p>
                <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-[#102d4b]">Edit Tahun Ajaran</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Perbarui periode, semester, atau status aktif.</p>
            </div>
            <div class="pt-6">@include('admin.tahun-ajaran._form', ['action' => route('admin.tahun-ajaran.update', $tahunAjaran), 'method' => 'PUT', 'tahunAjaran' => $tahunAjaran, 'submitLabel' => 'Simpan Perubahan'])</div>
        </div>
    </section>
@endsection
