@props([
    'href' => route('home'),
    'subtitle' => 'Sistem Rapor Digital',
    'onDark' => false,
])

<a href="{{ $href }}" {{ $attributes->class(['inline-flex items-center gap-3 rounded-xl']) }}>
    <span @class([
        'grid size-11 shrink-0 place-items-center rounded-xl text-white shadow-sm',
        'bg-emerald-500' => $onDark,
        'bg-emerald-600' => ! $onDark,
    ]) aria-hidden="true">
        <svg class="size-6" viewBox="0 0 40 40" fill="none">
            <path d="M9 11.5c4.5-1.4 8.2-.8 11 1.6v16.4c-2.8-2.4-6.5-3-11-1.6V11.5Zm22 0c-4.5-1.4-8.2-.8-11 1.6v16.4c2.8-2.4 6.5-3 11-1.6V11.5Z" stroke="currentColor" stroke-width="2.3" stroke-linejoin="round"/>
            <path d="m13.8 19.8 2.4 2.4 4.1-4.7" stroke="#a7f3d0" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </span>
    <span class="min-w-0">
        <strong @class([
            'block text-base font-extrabold tracking-tight',
            'text-white' => $onDark,
            'text-raporku-navy' => ! $onDark,
        ])>{{ config('app.name', 'RaporKu') }}</strong>
        <small @class([
            'block truncate text-[0.68rem] font-semibold',
            'text-slate-400' => $onDark,
            'text-slate-500' => ! $onDark,
        ])>{{ $subtitle }}</small>
    </span>
</a>
