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
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'vtpass' => [
        'base_url' => env('VTPASS_BASEURL'),
        'username' => env('VTPASS_USERNAME'),
        'password' => env('VTPASS_PASSWORD'),
    ],

    'megasub' => [
        'base_url' => env('MEGA_SUB_BASEURL'),
        'token' => env('MEGA_SUB_TOKEN'),
        'password' => env('MEGA_SUB_PASSWORD'),
    ],

    'monnify' => [
        'base_url' => env('MONNIFY_BASEURL'),
        'api_key' => env('MONNIFY_API_KEY'),
        'secret_key' => env('MONNIFY_SECRET_KEY'),
        'contract_code' => env('MONNIFY_CONTRACT_CODE'),
    ],

    'crystalpay' => [
        'base_url' => env('CRYSTALPAY_BASEURL'),
        'secret_key' => env('CRYSTALPAY_SECRET_KEY')
    ]

];
