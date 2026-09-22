<?php

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\DuskTestCase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| El catálogo usa una columna `vector` (pgvector), que solo existe en
| Postgres, así que la suite completa corre contra una base de Postgres de
| test (ver .env.testing / phpunit.xml), no contra sqlite in-memory.
|
*/

uses(TestCase::class, RefreshDatabase::class)->in('Feature', 'Unit');

// tests/Browser (Dusk) arranca un navegador real contra un servidor real, en
// un proceso PHP distinto al del test: no puede compartir la transacción de
// RefreshDatabase, así que usa DatabaseMigrations (migra/limpia de verdad).
uses(DuskTestCase::class, DatabaseMigrations::class)->in('Browser');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Lee el contenido crudo de un fixture de tests/Fixtures.
 * (Pest ya trae fixture() para resolver el path; esta lee el contenido.)
 */
function fixtureContents(string $name): string
{
    return file_get_contents(fixture($name));
}

/**
 * Lee un fixture JSON de tests/Fixtures ya decodificado como array.
 *
 * @return array<string, mixed>
 */
function fixtureJson(string $name): array
{
    return json_decode(fixtureContents($name), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * POSTea al webhook de WhatsApp con la firma X-Hub-Signature-256 correcta
 * (calculada byte a byte sobre el mismo body que se envía), tal como lo
 * exige App\Domains\WhatsApp\Http\Middleware\VerifyWhatsAppWebhookSignature.
 *
 * @param  array<string, mixed>  $payload
 */
function postWhatsAppWebhook(array $payload, ?string $appSecret = null): TestResponse
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = 'sha256='.hash_hmac('sha256', $body, $appSecret ?? config('services.whatsapp.app_secret'));

    return test()->call(
        'POST',
        '/webhooks/whatsapp',
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ],
        content: $body,
    );
}

/**
 * Construye un TemporaryUploadedFile real (el tipo que espera
 * ImportProductsAction::getUploadedFileStream()) a partir de un fixture,
 * sin pasar por un upload real de Livewire.
 *
 * TemporaryUploadedFile::getClientOriginalName() decodifica el nombre real
 * desde el propio nombre físico del temporal ("{hash}-meta{base64(nombre)}-..."),
 * así que hay que respetar ese formato para que la extensión (.xlsx vs .csv)
 * se detecte bien.
 */
function fakeTemporaryUploadedFile(string $fixtureName, string $originalName): \Livewire\Features\SupportFileUploads\TemporaryUploadedFile
{
    $disk = \Livewire\Features\SupportFileUploads\FileUploadConfiguration::disk();
    $meta = str_replace('/', '_', base64_encode($originalName));
    $physicalName = \Illuminate\Support\Str::random(20).'-meta'.$meta.'-.'.pathinfo($originalName, PATHINFO_EXTENSION);
    $relativePath = \Livewire\Features\SupportFileUploads\FileUploadConfiguration::directory().'/'.$physicalName;

    \Illuminate\Support\Facades\Storage::disk($disk)->put($relativePath, fixtureContents($fixtureName));

    return \Livewire\Features\SupportFileUploads\TemporaryUploadedFile::createFromLivewire($relativePath);
}
