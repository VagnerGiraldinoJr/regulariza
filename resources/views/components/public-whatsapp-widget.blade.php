@php
    $whatsappNumber = preg_replace('/\D+/', '', (string) config('services.cpfclean.whatsapp_number'));
    $defaultMessage = rawurlencode('Olá! Quero informações sobre limpeza de CPF/CNPJ.');
    $whatsappLink = "https://wa.me/{$whatsappNumber}?text={$defaultMessage}";
@endphp

<div class="fixed bottom-5 right-5 z-50" id="public-whatsapp-widget">
    <button
        type="button"
        id="toggle-whatsapp-widget"
        class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-[#25d366] text-white shadow-[0_12px_24px_rgba(0,0,0,0.28)] transition hover:scale-105"
        aria-label="Abrir contato WhatsApp"
    >
        <svg class="h-7 w-7" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
            <path d="M19.1 17.3c-.3-.2-1.8-.9-2.1-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.2-1.2-.4-2.2-1.3-.8-.7-1.3-1.6-1.4-1.9-.2-.3 0-.4.1-.6.1-.1.3-.3.4-.4.1-.1.2-.3.3-.5.1-.2 0-.4 0-.5 0-.1-.7-1.7-1-2.4-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.2.2 2.2 3.4 5.4 4.8.8.3 1.4.5 1.9.7.8.2 1.5.2 2 .1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.2-.3-.2-.6-.4Z"/>
            <path d="M16 3C8.8 3 3 8.7 3 15.8c0 2.3.6 4.5 1.8 6.4L3 29l7-1.8c1.8 1 3.9 1.5 6 1.5 7.2 0 13-5.7 13-12.8S23.2 3 16 3Zm0 23.5c-2 0-3.9-.6-5.4-1.6l-.4-.2-4.1 1.1 1.1-4-.3-.4c-1.1-1.6-1.7-3.4-1.7-5.5C5.2 9.9 10 5.2 16 5.2c6 0 10.8 4.7 10.8 10.6S22 26.5 16 26.5Z"/>
        </svg>
    </button>

    <div id="whatsapp-panel" class="mt-3 hidden w-[320px] max-w-[calc(100vw-2.5rem)] rounded-xl border border-[#d1e4d7] bg-white p-4 shadow-2xl">
        <p class="text-sm font-bold text-slate-800">Fale com nosso SAC</p>
        <p class="mt-1 text-xs text-slate-500">Deixe e-mail e WhatsApp para entrarmos em contato.</p>

        @if (session('public_whatsapp_success'))
            <div class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700">
                {{ session('public_whatsapp_success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('public.whatsapp.store') }}" class="mt-3 space-y-2">
            @csrf
            <input type="hidden" name="origem" value="{{ request()->path() }}">

            <input
                type="text"
                name="nome"
                value="{{ old('nome') }}"
                placeholder="Nome (opcional)"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#20b6c7] focus:outline-none"
            />

            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                placeholder="E-mail *"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#20b6c7] focus:outline-none"
            />

            <input
                type="text"
                name="whatsapp"
                value="{{ old('whatsapp') }}"
                required
                maxlength="15"
                placeholder="WhatsApp com DDD *"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#20b6c7] focus:outline-none"
                data-whatsapp-mask
            />

            @error('email')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
            @error('whatsapp')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror

            <textarea
                name="mensagem"
                rows="2"
                placeholder="Mensagem (opcional)"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#20b6c7] focus:outline-none"
            >{{ old('mensagem') }}</textarea>

            <button type="submit" class="w-full rounded-lg bg-[#20b6c7] px-4 py-2 text-sm font-bold text-white hover:bg-[#1599a8]">
                Enviar para o SAC
            </button>
        </form>

        <a href="{{ $whatsappLink }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex w-full items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
            Abrir WhatsApp direto
        </a>
    </div>
</div>

<script>
    (function () {
        const toggle = document.getElementById('toggle-whatsapp-widget');
        const panel = document.getElementById('whatsapp-panel');

        if (!toggle || !panel) return;

        toggle.addEventListener('click', function () {
            panel.classList.toggle('hidden');
        });

        if (document.querySelector('#whatsapp-panel .text-red-600') || document.querySelector('#whatsapp-panel .text-emerald-700')) {
            panel.classList.remove('hidden');
        }

        const input = panel.querySelector('[data-whatsapp-mask]');
        if (!input) return;

        input.addEventListener('input', function (event) {
            const digits = event.target.value.replace(/\D/g, '').slice(0, 11);
            if (digits.length <= 10) {
                event.target.value = digits
                    .replace(/(\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{4})(\d)/, '$1-$2');
            } else {
                event.target.value = digits
                    .replace(/(\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{5})(\d)/, '$1-$2');
            }
        });
    })();
</script>

