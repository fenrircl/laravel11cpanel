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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

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

    'mcp' => [
        'bearer' => env('MCP_BEARER'),
    ],

    'openai' => [
        'enabled' => env('ASSISTANT_CHAT_ENABLED', false),
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-6-luna'),
        'reasoning_effort' => env('OPENAI_REASONING_EFFORT', 'medium'),
        'max_output_tokens' => (int) env('OPENAI_MAX_OUTPUT_TOKENS', 700),
        'timeout' => (int) env('OPENAI_TIMEOUT', 35),
    ],

    // n8n webhook endpoint
    'n8n' => [
        'webhook_url' => env('N8N_WEBHOOK_URL'),
        'ingest_bearer' => env('N8N_INGEST_BEARER'),
        'pdf_webhook_url' => env('N8N_PDF_PROCESS_WEBHOOK_URL'),
        'pdf_webhook_bearer' => env('N8N_PDF_WEBHOOK_BEARER'),
    ],

];
