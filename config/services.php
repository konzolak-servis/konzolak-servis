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

    // Pošta – příjem přes Cloudflare Email Worker, odesílání přes Brevo.
    'posta' => [
        'token' => env('POSTA_TOKEN'),
    ],

    'brevo' => [
        'key' => env('BREVO_API_KEY'),
        'from_name' => env('BREVO_FROM_NAME', env('APP_NAME', 'Konzolák Zlín')),
    ],

    // Offsite záloha na Cloudflare R2 (přes rclone). Prázdný remote = jen lokální zálohy.
    'zaloha' => [
        'r2_remote' => env('ZALOHA_R2_REMOTE'),
        'r2_config' => env('ZALOHA_R2_CONFIG'),
        'r2_keep' => (int) env('ZALOHA_R2_KEEP', 60),
        'rclone_path' => env('RCLONE_PATH', 'rclone'),
    ],

];
