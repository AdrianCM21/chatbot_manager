<?php

declare(strict_types=1);

namespace App\Domains\WhatsApp\Http\Controllers\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VerifyWhatsAppWebhookController
{
    public function __invoke(Request $request): Response
    {
        $isValid = $request->query('hub_mode') === 'subscribe'
            && hash_equals((string) config('services.whatsapp.webhook_verify_token'), (string) $request->query('hub_verify_token'));

        abort_unless($isValid, 403);

        return response((string) $request->query('hub_challenge'), 200);
    }
}
