@extends('layouts.admin-v2')

@section('title', 'Import Data Guru')

@section('content')
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-semibold text-emerald-600">Data Guru</p><h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#102d4b]">Import Data Guru</h1><p class="mt-2 text-sm leading-6 text-slate-500">Periksa data dari Excel terlebih dahulu sebelum membuat akun guru.</p></div>
            <a href="{{ route('admin.guru.template') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Download Template Excel</a>
        </div>

        @if ($headerError)
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700" role="alert">{{ $headerError }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700" role="alert"><p>File belum dapat diperiksa:</p><ul class="mt-2 list-inside list-disc font-medium">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/[0.03] sm:p-8">
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm leading-6 text-emerald-900"><strong class="font-bold">Format file:</strong> gunakan kolom <span class="font-bold">NIP</span>, <span class="font-bold">Nama Guru</span>, dan <span class="font-bold">Email</span>. File yang didukung adalah .xlsx atau .xls dengan ukuran maksimal 5 MB.</div>
            <form method="POST" action="{{ route('admin.guru.import.preview') }}" enctype="multipart/form-data" class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-end">
                @csrf
                <div class="grid flex-1 gap-2"><label for="file" class="text-sm font-semibold text-slate-700">File Excel Guru</label><input id="file" name="file" type="file" accept=".xlsx,.xls" required class="block h-12 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-bold file:text-emerald-700 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10"></div>
                <button type="submit" class="inline-flex h-12 items-center justify-center rounded-xl bg-[#102d4b] px-6 text-sm font-bold text-white transition hover:bg-[#0b2945]">Periksa Data</button>
            </form>
        </div>

        @if ($previewRows !== [])
            @php($validCount = collect($previewRows)->where('valid', true)->count())
            @php($errorCount = count($previewRows) - $validCount)
            <div class="mt-8 rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.03]"><div class="flex flex-wrap gap-3 border-b border-slate-200 p-5 text-sm font-bold"><span class="rounded-full bg-slate-100 px-3 py-1.5 text-slate-600">Total: {{ count($previewRows) }}</span><span class="rounded-full bg-emerald-100 px-3 py-1.5 text-emerald-700">Valid: {{ $validCount }}</span><span class="rounded-full bg-red-100 px-3 py-1.5 text-red-700">Error: {{ $errorCount }}</span></div><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200"><thead class="bg-slate-50"><tr><th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">No</th><th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">NIP</th><th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Nama Guru</th><th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Email</th><th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status</th><th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Keterangan</th></tr></thead><tbody class="divide-y divide-slate-100">
                @foreach ($previewRows as $row)<tr><td class="px-5 py-4 text-sm text-slate-500">{{ $row['row'] }}</td><td class="px-5 py-4 text-sm font-semibold text-slate-700">{{ $row['nip'] }}</td><td class="px-5 py-4 text-sm font-bold text-[#102d4b]">{{ $row['name'] }}</td><td class="px-5 py-4 text-sm text-slate-600">{{ $row['email'] }}</td><td class="px-5 py-4">@if ($row['valid'])<span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">VALID</span>@else<span class="rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">ERROR</span>@endif</td><td class="px-5 py-4 text-sm {{ $row['valid'] ? 'text-slate-400' : 'font-semibold text-red-600' }}">{{ $row['message'] ?: '-' }}</td></tr>@endforeach
            </tbody></table></div>@if ($errorCount === 0)<div class="flex justify-end border-t border-slate-200 p-5"><form method="POST" action="{{ route('admin.guru.import.store') }}">@csrf<button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-emerald-600 px-6 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700">Import Data</button></form></div>@endif</div>
        @endif
    </section>
@endsection
