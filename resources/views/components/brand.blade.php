@props([
    'href' => route('home'),
    'subtitle' => 'Sistem Rapor Digital',
    'onDark' => false,
])

<a href="{{ $href }}" {{ $attributes->class(['inline-flex items-center gap-3 rounded-xl']) }}>
    <img src="{{ asset('images/raporku-logo.png') }}?v=20261106-1" alt="Logo RaporKu dengan maskot bekantan" class="size-14 shrink-0 object-contain">
    <span class="min-w-0">
        <small @class([
            'block truncate text-xs font-semibold',
            'text-slate-400' => $onDark,
            'text-slate-500' => ! $onDark,
        ])>{{ $subtitle }}</small>
    </span>
</a>
