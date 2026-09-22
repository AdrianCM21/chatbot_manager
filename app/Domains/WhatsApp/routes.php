<?php

declare(strict_types=1);

use App\Domains\WhatsApp\Http\Controllers\Web\ReceiveWhatsAppWebhookController;
use App\Domains\WhatsApp\Http\Controllers\Web\VerifyWhatsAppWebhookController;
use App\Domains\WhatsApp\Http\Middleware\VerifyWhatsAppWebhookSignature;
use Illuminate\Support\Facades\Route;

Route::get('/webhooks/whatsapp', VerifyWhatsAppWebhookController::class);

Route::post('/webhooks/whatsapp', ReceiveWhatsAppWebhookController::class)
    ->middleware(VerifyWhatsAppWebhookSignature::class);
