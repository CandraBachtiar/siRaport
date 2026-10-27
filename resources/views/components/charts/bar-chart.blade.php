@props([
    'items',
    'title',
    'emptyMessage' => 'Belum ada data yang dapat digambarkan.',
    'hasData' => null,
])

@php
    $chartItems = collect($items)->values();
    $hasChartData = $hasData ?? $chartItems->contains(fn (array $item): bool => $item['value'] !== null);
    $maximum = max((float) $chartItems->max('value'), 1);
    $chartSummary = $chartItems
        ->map(fn (array $item): string => $item['label'].': '.($item['value'] === null ? 'Belum ada nilai' : (string) $item['value']))
        ->implode('; ');
@endphp

<div {{ $attributes }} role="img" aria-label="{{ $title }}{{ $hasChartData ? ': '.$chartSummary : '. '.$emptyMessage }}">
    @if (! $hasChartData)
        <div class="grid min-h-64 place-items-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 text-center text-sm text-slate-500">{{ $emptyMessage }}</div>
    @else
        <div class="grid min-h-64 content-center gap-4">
            @foreach ($chartItems as $item)
                <div class="grid grid-cols-[4rem_minmax(0,1fr)_2rem] items-center gap-3">
                    <span class="text-xs font-bold text-slate-500">{{ $item['label'] }}</span>
                    <div class="h-8 overflow-hidden rounded-lg bg-slate-100"><div class="h-full rounded-lg bg-emerald-500 transition-[width] duration-200" style="width: {{ $item['value'] === null ? 0 : round(((float) $item['value'] / $maximum) * 100, 2) }}%"></div></div>
                    <strong class="text-right text-sm text-slate-700">{{ $item['value'] === null ? '—' : $item['value'] }}</strong>
                </div>
            @endforeach
        </div>
    @endif
</div>
