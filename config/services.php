<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY'),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        'chat_model' => env('DEEPSEEK_CHAT_MODEL', 'deepseek-chat'),
        'vision_model' => env('DEEPSEEK_VISION_MODEL', 'deepseek-vl'),
        // NOTA: al momento de generar este scaffold, DeepSeek no confirma
        // públicamente un endpoint de embeddings dedicado. Verificar
        // disponibilidad antes de depender de este modelo en producción;
        // si no existe, cambiar el proveedor de embeddings en
        // App\Domains\Catalog\Services\EmbeddingClient.
        'embedding_model' => env('DEEPSEEK_EMBEDDING_MODEL', 'deepseek-embedding'),
        'embedding_dimensions' => env('DEEPSEEK_EMBEDDING_DIMENSIONS', 1536),
    ],

    'whatsapp' => [
        'base_url' => env('WHATSAPP_CLOUD_API_BASE_URL', 'https://graph.facebook.com/v21.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
    ],

];
