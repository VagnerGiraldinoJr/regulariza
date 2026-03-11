<?php

use App\Models\Lead;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

new class extends Component
{
    public int $etapa = 1;
    public ?int $order_id = null;
    public string $cpf_cnpj = '';
    public string $whatsapp = '';
    public string $tipo_documento = '';
    public string $payment_method = 'PIX';
    public ?int $service_id = null;
    public ?int $lead_id = null;
    public ?string $protocolo = null;
    public ?int $referred_by_user_id = null;
    public ?string $referred_by_name = null;
    public ?string $referred_by_code = null;
    public array $payment_session = [];
    public array $services = [];

    public function mount(): void
    {
        $this->loadServices();
        $this->resolveReferralFromQuery();

        $this->order_id = request()->integer('order_id') ?: null;

        if ($this->order_id) {
            $this->hydrateFromOrder($this->order_id);
        }
    }

    private function hydrateFromOrder(int $orderId): void
    {
        $order = Order::query()->with(['service', 'lead', 'user'])->find($orderId);

        if (! $order) {
            return;
        }

        $this->order_id = $order->id;
        $this->lead_id = $order->lead_id;
        $this->service_id = $order->service_id;
        $this->protocolo = $order->protocolo;
        $this->cpf_cnpj = (string) ($order->lead?->cpf_cnpj ?: $order->user?->cpf_cnpj ?: '');
        $this->whatsapp = $this->formatWhatsapp((string) ($order->lead?->whatsapp ?: $order->user?->whatsapp ?: ''));
        $this->tipo_documento = (string) ($order->lead?->tipo_documento ?: (strlen(preg_replace('/\D+/', '', $this->cpf_cnpj)) > 11 ? 'cnpj' : 'cpf'));

        if ($order->pagamento_status === 'pago') {
            $this->etapa = 4;

            return;
        }

        $this->etapa = 3;

        try {
            $session = app(CheckoutService::class)->getCheckoutSessionForOrder($order);
            if ($session) {
                $this->payment_session = $session;
                $this->payment_method = $session['billing_type'] === 'BOLETO' || $session['billing_type'] === 'CREDIT_CARD'
                    ? $session['billing_type']
                    : 'PIX';
            }
        } catch (\Throwable) {
            $this->payment_session = [];
        }
    }

    private function resolveReferralFromQuery(): void
    {
        $code = strtoupper(trim((string) request()->query('indicacao', '')));

        $referrer = null;

        if ($code !== '') {
            $referrer = User::query()
                ->whereIn('role', ['cliente', 'analista', 'vendedor'])
                ->where('referral_code', $code)
                ->first();
        }

        if (! $referrer) {
            $defaultAnalystEmail = (string) config('services.sales.default_analyst_email');
            $referrer = User::query()
                ->whereIn('role', ['analista', 'vendedor'])
                ->where('email', $defaultAnalystEmail)
                ->first()
                ?? User::query()
                    ->whereIn('role', ['analista', 'vendedor'])
                    ->orderBy('id')
                    ->first();
        }

        if (! $referrer) {
            return;
        }

        $this->referred_by_user_id = $referrer->id;
        $this->referred_by_name = $referrer->name;
        $this->referred_by_code = $referrer->referral_code;
    }

    private function loadServices(): void
    {
        Service::query()
            ->where('slug', '!=', 'cpf-clean-brasil')
            ->update(['ativo' => false]);

        $existingService = Service::query()->where('slug', 'cpf-clean-brasil')->first();

        $service = Service::query()->updateOrCreate(
            ['slug' => 'cpf-clean-brasil'],
            [
                'nome' => 'pesquisa CPF CLEAN BRASIL',
                'descricao' => 'Diagnóstico consultivo do CPF ou CNPJ com análise especializada e plano de direcionamento.',
                'icone' => 'cpf clean',
                'preco' => (float) ($existingService?->preco ?? 200.00),
                'ativo' => true,
            ]
        );

        $this->services = [$service->toArray()];
    }

    public function getSelectedServiceProperty(): ?array
    {
        if (! $this->service_id) {
            return null;
        }

        foreach ($this->services as $service) {
            if ((int) $service['id'] === $this->service_id) {
                return $service;
            }
        }

        return null;
    }

    public function getHasActivePaymentSessionProperty(): bool
    {
        return $this->order_id !== null
            && $this->payment_session !== []
            && in_array((string) ($this->payment_session['status'] ?? ''), ['PENDING', 'RECEIVED_IN_CASH', 'CONFIRMED', 'OVERDUE', 'RECEIVED', 'AWAITING_PAYMENT', 'aguardando'], true);
    }

    public function updatedCpfCnpj(string $value): void
    {
        $digits = preg_replace('/\D+/', '', $value);
        $this->tipo_documento = strlen($digits) > 11 ? 'cnpj' : 'cpf';
    }

    public function avancarIdentificacao(): void
    {
        $digits = preg_replace('/\D+/', '', $this->cpf_cnpj);
        $whatsappDigits = $this->normalizeWhatsapp($this->whatsapp);
        $tipo = strlen($digits) === 14 ? 'cnpj' : 'cpf';

        Validator::make(
            ['cpf_cnpj' => $digits, 'tipo_documento' => $tipo, 'whatsapp' => $whatsappDigits],
            [
                'cpf_cnpj' => ['required', 'string'],
                'tipo_documento' => ['required', 'in:cpf,cnpj'],
                'whatsapp' => ['required', 'string', 'digits_between:10,11'],
            ]
        )->validate([], [
            'whatsapp.required' => 'Informe seu celular com DDD.',
            'whatsapp.digits_between' => 'Informe um celular válido com DDD.',
        ]);

        if (($tipo === 'cpf' && ! $this->isValidCpf($digits)) || ($tipo === 'cnpj' && ! $this->isValidCnpj($digits))) {
            $this->addError('cpf_cnpj', 'Documento inválido.');

            return;
        }

        $lead = Lead::query()->updateOrCreate(
            [
                'cpf_cnpj' => $digits,
                'session_id' => session()->getId(),
            ],
            [
                'tipo_documento' => $tipo,
                'whatsapp' => $whatsappDigits,
                'etapa' => 'identificacao',
                'referred_by_user_id' => $this->referred_by_user_id,
            ]
        );

        $this->lead_id = $lead->id;
        $this->cpf_cnpj = $digits;
        $this->whatsapp = $this->formatWhatsapp($whatsappDigits);
        $this->tipo_documento = $tipo;
        $this->etapa = 2;
    }

    public function selecionarServico(int $serviceId): void
    {
        $service = Service::query()->where('ativo', true)->findOrFail($serviceId);

        if (! $this->lead_id) {
            $this->addError('service_id', 'Cadastro não encontrado. Volte para a etapa 1.');

            return;
        }

        Lead::query()->whereKey($this->lead_id)->update([
            'service_id' => $service->id,
            'etapa' => 'servico',
        ]);

        $this->service_id = $service->id;
        $this->etapa = 3;
    }

    public function iniciarPagamento(CheckoutService $checkoutService)
    {
        if ((! $this->lead_id && ! $this->order_id) || ! $this->service_id) {
            $this->addError('service_id', 'Selecione a pesquisa para continuar.');

            return null;
        }

        try {
            if ($this->order_id) {
                $order = Order::query()->with(['service', 'lead', 'user'])->findOrFail($this->order_id);

                if ($order->pagamento_status === 'pago') {
                    $this->hydrateFromOrder($order->id);

                    return $this->redirect(route('regularizacao.sucesso', ['order_id' => $order->id]), navigate: false);
                }

                $existingSession = $checkoutService->getCheckoutSessionForOrder($order);

                if ($existingSession && $order->pagamento_status !== 'falhou') {
                    $this->payment_session = $existingSession;
                    $this->payment_method = $existingSession['billing_type'] === 'BOLETO' || $existingSession['billing_type'] === 'CREDIT_CARD'
                        ? $existingSession['billing_type']
                        : 'PIX';

                    return null;
                }

                $this->payment_session = $checkoutService->createCheckoutSessionForOrder($order, $this->payment_method);
            } else {
                $lead = Lead::findOrFail($this->lead_id);
                $service = Service::query()->where('ativo', true)->findOrFail($this->service_id);
                $this->payment_session = $checkoutService->createCheckoutSession($lead, $service, $this->payment_method);
                $this->order_id = (int) ($this->payment_session['order_id'] ?? 0) ?: null;
            }
        } catch (\RuntimeException $exception) {
            $this->addError('service_id', $exception->getMessage());

            return null;
        }

        return null;
    }

    public function sincronizarPagamentoPix(CheckoutService $checkoutService): void
    {
        if (! $this->order_id || $this->etapa !== 3) {
            return;
        }

        if (($this->payment_session['billing_type'] ?? null) !== 'PIX') {
            return;
        }

        $order = Order::query()->with(['service', 'lead', 'user'])->find($this->order_id);

        if (! $order) {
            return;
        }

        if ($order->pagamento_status === 'pago') {
            $this->hydrateFromOrder($order->id);

            $this->redirect(route('regularizacao.sucesso', ['order_id' => $order->id]), navigate: false);

            return;
        }

        try {
            $session = app(CheckoutService::class)->getCheckoutSessionForOrder($order);

            $order->refresh();

            if ($order->pagamento_status === 'pago') {
                $this->hydrateFromOrder($order->id);

                return;
            }

            if ($session) {
                $this->payment_session = $session;
            }
        } catch (\Throwable) {
            // Mantém a cobrança visível mesmo se uma verificação pontual falhar.
        }
    }

    protected function isValidCpf(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cpf[$i] * (($t + 1) - $i);
            }

            $digit = ((10 * $sum) % 11) % 10;

            if ((int) $cpf[$t] !== $digit) {
                return false;
            }
        }

        return true;
    }

    protected function isValidCnpj(string $cnpj): bool
    {
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $sum1 = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum1 += ((int) $cnpj[$i]) * $weights1[$i];
        }

        $digit1 = $sum1 % 11 < 2 ? 0 : 11 - ($sum1 % 11);

        $sum2 = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum2 += ((int) $cnpj[$i]) * $weights2[$i];
        }

        $digit2 = $sum2 % 11 < 2 ? 0 : 11 - ($sum2 % 11);

        return (int) $cnpj[12] === $digit1 && (int) $cnpj[13] === $digit2;
    }

    protected function normalizeWhatsapp(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (str_starts_with($digits, '55') && strlen($digits) > 11) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    protected function formatWhatsapp(string $digits): string
    {
        if (strlen($digits) === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $digits) ?? $digits;
        }

        if (strlen($digits) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $digits) ?? $digits;
        }

        return $digits;
    }
};
?>

