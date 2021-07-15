<?php

use Google\Service\SmartDeviceManagement;

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
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses'        => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'google_smd' => [
        'base_path'        => fn($projectId) => "https://nestservices.google.com/partnerconnections/{$projectId}/auth",
        'application_name' => env('GOOGLE_APPLICATION_NAME', ''),
        'client_id'        => env('GOOGLE_CLIENT_ID', ''),
        'client_secret'    => env('GOOGLE_CLIENT_SECRET', ''),
        'redirect_uri'     => fn() => route('google.callback'),
        'scopes'           => [ SmartDeviceManagement::SDM_SERVICE ],
        'access_type'      => 'offline', // 'online',
        'prompt'           => 'consent',
        'developer_key'    => env('GOOGLE_DEVELOPER_KEY', ''),
        'project_id'       => env('GOOGLE_DOORBELL_PROJECT_ID'),
        'response_type'    => 'code',
    ],

];
