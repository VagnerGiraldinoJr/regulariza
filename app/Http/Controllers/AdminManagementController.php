<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Contract;
use App\Models\ContractInstallment;
use App\Models\Order;
use App\Models\SacMessage;
use App\Models\SacTicket;
use App\Models\Service;
use App\Models\SellerCommission;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WhatsappLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminManagementController extends Controller
{
    private const FAKE_ORDER_PREFIX = 'FAKE-ORDER-';

    private const FAKE_CLIENT_EMAIL_DOMAIN = '@cpfclean.fake';

    public function dashboard(): RedirectResponse
    {
        return redirect()->route('admin.orders.index');
    }

    public function contractPayments(Request $request): View
    {
        $pagamento = (string) $request->query('pagamento_status', '');
        $status = (string) $request->query('status', '');

        $orders = Order::query()
            ->with(['user', 'service', 'lead'])
            ->when($pagamento !== '', fn ($q) => $q->where('pagamento_status', $pagamento))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin/management/contract-payments', [
            'orders' => $orders,
            'filters' => compact('pagamento', 'status'),
        ]);
    }

    public function commissions(Request $request): View
    {
        $status = (string) $request->query('status', '');

        $commissions = SellerCommission::query()
            ->with(['seller', 'order.user'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $totals = [
            'pending' => (float) SellerCommission::query()->whereIn('status', ['pending', 'available'])->sum('commission_amount'),
            'paid' => (float) SellerCommission::query()->where('status', 'paid')->sum('commission_amount'),
        ];

        return view('admin/management/commissions', compact('commissions', 'totals', 'status'));
    }

    public function payoutRequests(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $apenasPendentes = (string) $request->query('pendentes', '0') === '1';

        $requests = SellerCommission::query()
            ->with(['seller', 'order.user'])
            ->whereNotNull('payout_requested_at')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($apenasPendentes, fn ($q) => $q->where('status', 'available'))
            ->latest('payout_requested_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin/management/payout-requests', [
            'requests' => $requests,
            'status' => $status,
            'apenasPendentes' => $apenasPendentes,
        ]);
    }

    public function integrations(): View
    {
        $integrations = [
            'asaas' => [
                'enabled' => filled(config('services.asaas.api_key')),
                'base_url' => (string) config('services.asaas.base_url'),
                'api_key' => (string) config('services.asaas.api_key'),
                'webhook_token' => (string) config('services.asaas.webhook_token'),
                'webhook' => route('api.asaas.webhook'),
            ],
            'apibrasil' => [
                'enabled' => filled(config('services.apibrasil.token')),
                'base_url' => (string) config('services.apibrasil.base_url'),
                'token' => (string) config('services.apibrasil.token'),
                'token_header' => (string) config('services.apibrasil.token_header'),
                'token_prefix' => (string) config('services.apibrasil.token_prefix'),
                'homolog' => (bool) config('services.apibrasil.homolog', false),
                'balance_path' => (string) config('services.apibrasil.balance_path'),
                'balance_method' => (string) config('services.apibrasil.balance_method'),
                'cpf_path' => (string) config('services.apibrasil.cpf_path'),
                'cnpj_path' => (string) config('services.apibrasil.cnpj_path'),
                'cpf_method' => (string) config('services.apibrasil.cpf_method'),
                'cnpj_method' => (string) config('services.apibrasil.cnpj_method'),
            ],
            'zapi' => [
                'enabled' => filled(config('zapi.instance')) && filled(config('zapi.token')) && filled(config('zapi.client_token')),
                'instance' => (string) config('zapi.instance'),
                'token' => (string) config('zapi.token'),
                'client_token' => (string) config('zapi.client_token'),
                'whatsapp_number' => (string) config('services.cpfclean.whatsapp_number'),
            ],
        ];

        return view('admin/management/integrations', compact('integrations'));
    }

    public function updateIntegrations(Request $request): RedirectResponse
    {
        $group = (string) $request->validate([
            'integration_group' => ['required', Rule::in(['asaas', 'apibrasil', 'zapi'])],
        ])['integration_group'];

        $map = match ($group) {
            'asaas' => $this->asaasIntegrationMap($request),
            'apibrasil' => $this->apiBrasilIntegrationMap($request),
            'zapi' => $this->zApiIntegrationMap($request),
        };

        foreach ($map as $key => $value) {
            SystemSetting::setValue($key, $value !== '' ? $value : null);
        }

        Log::info('Integração administrativa atualizada.', [
            'group' => $group,
            'keys' => array_keys($map),
            'admin_user_id' => (int) $request->user()->id,
        ]);

        SystemSetting::applyRuntimeConfig();
        Cache::forget('apibrasil.balance.snapshot');

        return back()->with('success', 'Integração atualizada com sucesso.');
    }

    private function asaasIntegrationMap(Request $request): array
    {
        $data = $request->validate([
            'asaas_base_url' => ['required', 'url', 'max:255'],
            'asaas_api_key' => ['nullable', 'string', 'max:255'],
            'asaas_webhook_token' => ['nullable', 'string', 'max:255'],
        ]);

        $currentApiKey = (string) SystemSetting::getValue('asaas.api_key', (string) config('services.asaas.api_key'));
        $currentWebhookToken = (string) SystemSetting::getValue('asaas.webhook_token', (string) config('services.asaas.webhook_token'));

        return [
            'asaas.base_url' => trim((string) $data['asaas_base_url']),
            'asaas.api_key' => trim((string) ($data['asaas_api_key'] ?? '')) !== '' ? trim((string) $data['asaas_api_key']) : $currentApiKey,
            'asaas.webhook_token' => trim((string) ($data['asaas_webhook_token'] ?? '')) !== '' ? trim((string) $data['asaas_webhook_token']) : $currentWebhookToken,
        ];
    }

    private function apiBrasilIntegrationMap(Request $request): array
    {
        $data = $request->validate([
            'apibrasil_base_url' => ['required', 'url', 'max:255'],
            'apibrasil_token' => ['nullable', 'string', 'max:4096'],
            'apibrasil_token_header' => ['required', 'string', 'max:60'],
            'apibrasil_token_prefix' => ['nullable', 'string', 'max:30'],
            'apibrasil_homolog' => ['nullable', 'boolean'],
            'apibrasil_balance_path' => ['required', 'string', 'max:255'],
            'apibrasil_balance_method' => ['required', Rule::in(['GET', 'POST', 'PUT'])],
            'apibrasil_cpf_path' => ['required', 'string', 'max:255'],
            'apibrasil_cnpj_path' => ['required', 'string', 'max:255'],
            'apibrasil_cpf_method' => ['required', Rule::in(['GET', 'POST', 'PUT'])],
            'apibrasil_cnpj_method' => ['required', Rule::in(['GET', 'POST', 'PUT'])],
        ]);

        $currentToken = (string) SystemSetting::getValue('apibrasil.token', (string) config('services.apibrasil.token'));

        return [
            'apibrasil.base_url' => trim((string) $data['apibrasil_base_url']),
            'apibrasil.token' => trim((string) ($data['apibrasil_token'] ?? '')) !== '' ? trim((string) $data['apibrasil_token']) : $currentToken,
            'apibrasil.token_header' => trim((string) $data['apibrasil_token_header']),
            'apibrasil.token_prefix' => trim((string) ($data['apibrasil_token_prefix'] ?? '')),
            'apibrasil.homolog' => ! empty($data['apibrasil_homolog']) ? '1' : '0',
            'apibrasil.balance_path' => trim((string) $data['apibrasil_balance_path']),
            'apibrasil.balance_method' => trim((string) $data['apibrasil_balance_method']),
            'apibrasil.cpf_path' => trim((string) $data['apibrasil_cpf_path']),
            'apibrasil.cnpj_path' => trim((string) $data['apibrasil_cnpj_path']),
            'apibrasil.cpf_method' => trim((string) $data['apibrasil_cpf_method']),
            'apibrasil.cnpj_method' => trim((string) $data['apibrasil_cnpj_method']),
        ];
    }

    private function zApiIntegrationMap(Request $request): array
    {
        $data = $request->validate([
            'zapi_instance' => ['nullable', 'string', 'max:255'],
            'zapi_token' => ['nullable', 'string', 'max:255'],
            'zapi_client_token' => ['nullable', 'string', 'max:255'],
            'cpfclean_whatsapp_number' => ['nullable', 'string', 'max:25'],
        ]);

        $currentToken = (string) SystemSetting::getValue('zapi.token', (string) config('zapi.token'));
        $currentClientToken = (string) SystemSetting::getValue('zapi.client_token', (string) config('zapi.client_token'));

        return [
            'zapi.instance' => trim((string) ($data['zapi_instance'] ?? '')),
            'zapi.token' => trim((string) ($data['zapi_token'] ?? '')) !== '' ? trim((string) $data['zapi_token']) : $currentToken,
            'zapi.client_token' => trim((string) ($data['zapi_client_token'] ?? '')) !== '' ? trim((string) $data['zapi_client_token']) : $currentClientToken,
            'cpfclean.whatsapp_number' => preg_replace('/\D+/', '', (string) ($data['cpfclean_whatsapp_number'] ?? '')),
        ];
    }

    public function messages(Request $request): View
    {
        $status = (string) $request->query('status', '');

        $messages = WhatsappLog::query()
            ->with(['user', 'order'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin/management/messages', compact('messages', 'status'));
    }

    public function orphanLeads(): View
    {
        $leads = Lead::query()
            ->whereNull('referred_by_user_id')
            ->latest('id')
            ->paginate(20);

        $sellers = User::query()
            ->whereIn('role', ['analista', 'vendedor'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'referral_code']);

        return view('admin/management/orphan-leads', compact('leads', 'sellers'));
    }

    public function assignLead(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'seller_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        $seller = User::query()->findOrFail((int) $data['seller_id']);

        if (! in_array($seller->role, ['analista', 'vendedor'], true)) {
            return back()->withErrors(['seller_id' => 'Selecione um analista/vendedor válido.']);
        }

        $lead->update(['referred_by_user_id' => $seller->id]);

        return back()->with('success', 'Lead vinculado com sucesso.');
    }

    public function users(): View
    {
        $users = User::query()
            ->whereIn('role', ['admin', 'atendente', 'analista', 'vendedor'])
            ->latest('id')
            ->paginate(20);

        return view('admin/management/users', compact('users'));
    }

    public function vendors(): View
    {
        $vendors = User::query()
            ->whereIn('role', ['analista', 'vendedor'])
            ->latest('id')
            ->paginate(20);

        return view('admin/management/vendors', compact('vendors'));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['admin', 'atendente', 'analista', 'vendedor'])],
            'cpf_cnpj' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'pix_key' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => mb_strtolower(trim($data['email'])),
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'cpf_cnpj' => $data['cpf_cnpj'] ?: null,
            'whatsapp' => preg_replace('/\D+/', '', (string) ($data['whatsapp'] ?? '')),
            'pix_key' => $data['pix_key'] ?: null,
        ]);

        if (in_array($user->role, ['analista', 'vendedor'], true)) {
            $status = Password::sendResetLink(['email' => (string) $user->email]);

            if ($status === Password::RESET_LINK_SENT) {
                return back()->with('success', "Usuário criado e e-mail de redefinição enviado para {$user->email}.");
            }

            return back()->with('success', 'Usuário criado com sucesso.')
                ->withErrors(['reset_link' => "Usuário criado, mas não foi possível enviar reset agora para {$user->email}."]);
        }

        return back()->with('success', 'Usuário criado com sucesso.');
    }

    public function storeVendor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'role' => ['required', Rule::in(['analista', 'vendedor'])],
            'cpf_cnpj' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'pix_key' => ['nullable', 'string', 'max:120'],
            'pix_key_type' => ['nullable', Rule::in(['cpf', 'cnpj', 'email', 'telefone', 'aleatoria'])],
            'pix_holder_name' => ['nullable', 'string', 'max:120'],
            'pix_holder_document' => ['nullable', 'string', 'max:20'],
        ]);

        $vendor = User::create([
            'name' => $data['name'],
            'email' => mb_strtolower(trim($data['email'])),
            'password' => Hash::make(str()->random(24)),
            'role' => $data['role'],
            'cpf_cnpj' => $data['cpf_cnpj'] ?: null,
            'whatsapp' => preg_replace('/\D+/', '', (string) ($data['whatsapp'] ?? '')),
            'pix_key' => $data['pix_key'] ?: null,
            'pix_key_type' => $data['pix_key_type'] ?: null,
            'pix_holder_name' => $data['pix_holder_name'] ?: null,
            'pix_holder_document' => isset($data['pix_holder_document'])
                ? preg_replace('/\D+/', '', (string) $data['pix_holder_document'])
                : null,
        ]);

        $status = Password::sendResetLink(['email' => (string) $vendor->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $response = back()->with('success', "Vendedor criado e e-mail de definição de senha enviado para {$vendor->email}.");

            if (app()->isLocal()) {
                $response->with('reset_preview', [
                    'email' => (string) $vendor->email,
                    'url' => $this->buildResetUrl($vendor),
                ]);
            }

            return $response;
        }

        return back()->with('success', 'Vendedor criado com sucesso.')
            ->withErrors(['reset_link' => "Vendedor criado, mas não foi possível enviar reset agora para {$vendor->email}."]);
    }

    public function sendResetLink(User $user): RedirectResponse
    {
        $status = Password::sendResetLink(['email' => (string) $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $response = back()->with('success', "Link de redefinição enviado para {$user->email}.");

            if (app()->isLocal()) {
                $response->with('reset_preview', [
                    'email' => (string) $user->email,
                    'url' => $this->buildResetUrl($user),
                ]);
            }

            return $response;
        }

        return back()->withErrors([
            'reset_link' => "Não foi possível enviar o e-mail de redefinição para {$user->email}.",
        ]);
    }

    private function buildResetUrl(User $user): string
    {
        $token = Password::broker()->createToken($user);

        return URL::route('password.reset', [
            'token' => $token,
            'email' => (string) $user->email,
        ]);
    }

    public function clients(): View
    {
        $clients = User::query()
            ->where('role', 'cliente')
            ->withCount(['orders', 'sacTickets'])
            ->latest('id')
            ->paginate(20);

        $analysts = User::query()
            ->whereIn('role', ['analista', 'vendedor'])
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin/management/clients', compact('clients', 'analysts'));
    }

    public function storeClient(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'cpf_cnpj' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'referred_by_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => mb_strtolower(trim($data['email'])),
            'password' => Hash::make($data['password']),
            'role' => 'cliente',
            'cpf_cnpj' => $data['cpf_cnpj'] ?: null,
            'whatsapp' => preg_replace('/\D+/', '', (string) $data['whatsapp']),
            'referred_by_user_id' => $data['referred_by_user_id'] ?: null,
        ]);

        return back()->with('success', 'Cliente cadastrado com sucesso.');
    }

    public function clientHistory(User $user): View
    {
        abort_unless($user->role === 'cliente', 404);

        $orders = $user->orders()->latest()->get(['id', 'protocolo', 'status', 'pagamento_status', 'created_at']);
        $tickets = $user->sacTickets()->latest()->get(['id', 'protocolo', 'assunto', 'status', 'created_at']);
        $messages = SacMessage::query()
            ->whereHas('ticket', fn ($q) => $q->where('user_id', $user->id))
            ->latest('id')
            ->limit(50)
            ->get(['id', 'mensagem', 'created_at']);
        $whatsapp = WhatsappLog::query()->where('user_id', $user->id)->latest('id')->get(['id', 'evento', 'status', 'created_at']);

        $events = collect();

        foreach ($orders as $order) {
            $events->push([
                'at' => $order->created_at,
                'type' => 'Pedido',
                'description' => "{$order->protocolo} | {$order->status} | pagamento {$order->pagamento_status}",
            ]);
        }

        foreach ($tickets as $ticket) {
            $events->push([
                'at' => $ticket->created_at,
                'type' => 'Ticket',
                'description' => "{$ticket->protocolo} | {$ticket->assunto} | {$ticket->status}",
            ]);
        }

        foreach ($messages as $message) {
            $events->push([
                'at' => $message->created_at,
                'type' => 'Mensagem',
                'description' => mb_strimwidth((string) $message->mensagem, 0, 100, '...'),
            ]);
        }

        foreach ($whatsapp as $log) {
            $events->push([
                'at' => $log->created_at,
                'type' => 'WhatsApp',
                'description' => "{$log->evento} | {$log->status}",
            ]);
        }

        $events = $events->sortByDesc('at')->values();

        return view('admin/management/client-history', [
            'client' => $user,
            'events' => $events,
        ]);
    }

    public function generateFakeData(): RedirectResponse
    {
        $analyst = User::query()
            ->whereIn('role', ['analista', 'vendedor'])
            ->orderByRaw("role = 'analista' desc")
            ->orderBy('id')
            ->first();

        $service = Service::query()->orderBy('id')->first();

        if (! $analyst || ! $service) {
            return back()->withErrors([
                'fake_data' => 'Cadastre ao menos 1 analista/vendedor e 1 serviço antes de gerar dados fake.',
            ]);
        }

        $batch = now()->format('YmdHis');

        DB::transaction(function () use ($analyst, $service, $batch): void {
            for ($i = 1; $i <= 12; $i++) {
                $suffix = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                $email = "fake.cliente.{$batch}.{$suffix}".self::FAKE_CLIENT_EMAIL_DOMAIN;

                $client = User::query()->create([
                    'name' => "[FAKE] Cliente {$suffix}",
                    'email' => $email,
                    'password' => Hash::make('Cliente@123'),
                    'role' => 'cliente',
                    'cpf_cnpj' => '9'.str_pad((string) random_int(100000000, 999999999), 11, '0', STR_PAD_LEFT),
                    'whatsapp' => '1199'.str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                    'referred_by_user_id' => $analyst->id,
                ]);

                $lead = Lead::query()->create([
                    'cpf_cnpj' => (string) $client->cpf_cnpj,
                    'tipo_documento' => strlen((string) $client->cpf_cnpj) > 11 ? 'cnpj' : 'cpf',
                    'nome' => $client->name,
                    'email' => $client->email,
                    'whatsapp' => (string) $client->whatsapp,
                    'service_id' => $service->id,
                    'etapa' => 'concluido',
                    'session_id' => "fake-session-{$batch}-{$suffix}",
                    'referred_by_user_id' => $analyst->id,
                ]);

                $isPaid = $i % 3 !== 0;
                $order = Order::query()->create([
                    'protocolo' => self::FAKE_ORDER_PREFIX.$batch.'-'.$suffix,
                    'user_id' => $client->id,
                    'service_id' => $service->id,
                    'lead_id' => $lead->id,
                    'status' => $isPaid ? ($i % 2 === 0 ? 'concluido' : 'em_andamento') : 'pendente',
                    'valor' => (float) $service->preco,
                    'payment_provider' => 'asaas',
                    'pagamento_status' => $isPaid ? 'pago' : 'aguardando',
                    'pago_em' => $isPaid ? now()->subHours($i * 3) : null,
                ]);

                WhatsappLog::query()->create([
                    'user_id' => $client->id,
                    'order_id' => $order->id,
                    'telefone' => '55'.preg_replace('/\D+/', '', (string) $client->whatsapp),
                    'evento' => 'status_atualizado',
                    'mensagem' => '[FAKE] Mensagem de atualização para validação de painel.',
                    'status' => $isPaid ? 'enviado' : 'pendente',
                    'enviado_em' => $isPaid ? now()->subHours($i) : null,
                ]);

                $ticket = SacTicket::query()->create([
                    'order_id' => $order->id,
                    'user_id' => $client->id,
                    'atendente_id' => $analyst->id,
                    'assunto' => '[FAKE] Dúvida sobre contrato',
                    'status' => $isPaid ? 'em_atendimento' : 'aberto',
                    'prioridade' => 'media',
                ]);

                SacMessage::query()->create([
                    'sac_ticket_id' => $ticket->id,
                    'user_id' => $client->id,
                    'mensagem' => '[FAKE] Cliente solicitou retorno sobre proposta.',
                    'tipo' => 'texto',
                    'lida' => false,
                ]);

                if (! $isPaid) {
                    continue;
                }

                $feeAmount = (float) random_int(3000, 15000);
                $entryAmount = round($feeAmount * 0.5, 2);

                $contract = Contract::query()->create([
                    'order_id' => $order->id,
                    'user_id' => $client->id,
                    'analyst_id' => $analyst->id,
                    'debt_amount' => $feeAmount * 2,
                    'fee_amount' => $feeAmount,
                    'entry_percentage' => 50,
                    'entry_amount' => $entryAmount,
                    'installments_count' => 3,
                    'status' => 'em_execucao',
                    'payment_provider' => 'asaas',
                    'accepted_at' => now()->subDays(2),
                ]);

                $labels = [
                    0 => 'Entrada',
                    1 => 'Parcela 1/3',
                    2 => 'Parcela 2/3',
                    3 => 'Parcela 3/3',
                ];

                for ($n = 0; $n <= 3; $n++) {
                    $amount = $n === 0 ? $entryAmount : round(($feeAmount - $entryAmount) / 3, 2);
                    $installment = ContractInstallment::query()->create([
                        'contract_id' => $contract->id,
                        'order_id' => $order->id,
                        'installment_number' => $n,
                        'label' => $labels[$n],
                        'amount' => $amount,
                        'due_date' => now()->addDays($n * 30)->toDateString(),
                        'billing_type' => 'PIX',
                        'payment_provider' => 'asaas',
                        'status' => $n === 0 ? 'pago' : 'aguardando_pagamento',
                        'paid_at' => $n === 0 ? now()->subDay() : null,
                    ]);

                    if ($n === 0) {
                        SellerCommission::query()->create([
                            'order_id' => $order->id,
                            'seller_id' => $analyst->id,
                            'source_type' => 'contract_installment',
                            'source_id' => $installment->id,
                            'base_amount' => $amount,
                            'rate' => 0.4,
                            'commission_amount' => round($amount * 0.4, 2),
                            'status' => 'available',
                            'available_at' => now()->subHours(2),
                            'notes' => '[FAKE] Comissão de validação visual.',
                        ]);
                    }
                }
            }
        });

        return back()->with('success', 'Dados fake gerados com sucesso para validação dos painéis.');
    }

    public function clearFakeData(): RedirectResponse
    {
        $fakeUsers = User::query()
            ->where('email', 'like', 'fake.cliente.%'.self::FAKE_CLIENT_EMAIL_DOMAIN)
            ->pluck('id');

        $fakeLeads = Lead::query()
            ->where('email', 'like', 'fake.cliente.%'.self::FAKE_CLIENT_EMAIL_DOMAIN)
            ->orWhere('nome', 'like', '[FAKE]%')
            ->pluck('id');

        $fakeOrderIds = Order::withTrashed()
            ->where('protocolo', 'like', self::FAKE_ORDER_PREFIX.'%')
            ->orWhereIn('user_id', $fakeUsers)
            ->orWhereIn('lead_id', $fakeLeads)
            ->pluck('id');

        DB::transaction(function () use ($fakeUsers, $fakeLeads, $fakeOrderIds): void {
            $ticketIds = SacTicket::withTrashed()
                ->whereIn('order_id', $fakeOrderIds)
                ->orWhereIn('user_id', $fakeUsers)
                ->pluck('id');

            SacMessage::query()->whereIn('sac_ticket_id', $ticketIds)->delete();
            SacTicket::withTrashed()->whereIn('id', $ticketIds)->forceDelete();

            WhatsappLog::query()
                ->whereIn('order_id', $fakeOrderIds)
                ->orWhereIn('user_id', $fakeUsers)
                ->delete();

            SellerCommission::query()->whereIn('order_id', $fakeOrderIds)->delete();

            ContractInstallment::query()->whereIn('order_id', $fakeOrderIds)->delete();
            Contract::query()->whereIn('order_id', $fakeOrderIds)->delete();

            Order::withTrashed()->whereIn('id', $fakeOrderIds)->forceDelete();
            Lead::query()->whereIn('id', $fakeLeads)->delete();
            User::withTrashed()->whereIn('id', $fakeUsers)->forceDelete();
        });

        return back()->with('success', 'Dados fake removidos com sucesso.');
    }
}