<div
    class="mx-auto max-w-6xl px-4 py-8"
    x-data="{
        masked: @entangle('cpf_cnpj'),
        whatsappMasked: @entangle('whatsapp'),
        formatDocument(value) {
            const digits = value.replace(/\D/g, '').slice(0, 14);
            if (digits.length <= 11) {
                return digits
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            }

            return digits
                .replace(/(\d{2})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1/$2')
                .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
        },
        formatWhatsapp(value) {
            const digits = value.replace(/\D/g, '').slice(0, 11);
            if (digits.length <= 10) {
                return digits
                    .replace(/(\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{4})(\d)/, '$1-$2');
            }

            return digits
                .replace(/(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{5})(\d)/, '$1-$2');
        }
    }"
>
    <div class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
        <section class="panel-card overflow-hidden">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h1 class="text-lg font-black text-slate-800">Regularização CPF/CNPJ</h1>
                        <p class="mt-1 text-sm text-slate-500">Fluxo guiado para contratação da pesquisa e envio seguro dos dados.</p>
                    </div>
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-blue-700">
                        Etapa {{ $etapa }} / 4
                    </span>
                </div>

                <div class="mt-4">
                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full rounded-full bg-blue-500 transition-all duration-300" style="width: {{ ($etapa / 4) * 100 }}%"></div>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                        <span>Identificação</span>
                        <span>Pesquisa</span>
                        <span>Pagamento</span>
                        <span>Sucesso</span>
                    </div>
                </div>
            </div>

            <div class="p-5 sm:p-6">
                @if ($etapa === 1)
                    <div class="space-y-5">
                        @if ($referred_by_user_id && $referred_by_name)
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                                Indicado por <strong>{{ $referred_by_name }}</strong>
                                @if ($referred_by_code)
                                    <span class="font-semibold">({{ $referred_by_code }})</span>
                                @endif
                            </div>
                        @endif

                        <div>
                            <h2 class="text-base font-bold text-slate-800">1. Identificação do documento</h2>
                            <p class="mt-1 text-sm text-slate-500">Digite CPF ou CNPJ para iniciarmos sua análise consultiva. A validação é automática.</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">CPF/CNPJ</label>
                            <input
                                type="text"
                                x-model="masked"
                                @input="masked = formatDocument($event.target.value)"
                                wire:model.live="cpf_cnpj"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none"
                                placeholder="000.000.000-00"
                            />
                            @error('cpf_cnpj')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Celular (WhatsApp)</label>
                            <input
                                type="tel"
                                x-model="whatsappMasked"
                                @input="whatsappMasked = formatWhatsapp($event.target.value)"
                                wire:model.live="whatsapp"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none"
                                placeholder="(11) 99999-9999"
                                maxlength="15"
                                required
                            />
                            @error('whatsapp')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <button wire:click="avancarIdentificacao" class="btn-primary w-full">Continuar para pesquisa</button>
                    </div>
                @endif

                @if ($etapa === 2)
                    <div class="space-y-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">2. Contratação da pesquisa</h2>
                            <p class="mt-1 text-sm text-slate-500">Selecione a pesquisa para análise do seu caso e direcionamento estratégico.</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($services as $service)
                                <button
                                    wire:click="selecionarServico({{ $service['id'] }})"
                                    class="rounded-xl border p-4 text-left transition {{ $service_id === $service['id'] ? 'border-blue-500 bg-blue-50 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300' }}"
                                >
                                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">{{ $service['icone'] ?: 'serviço' }}</p>
                                    <h3 class="mt-1 text-sm font-bold text-slate-800">{{ $service['nome'] }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">{{ $service['descricao'] }}</p>
                                    <p class="mt-3 text-sm font-extrabold text-slate-800">Pagamento da pesquisa: R$ {{ number_format((float) $service['preco'], 2, ',', '.') }}</p>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($etapa === 3)
                    <div class="space-y-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">3. Confirmar pagamento da pesquisa</h2>
                            <p class="mt-1 text-sm text-slate-500">Escolha como deseja pagar a pesquisa. O pagamento é gerado pelo Asaas no mesmo fluxo.</p>
                        </div>

                        @if ($this->selectedService)
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">pesquisa selecionada</p>
                                <p class="mt-1 text-sm font-bold text-slate-800">{{ $this->selectedService['nome'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $this->selectedService['descricao'] }}</p>
                                <p class="mt-3 text-base font-extrabold text-slate-900">Pagamento da pesquisa: R$ {{ number_format((float) $this->selectedService['preco'], 2, ',', '.') }}</p>
                            </div>
                        @endif

                        <div class="grid gap-3 sm:grid-cols-3">
                            @foreach ([
                                'PIX' => ['title' => 'PIX', 'copy' => 'QRCode e copia e cola', 'icon_bg' => 'bg-emerald-100', 'icon_text' => 'text-emerald-700'],
                                'BOLETO' => ['title' => 'Boleto', 'copy' => 'Boleto Asaas para pagamento', 'icon_bg' => 'bg-amber-100', 'icon_text' => 'text-amber-700'],
                                'CREDIT_CARD' => ['title' => 'Cartão', 'copy' => 'Pagamento com cartão no Asaas', 'icon_bg' => 'bg-sky-100', 'icon_text' => 'text-sky-700'],
                            ] as $method => $meta)
                                <button
                                    type="button"
                                    wire:click="$set('payment_method', '{{ $method }}')"
                                    @disabled($this->hasActivePaymentSession)
                                    class="rounded-xl border p-4 text-left transition disabled:cursor-not-allowed disabled:opacity-60 {{ $payment_method === $method ? 'border-cyan-500 bg-cyan-50 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300' }}"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-cyan-700">{{ $meta['title'] }}</p>
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl {{ $meta['icon_bg'] }} {{ $meta['icon_text'] }}">
                                            @if ($method === 'PIX')
                                                <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" aria-hidden="true">
                                                    <path d="M8.5 5.5 12 9l3.5-3.5a2.6 2.6 0 0 1 3.68 0l1.32 1.32a2.6 2.6 0 0 1 0 3.68L17 14l3.5 3.5a2.6 2.6 0 0 1 0 3.68l-1.32 1.32a2.6 2.6 0 0 1-3.68 0L12 19l-3.5 3.5a2.6 2.6 0 0 1-3.68 0L3.5 21.18a2.6 2.6 0 0 1 0-3.68L7 14l-3.5-3.5a2.6 2.6 0 0 1 0-3.68L4.82 5.5a2.6 2.6 0 0 1 3.68 0Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                                    <path d="M9.7 12 12 14.3 14.3 12 12 9.7 9.7 12Z" fill="currentColor"/>
                                                </svg>
                                            @elseif ($method === 'BOLETO')
                                                <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" aria-hidden="true">
                                                    <rect x="4" y="5" width="16" height="14" rx="2.5" stroke="currentColor" stroke-width="1.7"/>
                                                    <path d="M7.5 8v8M10.5 8v8M14.5 8v8M17 8v8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                                </svg>
                                            @else
                                                <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" aria-hidden="true">
                                                    <rect x="3.5" y="6" width="17" height="12" rx="2.5" stroke="currentColor" stroke-width="1.7"/>
                                                    <path d="M3.5 10.5h17" stroke="currentColor" stroke-width="1.7"/>
                                                    <path d="M7.5 15h3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                                </svg>
                                            @endif
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm font-bold text-slate-800">{{ $meta['copy'] }}</p>
                                </button>
                            @endforeach
                        </div>

                        @error('service_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

                        @if (! $this->hasActivePaymentSession)
                            <button wire:click="iniciarPagamento" class="btn-primary w-full">
                                {{ $order_id ? 'Atualizar cobrança no Asaas' : 'Gerar cobrança no Asaas' }}
                            </button>
                        @else
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                Ja existe uma cobranca ativa para este pedido. Aguarde a compensacao ou use os atalhos abaixo sem gerar uma nova cobranca.
                            </div>
                        @endif

                        @if (! empty($payment_session))
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cobrança ativa</p>
                                        <p class="mt-1 text-sm font-bold text-slate-800">{{ $payment_session['billing_type'] === 'CREDIT_CARD' ? 'Cartão de crédito' : ucfirst(mb_strtolower(str_replace('_', ' ', $payment_session['billing_type']))) }}</p>
                                    </div>
                                    @if (! empty($payment_session['due_date']))
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">Vencimento {{ \Carbon\Carbon::parse($payment_session['due_date'])->format('d/m/Y') }}</span>
                                    @endif
                                </div>

                                @if (($payment_session['billing_type'] ?? '') === 'PIX' && ! empty($payment_session['pix']))
                                    <div wire:poll.5s="sincronizarPagamentoPix" class="mt-4 space-y-4">
                                        <div class="rounded-2xl border border-cyan-200 bg-cyan-50/80 p-4">
                                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                                <div>
                                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-cyan-700">Aguardando pagamento Pix</p>
                                                    <p class="mt-1 text-sm text-slate-700">Estamos verificando automaticamente o recebimento a cada 5 segundos. Assim que o Pix cair, esta tela avança sozinha.</p>
                                                </div>
                                                <div class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-2 text-xs font-semibold text-cyan-700 shadow-sm">
                                                    <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-cyan-500"></span>
                                                    Monitoramento automatico ativo
                                                </div>
                                            </div>
                                            <div wire:loading.flex wire:target="sincronizarPagamentoPix" class="mt-3 hidden items-center gap-2 text-xs font-semibold text-cyan-800">
                                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.2" stroke-width="3"></circle>
                                                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                                </svg>
                                                Verificando pagamento Pix...
                                            </div>
                                        </div>

                                        <div class="grid gap-4 lg:grid-cols-[220px_1fr]">
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                            @if (! empty($payment_session['pix']['encoded_image']))
                                                <img src="data:image/png;base64,{{ $payment_session['pix']['encoded_image'] }}" alt="QR Code Pix" class="mx-auto h-48 w-48 rounded-lg border border-slate-200 bg-white p-2" />
                                            @endif
                                        </div>
                                        <div class="space-y-3">
                                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pix copia e cola</p>
                                                <textarea readonly rows="5" class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-700">{{ $payment_session['pix']['payload'] ?? '' }}</textarea>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                @if (! empty($payment_session['payment_url']))
                                                    <a href="{{ $payment_session['payment_url'] }}" target="_blank" rel="noopener noreferrer" class="btn-dark">Abrir fatura Asaas</a>
                                                @endif
                                                <button type="button" wire:click="sincronizarPagamentoPix" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                                    Atualizar status
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @elseif (($payment_session['billing_type'] ?? '') === 'BOLETO')
                                    <div class="mt-4 space-y-3">
                                        <p class="text-sm text-slate-600">O boleto foi gerado. Abra o documento do Asaas para visualizar ou baixar.</p>
                                        <div class="flex flex-wrap gap-2">
                                            @if (! empty($payment_session['bank_slip_url']))
                                                <a href="{{ $payment_session['bank_slip_url'] }}" target="_blank" rel="noopener noreferrer" class="btn-dark">Abrir boleto</a>
                                            @endif
                                            @if (! empty($payment_session['invoice_url']))
                                                <a href="{{ $payment_session['invoice_url'] }}" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Abrir fatura</a>
                                            @endif
                                        </div>
                                    </div>
                                @elseif (($payment_session['billing_type'] ?? '') === 'CREDIT_CARD')
                                    <div class="mt-4 space-y-3">
                                        <p class="text-sm text-slate-600">O pagamento com cartão é concluído na página segura do Asaas.</p>
                                        <div class="flex flex-wrap gap-2">
                                            @if (! empty($payment_session['payment_url']))
                                                <a href="{{ $payment_session['payment_url'] }}" target="_blank" rel="noopener noreferrer" class="btn-dark">Pagar com cartão</a>
                                            @endif
                                            <a href="{{ route('regularizacao.index', ['order_id' => $payment_session['order_id']]) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Atualizar status</a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                @if ($etapa === 4)
                    <div class="space-y-4">
                        <div class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-700">
                            Pagamento confirmado
                        </div>

                        <div>
                            <h2 class="text-base font-bold text-slate-800">4. Pedido finalizado</h2>
                            <p class="mt-1 text-sm text-slate-500">Seu protocolo foi gerado com sucesso.</p>
                            <p class="mt-1 text-sm text-slate-500">Um dos nossos analistas entrará em contato no numero WhatsApp que você informou em instantes.</p>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Protocolo</p>
                            <p class="mt-1 text-lg font-black text-slate-900">{{ $protocolo }}</p>
                        </div>

                        <a href="{{ \Illuminate\Support\Facades\Route::has('portal.welcome') ? route('portal.welcome') : route('regularizacao.index') }}" class="btn-dark inline-block">Fechar Janela</a>
                    </div>
                @endif

            </div>
        </section>

        <aside class="space-y-4">
            <div class="panel-card p-4">
                <h3 class="text-sm font-bold uppercase tracking-wide text-slate-700">Como funciona</h3>
                <ol class="mt-3 space-y-3 text-sm text-slate-600">
                    <li><span class="font-semibold text-slate-800">1.</span> Valide CPF/CNPJ.</li>
                    <li><span class="font-semibold text-slate-800">2.</span> Contrate a pesquisa de análise.</li>
                    <li><span class="font-semibold text-slate-800">3.</span> Nossa equipe recebe e analisa seu caso.</li>
                    <li><span class="font-semibold text-slate-800">4.</span> Receba protocolo, direcionamento e acompanhamento.</li>
                </ol>
            </div>

            <div class="panel-card p-4">
                <h3 class="text-sm font-bold uppercase tracking-wide text-slate-700">Diferenciais</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li>Análise interna com time especializado.</li>
                    <li>Direcionamento estratégico para pessoa física ou jurídica.</li>
                    <li>Acompanhamento consultivo com protocolo e histórico.</li>
                </ul>
            </div>
        </aside>
    </div>

    <div class="mb-2 mt-5 flex items-center gap-2">
        <span class="h-px flex-1 bg-slate-300"></span>
        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Ambiente seguro</p>
        <span class="h-px flex-1 bg-slate-300"></span>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
            <img src="{{ asset('assets/selos-seguranca/lets-encrypt_.svg') }}" alt="Let's Encrypt" class="h-7 w-full object-contain" />
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
            <img src="{{ asset('assets/selos-seguranca/site-protegido.svg') }}" alt="Site protegido" class="h-7 w-full object-contain" />
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
            <img src="{{ asset('assets/selos-seguranca/site-protegido.svg') }}" alt="Asaas" class="h-7 w-full object-contain" />
        </div>
    </div>
</div>
