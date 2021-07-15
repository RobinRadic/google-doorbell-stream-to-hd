<?php

use Google\Service\SmartDeviceManagement;

return [
    'smd' => [
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
        'output_directory' => env('GOOGLE_DOORBELL_OUTPUT_DIRECTORY'),
    ],
];
