<form method="GET" action="{{ $filterAction }}" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-[1fr_1fr_auto] sm:items-end">
    <div>
        <label for="kelas_id" class="text-xs font-bold text-slate-600">Kelas perwalian</label>
        <select id="kelas_id" name="kelas_id" class="rk-field mt-2" data-wali-class-filter>
            @foreach ($classes as $classOption)
                <option value="{{ $classOption->id }}" @selected($selectedClass?->id === $classOption->id)>{{ $classOption->tingkat }} {{ $classOption->nama }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="tahun_ajaran_id" class="text-xs font-bold text-slate-600">Tahun ajaran & semester</label>
        <select id="tahun_ajaran_id" name="tahun_ajaran_id" class="rk-field mt-2" data-wali-year-filter>
            @foreach ($schoolYears as $yearOption)
                <option value="{{ $yearOption->id }}" @selected($selectedSchoolYear?->id === $yearOption->id)>{{ $yearOption->tahun }} · {{ $yearOption->semester }}{{ $yearOption->aktif ? ' (Aktif)' : '' }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="rk-button rk-button-primary">Terapkan Filter</button>
</form>
