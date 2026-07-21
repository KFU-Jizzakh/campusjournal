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

    'crossref' => [
        'enabled' => env('CROSSREF_ENABLED', false),
        'endpoint' => env('CROSSREF_ENDPOINT', 'https://test.crossref.org/servlet/deposit'),
        'username' => env('CROSSREF_USERNAME'),
        'password' => env('CROSSREF_PASSWORD'),
        'prefix' => env('CROSSREF_PREFIX'),
        'depositor_name' => env('CROSSREF_DEPOSITOR_NAME'),
        'depositor_email' => env('CROSSREF_DEPOSITOR_EMAIL'),
        'registrant' => env('CROSSREF_REGISTRANT'),
        'doi_pattern' => env('CROSSREF_DOI_PATTERN', '{prefix}/kfujournal.{year}.{volume}.{article_id}'),
    ],

];
