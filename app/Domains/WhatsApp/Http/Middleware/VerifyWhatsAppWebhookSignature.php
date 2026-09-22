<?php

declare(strict_types=1);

namespace App\Domains\WhatsApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWhatsAppWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $appSecret = (string) config('services.whatsapp.app_secret');
        $signatureHeader = (string) $request->header('X-Hub-Signature-256');

        if ($appSecret === '' || $signatureHeader === '') {
            abort(401, 'Firma de webhook ausente o app secret sin configurar.');
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        if (! hash_equals($expected, $signatureHeader)) {
            abort(401, 'Firma de webhook inválida.');
        }

        return $next($request);
    }
}
