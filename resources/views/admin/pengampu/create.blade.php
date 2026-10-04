@extends('layouts.admin')
@section('title', 'Tambah Pengampu')
@section('content')
    <section class="mx-auto max-w-3xl"><a href="{{ route('admin.pengampu.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-emerald-700">&larr; Kembali ke Data Pengampu</a><div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/[0.03] sm:p-8"><div class="border-b border-slate-200 pb-6"><p class="text-sm font-semibold text-emerald-600">Data Pengampu</p><h1 class="mt-2 text-2xl font-extrabold tracking-tight text-[#102d4b]">Tambah Pengampu</h1><p class="mt-2 text-sm leading-6 text-slate-500">Pilih guru, mata pelajaran, kelas, dan tahun ajaran.</p></div><div class="pt-6">@include('admin.pengampu._form', ['action' => route('admin.pengampu.store'), 'method' => 'POST', 'pengampu' => null, 'submitLabel' => 'Simpan'])</div></div></section>
@endsection
