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
            /**
             * @template
             * The command uses @link https://packagist.org/packages/nicmart/string-template
             * The [command_variables] are used to provide the values for the {placeholders}
             */
            'command'           => 'ffmpeg -i {url} -b {bitrate} -vcodec copy -r {fps} -y {filepath}',
            /**
             * The [command_variables] are used to provide the string-template values
             * For the {placeholders} in both the [command] and [command_variables.filepath] values
             */
            'command_variables' => [
                // url: this value will be override on runtime, after the google server responds with, the rtsp:// link will be filled in.
                'url'        => null,
                'bitrate'    => '900k',
                'fps'        => 30,
                'directory'  => env('GOOGLE_DOORBELL_OUTPUT_DIRECTORY'),
                /**
                 * @template
                 * @see \App\Google\Recorder::getDateParameters()
                 * The [filepath] value wil also be parsed by string-template.
                 * The [command_variables] are used to provide the string-template values
                 * The characters you see are all provided by the `date()` function
                 * ! The [directory] will be prepended. So don't include it there.
                 */
                'filepath'   => '{Y}/{m}-{M}/{d}-{D}/{H}-{i}-{s}',
                'extension'  => 'avi', // File type extension. Will be appended to the filepath (including a dot . obviously)
                'split_time' => 60 * 60, // Seconds between splitting each recording into a file. Currently 1 Hour
                'device'     => [
                    // these values will be override by $device->toArray(). can be used in the [command] AND [filepath] template
                    'assignee'        => null,
                    'name'            => null,
                    'parentRelations' => null,
                    'traits'          => null,
                    'type'            => null,
                    'projectId'       => null,
                    'deviceId'        => null,
                    'deviceType'      => null,
                    'structureId'     => null,
                    'roomId'          => null,
                    'roomName'        => null,
                ],
            ],
        ],
    ],
];
