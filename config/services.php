<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ethereum' => [
        'provider' => env('ETHEREUM_PROVIDER', 'https://rpc.frax.com/'),
        'token_address' => env('TOKEN_ADDRESS', '0xDcc0F2D8F90FDe85b10aC1c8Ab57dc0AE946A543'),
    ],

    'frax' => [
        'base_url' => env('FRAX_BASE_URL', 'https://api.fraxscan.com/api'),
        'api_key' => env('FRAX_API_KEY', '9A66G855SZS1IHF51I3WUBPJEPM4XP8P6K'),
    ],

];
