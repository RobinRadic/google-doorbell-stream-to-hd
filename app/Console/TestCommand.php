<?php

namespace App\Console;

use App\Google\DataModels\Device;
use App\Google\LivestreamLoop;
use App\Google\Recorder;
use App\Models\Google;
use App\Models\LiveStream;
use App\Models\User;
use Google\Service\SmartDeviceManagement;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use function config;
use function env;
use function route;
use function url;

class TestCommand extends Command
{
    protected $signature = 'test';

    protected $description = 'DONT RUN DEV CODE STUFF';

    public function handle()
    {
        $recorder = new Recorder();
        $filePath = $recorder->resolveFilePath();

        return;
    }

    public function handwwwle()
    {
        $user   = User::first();
        $auth   = $user->auth;
        $client = $auth->getGoogleClient();
        $client->setAccessToken($auth->access_token);
        $http = $client->authorize();
        $res  = $client->getHttpClient()->get("https://smartdevicemanagement.googleapis.com/v1/enterprises/{$auth->project_id}/devices", [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => $auth->token_type . ' ' . $auth->access_token,
            ],
        ])->getBody()->getContents();

        return;
        $client = new \Google\Client([
            'base_path'     => 'https://nestservices.google.com/partnerconnections/eaf8cd73-9ea9-4e7e-8683-2aac55514216/auth',
            'redirect_uri'  => route('callback'),
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'client_id'     => config('google.client_id'),
            'response_type' => 'code',
        ]);
        $client->setScopes(SmartDeviceManagement::SDM_SERVICE);
        $url = $client->createAuthUrl();
        $this->line('Navigate to: ' . $url);
//        $client->fetchAccessTokenWithAuthCode(env('code'));
//        $smd = new SmartDeviceManagement($client);
//        $devs = $smd->enterprises_devices->listEnterprisesDevices('{parent=enterprises/*}');
////       $s = $smd->enterprises_structures->get('/enterprises/' . env('GOOGLE_DOORBELL_PROJECT_ID'));
//        https://nestservices.google.com/partnerconnections/eaf8cd73-9ea9-4e7e-8683-2aac55514216/auth?redirect_uri=https://www.google.com&access_type=offline&prompt=consent&client_id=795266524818-2kkjdc23r4ompfb096mhnjavpvkfr1qf.apps.googleusercontent.com&response_type=code&scope=https://www.googleapis.com/auth/sdm.service
    }

    public function handl2e()
    {
        $client = new \Google\Client([
            'application_name' => config('google.application_name'),
            'client_id'        => config('google.client_id'),
            'client_secret'    => config('google.client_id'),
            'redirect_uri'     => url('/callback'),
            'access_type'      => 'offline',
            'prompt'           => 'consent',
        ]);
        $client->setScopes('https://www.googleapis.com/auth/sdm.service');
        $client->fetchAccessTokenWithAuthCode(env('code'));
        $smd = new SmartDeviceManagement($client);

        return;
    }
}
