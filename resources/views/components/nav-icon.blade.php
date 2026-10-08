@props(['name'])

<svg {{ $attributes->class(['size-5 shrink-0']) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
    @switch($name)
        @case('dashboard')
            <path d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            @break
        @case('guru')
            <path d="M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20m7-9a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7-6.5a4 4 0 0 1 0 7.7m5 8.8v-1.5a4 4 0 0 0-3-3.9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            @break
        @case('siswa')
            <path d="m3 8 9-5 9 5-9 5-9-5Zm3 2.5V16l6 3 6-3v-5.5M21 9v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('kelas')
            <path d="M4 20V5l8-2 8 2v15M8 9h2m4 0h2M8 13h2m4 0h2M9 20v-3h6v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('mapel')
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2ZM8 7h8m-8 4h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('calendar')
            <path d="M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Zm2-2v4m10-4v4M3 9h18M7 13h3m4 0h3m-10 4h3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('pengampu')
            <path d="M9 15 7 17a3 3 0 1 1-4-4l3-3a3 3 0 0 1 4 0m5-1 2-2a3 3 0 1 1 4 4l-3 3a3 3 0 0 1-4 0m-6 1 8-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('penilaian')
            <path d="M8 4h8m-7-2h6a1 1 0 0 1 1 1v2H8V3a1 1 0 0 1 1-1ZM6 4H5a2 2 0 0 0-2 2v14h18V6a2 2 0 0 0-2-2h-1M7 10h10M7 14h6m-6 4h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('nilai')
            <path d="M4 5h16v14H4V5Zm0 5h16M9 5v14m5-9v9m4-6v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('chart')
            <path d="M4 19V5m0 14h16M8 16v-4m4 4V8m4 8v-6m4 6V5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('attention')
            <path d="M12 3 2.5 20h19L12 3Zm0 6v5m0 3h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('rekap')
            <path d="M6 2h9l3 3v17H6V2Zm9 0v4h4M9 11h6m-6 4h6m-6 4h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('description')
            <path d="M5 3h14v18H5V3Zm4 5h6M9 12h6m-6 4h4m5-12 2 2-5 5-3 1 1-3 5-5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('printer')
            <path d="M7 8V3h10v5M7 17H4V9h16v8h-3m-10-4h10v8H7v-8Zm10-1h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('profile')
            <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 9a7 7 0 0 1 14 0H5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('upload')
            <path d="M12 16V4m-4 4 4-4 4 4M5 14v5h14v-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('logout')
            <path d="M10 5H5v14h5m4-4 4-3-4-3m4 3H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @default
            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/><path d="M12 8v4m0 4h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
    @endswitch
</svg>
