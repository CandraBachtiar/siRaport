@props([
    'points',
    'title',
    'emptyMessage' => 'Belum ada data yang dapat digambarkan.',
])

@php
    $chartPoints = collect($points)->values();
    $validPoints = $chartPoints->filter(fn (array $point): bool => $point['value'] !== null);
    $lastIndex = max($chartPoints->count() - 1, 1);
    $coordinates = $chartPoints->map(function (array $point, int $index) use ($lastIndex): ?array {
        if ($point['value'] === null) {
            return null;
        }

        return [
            ...$point,
            'x' => round(8 + (($index / $lastIndex) * 88), 2),
            'y' => round(47 - (((float) $point['value'] / 100) * 40), 2),
        ];
    })->filter()->values();
    $polylinePoints = $coordinates->map(fn (array $point): string => $point['x'].','.$point['y'])->implode(' ');
@endphp

<div {{ $attributes }}>
    @if ($validPoints->isEmpty())
        <div class="grid min-h-64 place-items-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 text-center text-sm text-slate-500">{{ $emptyMessage }}</div>
    @else
        <svg class="h-64 w-full overflow-visible" viewBox="0 0 100 58" role="img" aria-label="{{ $title }}">
            <title>{{ $title }}</title>
            @foreach ([100 => 7, 75 => 17, 50 => 27, 25 => 37, 0 => 47] as $label => $y)
                <line x1="8" y1="{{ $y }}" x2="96" y2="{{ $y }}" stroke="#e2e8f0" stroke-width="0.45" stroke-dasharray="2 2" />
                <text x="1" y="{{ $y + 1 }}" fill="#94a3b8" font-size="3">{{ $label }}</text>
            @endforeach
            @if ($coordinates->count() > 1)
                <polyline points="{{ $polylinePoints }}" fill="none" stroke="#059669" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" />
            @endif
            @foreach ($coordinates as $point)
                <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="2" fill="#ffffff" stroke="#059669" stroke-width="1.2"><title>{{ $point['label'] }}: {{ number_format((float) $point['value'], 2, ',', '.') }}</title></circle>
            @endforeach
        </svg>
        <div class="flex gap-1 border-t border-slate-100 pt-3" aria-hidden="true">
            @foreach ($chartPoints as $point)
                <div class="min-w-0 flex-1 text-center"><strong class="block truncate text-[0.65rem] text-slate-500">{{ $point['shortLabel'] }}</strong><span class="mt-1 block text-[0.65rem] font-bold text-slate-700">{{ $point['value'] === null ? '—' : number_format((float) $point['value'], 1, ',', '.') }}</span></div>
            @endforeach
        </div>
    @endif
</div>
