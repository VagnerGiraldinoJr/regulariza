<?php

use App\Models\Lead;
use App\Models\Order;
use App\Models\Service;
use App\Services\StripeCheckoutService;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

new class extends Component
{
    public int $etapa = 1;
    public string $cpf_cnpj = '';
    public string $tipo_documento = '';
    public ?int $service_id = null;
    public ?int $lead_id = null;
    public ?string $protocolo = null;
    public array $services = [];

    public function mount(): void
    {
        $this->services = Service::query()
            ->where('ativo', true)
            ->orderBy('nome')
            ->get()
            ->toArray();

        $orderId = request()->integer('order_id');

        if ($orderId && request()->routeIs('regularizacao.sucesso')) {
            $order = Order::find($orderId);

            if ($order) {
                $this->etapa = 4;
                $this->protocolo = $order->protocolo;
            }
        }
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

    public function updatedCpfCnpj(string $value): void
    {
        $digits = preg_replace('/\D+/', '', $value);
        $this->tipo_documento = strlen($digits) > 11 ? 'cnpj' : 'cpf';
    }

    public function avancarIdentificacao(): void
    {
        $digits = preg_replace('/\D+/', '', $this->cpf_cnpj);
        $tipo = strlen($digits) === 14 ? 'cnpj' : 'cpf';

        Validator::make(
            ['cpf_cnpj' => $digits, 'tipo_documento' => $tipo],
            [
                'cpf_cnpj' => ['required', 'string'],
                'tipo_documento' => ['required', 'in:cpf,cnpj'],
            ]
        )->validate();

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
                'etapa' => 'identificacao',
            ]
        );

        $this->lead_id = $lead->id;
        $this->cpf_cnpj = $digits;
        $this->tipo_documento = $tipo;
        $this->etapa = 2;
    }

    public function selecionarServico(int $serviceId): void
    {
        $service = Service::query()->where('ativo', true)->findOrFail($serviceId);

        if (! $this->lead_id) {
            $this->addError('service_id', 'Lead não encontrado. Volte para a etapa 1.');

            return;
        }

        Lead::query()->whereKey($this->lead_id)->update([
            'service_id' => $service->id,
            'etapa' => 'servico',
        ]);

        $this->service_id = $service->id;
        $this->etapa = 3;
    }

    public function iniciarPagamento(StripeCheckoutService $stripeCheckoutService)
    {
        if (! $this->lead_id || ! $this->service_id) {
            $this->addError('service_id', 'Selecione um serviço para continuar.');

            return null;
        }

        $lead = Lead::findOrFail($this->lead_id);
        $service = Service::query()->where('ativo', true)->findOrFail($this->service_id);

        try {
            $checkoutUrl = $stripeCheckoutService->createCheckoutSession($lead, $service);
        } catch (\RuntimeException $exception) {
            $this->addError('service_id', $exception->getMessage());

            return null;
        }

        return $this->redirect($checkoutUrl, navigate: false);
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
};
?>

<div
    class="mx-auto max-w-6xl px-4 py-8"
    x-data="{
        masked: @entangle('cpf_cnpj'),
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
        }
    }"
>
    <div class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
        <section class="panel-card overflow-hidden">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h1 class="text-lg font-black text-slate-800">Regularização CPF/CNPJ</h1>
                        <p class="mt-1 text-sm text-slate-500">Fluxo guiado para contratação e pagamento.</p>
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
                        <span>Serviço</span>
                        <span>Pagamento</span>
                        <span>Sucesso</span>
                    </div>
                </div>
            </div>

            <div class="p-5 sm:p-6">
                @if ($etapa === 1)
                    <div class="space-y-5">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">1. Identificação do documento</h2>
                            <p class="mt-1 text-sm text-slate-500">Digite CPF ou CNPJ para continuar. A validação é feita automaticamente.</p>
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

                        <button wire:click="avancarIdentificacao" class="btn-primary w-full">Continuar para serviços</button>
                    </div>
                @endif

                @if ($etapa === 2)
                    <div class="space-y-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">2. Escolha o serviço</h2>
                            <p class="mt-1 text-sm text-slate-500">Selecione o tipo de regularização desejada.</p>
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
                                    <p class="mt-3 text-sm font-extrabold text-slate-800">R$ {{ number_format((float) $service['preco'], 2, ',', '.') }}</p>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($etapa === 3)
                    <div class="space-y-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">3. Confirmar pagamento</h2>
                            <p class="mt-1 text-sm text-slate-500">Você será redirecionado para checkout seguro do Stripe.</p>
                        </div>

                        @if ($this->selectedService)
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Serviço selecionado</p>
                                <p class="mt-1 text-sm font-bold text-slate-800">{{ $this->selectedService['nome'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $this->selectedService['descricao'] }}</p>
                                <p class="mt-3 text-base font-extrabold text-slate-900">R$ {{ number_format((float) $this->selectedService['preco'], 2, ',', '.') }}</p>
                            </div>
                        @endif

                        @error('service_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

                        <button wire:click="iniciarPagamento" class="btn-primary w-full">Ir para pagamento</button>
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
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Protocolo</p>
                            <p class="mt-1 text-lg font-black text-slate-900">{{ $protocolo }}</p>
                        </div>

                        <a href="{{ route('portal.dashboard') }}" class="btn-dark inline-block">Acessar portal do cliente</a>
                    </div>
                @endif

            </div>
        </section>

        <aside class="space-y-4">
            <div class="panel-card p-4">
                <h3 class="text-sm font-bold uppercase tracking-wide text-slate-700">Como funciona</h3>
                <ol class="mt-3 space-y-3 text-sm text-slate-600">
                    <li><span class="font-semibold text-slate-800">1.</span> Valide CPF/CNPJ.</li>
                    <li><span class="font-semibold text-slate-800">2.</span> Escolha o serviço ideal.</li>
                    <li><span class="font-semibold text-slate-800">3.</span> Faça o pagamento com segurança.</li>
                    <li><span class="font-semibold text-slate-800">4.</span> Receba protocolo e acompanhe no portal.</li>
                </ol>
            </div>

            <div class="panel-card p-4">
                <h3 class="text-sm font-bold uppercase tracking-wide text-slate-700">Diferenciais</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li>Atendimento SAC com histórico de mensagens.</li>
                    <li>Protocolo automático para rastreabilidade.</li>
                    <li>Integração com pagamento e notificações.</li>
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
            <img src="{{ asset('assets/selos-seguranca/stripe.svg') }}" alt="Stripe" class="h-7 w-full object-contain" />
        </div>
    </div>
</div>
