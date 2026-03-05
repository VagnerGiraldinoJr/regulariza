@php
    $whatsappNumber = preg_replace('/\D+/', '', (string) config('services.cpfclean.whatsapp_number'));
    $defaultMessage = rawurlencode('Olá! Quero informações sobre limpeza de CPF/CNPJ.');
    $whatsappLink = "https://wa.me/{$whatsappNumber}?text={$defaultMessage}";
    $publicWhatsappErrors = $errors->getBag('publicWhatsapp');
    $hasPublicWhatsappErrors = $publicWhatsappErrors->any();
@endphp

<div class="fixed bottom-5 right-5 z-50" id="public-whatsapp-widget">
    <button
        type="button"
        id="toggle-whatsapp-widget"
        class="whatsapp-trigger inline-flex items-center gap-2 rounded-full border border-cyan-200/60 bg-[#041f37] px-3 py-2 text-white shadow-[0_14px_30px_rgba(1,20,40,0.38)] transition hover:-translate-y-0.5 hover:bg-[#062a49]"
        aria-label="Abrir contato WhatsApp"
    >
        <span class="whatsapp-icon-wrap inline-flex h-10 w-10 items-center justify-center rounded-full bg-white">
            <img src="{{ asset('assets/icons/whatsapp-color-icon.svg') }}" alt="WhatsApp" class="h-6 w-6 object-contain">
        </span>
        <span class="hidden text-left leading-tight sm:block">
            <strong class="block text-xs font-extrabold tracking-wide text-cyan-100">WhatsApp</strong>
            <span class="block text-[11px] text-cyan-200/90">Fale com o SAC</span>
        </span>
    </button>

    <div id="whatsapp-panel" class="mt-3 hidden w-[320px] max-w-[calc(100vw-2.5rem)] rounded-xl border border-[#d1e4d7] bg-white p-4 shadow-2xl" data-has-errors="{{ $hasPublicWhatsappErrors ? '1' : '0' }}">
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

            @if ($publicWhatsappErrors->has('email'))
                <p class="text-xs text-red-600">{{ $publicWhatsappErrors->first('email') }}</p>
            @endif
            @if ($publicWhatsappErrors->has('whatsapp'))
                <p class="text-xs text-red-600">{{ $publicWhatsappErrors->first('whatsapp') }}</p>
            @endif
            @if ($publicWhatsappErrors->has('cnpj'))
                <p class="text-xs text-red-600">{{ $publicWhatsappErrors->first('cnpj') }}</p>
            @endif

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

<style>
    .whatsapp-trigger {
        animation: whatsappPulse 1.8s ease-in-out infinite;
    }

    @keyframes whatsappPulse {
        0% {
            transform: translateY(0);
            box-shadow: 0 0 0 0 rgba(38, 190, 211, 0.35), 0 14px 30px rgba(1, 20, 40, 0.38);
        }
        65% {
            transform: translateY(-1px);
            box-shadow: 0 0 0 12px rgba(38, 190, 211, 0), 0 14px 30px rgba(1, 20, 40, 0.38);
        }
        100% {
            transform: translateY(0);
            box-shadow: 0 0 0 0 rgba(38, 190, 211, 0), 0 14px 30px rgba(1, 20, 40, 0.38);
        }
    }
</style>

<script>
    (function () {
        const toggle = document.getElementById('toggle-whatsapp-widget');
        const panel = document.getElementById('whatsapp-panel');

        if (!toggle || !panel) return;

        toggle.addEventListener('click', function () {
            panel.classList.toggle('hidden');
        });

        if (panel.dataset.hasErrors === '1' || document.querySelector('#whatsapp-panel .text-emerald-700')) {
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
