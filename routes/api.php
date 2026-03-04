<?php

use App\Http\Controllers\AsaasWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/asaas/webhook', AsaasWebhookController::class)
    ->name('api.asaas.webhook');
