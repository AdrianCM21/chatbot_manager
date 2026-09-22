<?php

declare(strict_types=1);

namespace App\Domains\WhatsApp\Http\Controllers\Web;

use App\Domains\Settings\Models\BotSetting;
use App\Domains\WhatsApp\Jobs\ProcessIncomingWhatsAppMessageJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReceiveWhatsAppWebhookController
{
    public function __invoke(Request $request): Response
    {
        // Meta espera 200 siempre, incluso con el bot pausado, para no reintentar el envío.
        if (BotSetting::isEnabled()) {
            ProcessIncomingWhatsAppMessageJob::dispatch($request->all());
        }

        return response('', 200);
    }
}
