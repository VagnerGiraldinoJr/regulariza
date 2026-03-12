@props([
    'inline' => false,
])

<div @class([
    'print:hidden',
    'pointer-events-none fixed bottom-4 left-4 z-40' => ! $inline,
    'w-full' => $inline,
])>
    <div @class([
        'inline-flex items-center gap-2 rounded-full border border-slate-200/90 bg-white/88 px-3 py-2 shadow-[0_10px_30px_rgba(15,35,53,0.10)] backdrop-blur',
        'flex w-full justify-center border-white/10 bg-white/95 shadow-none' => $inline,
    ])>
        <img src="{{ asset('assets/selos-seguranca/siteblindado.svg') }}" alt="Site Blindado" class="h-7 w-auto object-contain opacity-95" />
        <div class="leading-tight">
            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">Auditado</p>
            <p class="text-[11px] font-semibold text-slate-700">{{ now()->format('d/m/Y') }}</p>
        </div>
    </div>
</div>
