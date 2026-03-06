<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\SellerCommissionService;
use App\Services\ZApiService;

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
