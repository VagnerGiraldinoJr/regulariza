<?php

namespace App\Http\Controllers;

use App\Jobs\EnviarAcessoPortalWhatsApp;
use App\Models\Contract;
use App\Services\ContractService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use RuntimeException;

class ContractAcceptanceController extends Controller
{
    public function show(string $token): View
    {
        $contract = $this->resolveContract($token);

        abort_if(
            $contract->accepted_at === null && $contract->acceptance_expires_at?->isPast(),
            410,
            'Este link de aceite expirou. Solicite um novo envio do contrato.'
        );

        return view('contracts.accept', compact('contract'));
    }

    public function accept(string $token, Request $request, ContractService $contractService): RedirectResponse
    {
        $contract = $this->resolveContract($token);

        if ($contract->accepted_at !== null) {
            return redirect()
                ->route('contracts.accept.show', $contract->acceptance_token)
                ->with('success', 'Este contrato já possui aceite registrado.');
        }

        $data = $request->validate([
            'accepted_name' => ['required', 'string', 'max:120'],
            'accept_terms' => ['accepted'],
        ]);

        try {
            $contract = $contractService->accept(
                contract: $contract,
                acceptedName: (string) $data['accepted_name'],
                acceptedIp: (string) $request->ip(),
                acceptedUserAgent: mb_substr((string) $request->userAgent(), 0, 65535)
            );
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('contracts.accept.show', $contract->acceptance_token)
                ->withErrors(['contract' => $exception->getMessage()]);
        }

        $chargeRelease = $contractService->releaseChargesAfterAcceptance($contract);
        $this->dispatchPortalAccessIfEligible($contract);

        $message = 'Aceite registrado com sucesso. O PDF final do contrato já pode ser baixado.';
        if (($chargeRelease['released'] ?? 0) > 0 && ($chargeRelease['failed'] ?? 0) === 0) {
            $message = 'Aceite registrado com sucesso. As cobranças do contrato já foram liberadas e o PDF final pode ser baixado.';
        } elseif (($chargeRelease['failed'] ?? 0) > 0) {
            $message = 'Aceite registrado com sucesso. O PDF final pode ser baixado, mas uma ou mais cobranças ainda não foram emitidas.';
        }

        return redirect()
            ->route('contracts.accept.show', $contract->acceptance_token)
            ->with('success', $message);
    }

    public function downloadPdf(string $token): Response
    {
        $contract = $this->resolveContract($token);

        abort_if($contract->accepted_at === null, 403, 'O PDF final do contrato só fica disponível após o aceite.');

        $filename = sprintf('contrato-%s-aceite.pdf', $contract->order?->protocolo ?: $contract->id);

        $pdf = Pdf::loadView('contracts.accepted-pdf', [
            'contract' => $contract,
        ])->setPaper('a4');

        return $pdf->download($filename);
    }

    public function downloadOriginalDocument(string $token): Response
    {
        $contract = $this->resolveContract($token);

        abort_if(! filled($contract->document_path), 404, 'Este contrato não possui documento-base anexado.');

        return Storage::download(
            $contract->document_path,
            basename((string) $contract->document_path)
        );
    }

    private function resolveContract(string $token): Contract
    {
        return Contract::query()
            ->with(['order', 'user', 'analyst', 'installments'])
            ->where('acceptance_token', $token)
            ->firstOrFail();
    }

    private function dispatchPortalAccessIfEligible(Contract $contract): void
    {
        $entryPaid = $contract->installments->firstWhere('installment_number', 0)?->status === 'pago';

        if (! $entryPaid || $contract->portal_access_sent_at !== null || ! $contract->order) {
            return;
        }

        EnviarAcessoPortalWhatsApp::dispatch($contract->order);
        $contract->update(['portal_access_sent_at' => now()]);
    }
}
