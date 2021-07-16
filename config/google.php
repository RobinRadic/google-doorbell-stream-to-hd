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
        'recording'        => [
            'enabled'           => true,
            'directory'  => env('GOOGLE_DOORBELL_OUTPUT_DIRECTORY'),
            /**
             * @template
             *
             * The [filepath] will have the above [directory] prepended.
             *
             * The [filepath] value wil be parsed by string-template. The usable variables are:
             * The {@see \App\Google\Recorder\PathManager::getDateParameters()}
             * and {@see \App\Google\DataModels\Device::toArray()}
             *
             * By using this default template,
             * files will be placed like [directory]/2021/07-Jul/16-Fri/07-02-30.avi
             */
            'filepath'   => '{Y}/{m}-{M}/{d}-{D}/{H}-{i}-{s}',
            'extension'  => 'avi',
            'split_time' => 60 * 60, // max video time, in seconds.
        ],
    ],
];
