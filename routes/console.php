<?php

use App\Models\Order;
use App\Services\PaidOrderReconciliationService;
use App\Services\SellerCommissionService;
use App\Services\ZApiService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('commissions:process', function (SellerCommissionService $service) {
    $released = $service->releaseDueCommissions();
    $result = $service->payoutAvailableCommissions();

    $this->info("Comissões liberadas: {$released}");
    $this->info("Comissões pagas: {$result['paid']}");
    $this->line("Comissões sem chave PIX: {$result['skipped']}");
    $this->line("Falhas no pagamento: {$result['failed']}");
})->purpose('Libera e paga comissões elegíveis dos vendedores via PIX/Asaas');

Artisan::command('whatsapp:boas-vindas {phone} {--nome=Cliente} {--protocolo=REG-TESTE-001}', function (ZApiService $zApiService) {
    $message = $zApiService->renderTemplate('boas_vindas', [
        'nome' => (string) $this->option('nome'),
        'protocolo' => (string) $this->option('protocolo'),
        'link' => config('app.url').'/login',
    ]);

    $imageUrl = trim((string) config('zapi.media.boas_vindas_image_url', ''));
    $phone = (string) $this->argument('phone');

    if ($imageUrl !== '') {
        $result = $zApiService->enviarImagem($phone, $imageUrl, $message, 'boas_vindas');
        $this->info('Resultado (imagem): '.json_encode($result, JSON_UNESCAPED_UNICODE));

        return;
    }

    $result = $zApiService->enviarMensagem($phone, $message, 'boas_vindas');
    $this->info('Resultado (texto): '.json_encode($result, JSON_UNESCAPED_UNICODE));
})->purpose('Envia mensagem de boas-vindas via WhatsApp (com imagem se configurada)');

Artisan::command('whatsapp:avaliacao {phone} {--nome=Cliente} {--protocolo=SAC-TESTE-001}', function (ZApiService $zApiService) {
    $message = $zApiService->renderTemplate('avaliacao_atendimento', [
        'nome' => (string) $this->option('nome'),
        'protocolo' => (string) $this->option('protocolo'),
    ]);

    $result = $zApiService->enviarMensagem(
        (string) $this->argument('phone'),
        $message,
        'lembrete'
    );

    $this->info('Resultado: '.json_encode($result, JSON_UNESCAPED_UNICODE));
})->purpose('Envia mensagem de avaliação de atendimento via WhatsApp');

Artisan::command('orders:reconcile-paid {order_id?*}', function (PaidOrderReconciliationService $service) {
    $ids = collect((array) $this->argument('order_id'))
        ->filter(fn ($value) => is_numeric($value))
        ->map(fn ($value) => (int) $value)
        ->values();

    $orders = Order::query()
        ->with(['lead', 'user'])
        ->where('pagamento_status', 'pago')
        ->when($ids->isNotEmpty(), fn ($query) => $query->whereIn('id', $ids->all()))
        ->orderBy('id')
        ->get();

    if ($orders->isEmpty()) {
        $this->warn('Nenhum pedido pago encontrado para reconciliar.');

        return;
    }

    $processed = 0;

    foreach ($orders as $order) {
        $service->reconcile($order, [], false);
        $processed++;
        $this->line("Pedido #{$order->id} reconciliado.");
    }

    $this->info("Pedidos reconciliados: {$processed}");
})->purpose('Reprocessa pedidos pagos para garantir comissão, portal e status consistentes');
