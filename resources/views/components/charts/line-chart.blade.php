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
    });
    $segments = collect();
    $currentSegment = [];

    foreach ($coordinates as $coordinate) {
        if ($coordinate === null) {
            if ($currentSegment !== []) {
                $segments->push($currentSegment);
                $currentSegment = [];
            }

            continue;
        }

        $currentSegment[] = $coordinate;
    }

    if ($currentSegment !== []) {
        $segments->push($currentSegment);
    }

    $lineSegments = $segments
        ->filter(fn (array $segment): bool => count($segment) > 1)
        ->map(fn (array $segment): string => collect($segment)->map(fn (array $point): string => $point['x'].','.$point['y'])->implode(' '));
    $visibleCoordinates = $coordinates->filter()->values();
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
            @foreach ($lineSegments as $lineSegment)
                <polyline points="{{ $lineSegment }}" fill="none" stroke="#059669" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" />
            @endforeach
            @foreach ($visibleCoordinates as $point)
                <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="2" fill="#ffffff" stroke="#059669" stroke-width="1.2"><title>{{ $point['label'] }}{{ isset($point['dateLabel']) ? ' · '.$point['dateLabel'] : '' }}: {{ number_format((float) $point['value'], 2, ',', '.') }}</title></circle>
            @endforeach
        </svg>
        <div class="grid grid-cols-2 gap-x-4 gap-y-3 border-t border-slate-100 pt-3 sm:grid-cols-3">
            @foreach ($chartPoints as $point)
                <div class="min-w-0"><strong class="block truncate text-xs text-slate-600" title="{{ $point['label'] }}">{{ $point['label'] }}</strong><span class="mt-1 block text-xs font-bold text-slate-700">{{ $point['dateLabel'] ?? $point['shortLabel'] }} · {{ $point['value'] === null ? '—' : number_format((float) $point['value'], 1, ',', '.') }}</span></div>
            @endforeach
        </div>
    @endif
</div>
