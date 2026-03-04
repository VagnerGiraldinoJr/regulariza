<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\SellerCommissionService;

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
