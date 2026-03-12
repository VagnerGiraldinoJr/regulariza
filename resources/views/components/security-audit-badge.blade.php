@props([
    'inline' => false,
])

<div @class([
    'print:hidden',
    'pointer-events-none fixed bottom-4 left-4 z-40' => ! $inline,
    'w-full' => $inline,
])>
    <img
        src="{{ route('assets.siteblindado.svg') }}"
        alt="Site Blindado"
        @class([
            'w-auto object-contain opacity-95',
            'h-9' => ! $inline,
            'mx-auto h-7' => $inline,
        ])
    />
</div>
