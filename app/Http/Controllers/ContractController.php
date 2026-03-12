<?php

namespace App\Http\Controllers;

use App\Jobs\EnviarAcessoPortalWhatsApp;
use App\Jobs\EnviarLinkAceiteContratoWhatsApp;
use App\Models\Contract;
use App\Models\Order;
use App\Services\ContractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function adminIndex(Request $request): View
    {
        $contracts = Contract::query()
            ->with(['user', 'analyst', 'order', 'installments'])
            ->latest('id')
            ->paginate(20);

        $eligibleOrders = Order::query()
            ->with(['user', 'lead'])
            ->where('pagamento_status', 'pago')
            ->whereDoesntHave('contract')
            ->latest('id')
            ->limit(100)
            ->get();

        return view('admin/contracts/index', compact('contracts', 'eligibleOrders'));
    }

    public function store(Request $request, ContractService $contractService): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')],
            'debt_amount' => ['required', 'numeric', 'min:0'],
            'fee_amount' => ['required', 'numeric', 'min:0.01'],
            'entry_percentage' => ['required', 'numeric', 'min:1', 'max:99'],
            'document' => ['nullable', 'file', 'mimes:doc,docx,pdf', 'max:10240'],
        ]);

        $order = Order::query()->findOrFail((int) $data['order_id']);
        $contract = $contractService->createForOrder(
            order: $order,
            debtAmount: (float) $data['debt_amount'],
            feeAmount: (float) $data['fee_amount'],
            entryPercentage: (float) $data['entry_percentage'],
            documentFile: $request->file('document')
        );

        if ($contract->order) {
            EnviarLinkAceiteContratoWhatsApp::dispatch($contract);
            EnviarAcessoPortalWhatsApp::dispatch($contract->order);
            $contract->update(['portal_access_sent_at' => now()]);
        }

        return back()->with('success', 'Contrato criado com sucesso. O link de aceite e os dados de acesso ao portal foram enviados ao cliente por WhatsApp e e-mail quando disponíveis.');
    }

    public function analystIndex(Request $request): View
    {
        $analystId = (int) $request->user()->id;

        $contracts = Contract::query()
            ->with(['user', 'order', 'installments'])
            ->where('analyst_id', $analystId)
            ->latest('id')
            ->paginate(20);

        return view('analyst/contracts', compact('contracts'));
    }

    public function clientIndex(Request $request): View
    {
        $contracts = Contract::query()
            ->with(['order', 'analyst', 'installments'])
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate(20);

        return view('portal/contracts', compact('contracts'));
    }
}
