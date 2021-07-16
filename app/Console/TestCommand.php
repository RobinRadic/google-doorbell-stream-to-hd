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
use League\Uri\Components\Query;
use League\Uri\Uri;
use League\Uri\UriModifier;
use Webmozart\PathUtil\Url;
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
        $str = 'rtsps://stream-eu1-charlie.dropcam.com:443/sdm_live_stream/CiQA2vuxr6kP__VPDIB1H-xCFf0zZ_uhcq2bNnWCyZIiD8qh8T0ScQBBuHgoFmSwEZ0gZWeWjD6mIALsxKEFp3hQ7bWvrqzOLHQTytLL56GI-oBZOxSwmSxrT3BtyZBq12WyjXTcxXPlSQwT9rBPoMS7dp7eckX8Gy6VFrGUuhnfn6OGLolAxnpyrHOvbosutFoMSQ1jJrui?auth=g.0.eyJraWQiOiIyMzhiNTUxZmMyM2EyM2Y4M2E2ZTE3MmJjZTg0YmU3ZjgxMzAzMmM4IiwiYWxnIjoiUlMyNTYifQ.eyJpc3MiOiJuZXN0LXNlY3VyaXR5LWF1dGhwcm94eSIsInN1YiI6Im5lc3RfaWQ6bmVzdC1waG9lbml4LXByb2Q6MjA5NTQ5MjEiLCJwb2wiOiIzcC1vYXV0aC1zY29wZS1BUElfU0RNX1NFUlZJQ0UtY2xpZW50LTc5NTI2NjUyNDgxOC0ya2tqZGMyM3I0b21wZmIwOTZtaG5qYXZwdmtmcjFxZi5hcHBzLmdvb2dsZXVzZXJjb250ZW50LmNvbSIsImV4cCI6MTYyNjQwMzkwN30.nJEGyWjgsl2gp9uuDVI7pDD_nonOvCXfoOb79r9mJrC350_cu9EhYWR9EkwUAAWPs6KEImi-XGauupkyk6EzuiZsc5P2LleD55O7UstmPZEzzL8Hy9U00bq8k8zQWz9RpPwNVSqojupAT5eXB2fdFeC-nhxHLaZ2TMBucwWyfNVWVkGr01O753ZWwP93U0hONC2ykrOkL7ZPy4BlqQNXTHoM_o-MufST89KjqHCIBIkGl1fHpktLZtbbelb4_u0aMUsP-baRaHAoC8flpUxJ7_JmgeFcqYfTtfGu0BZCeGe6JHW35QPcpRTELpZDgUWm0vEVF7dU2EYm1BTBrUQCVQ';
        $url = Uri::createFromString($str);
        $query = Query::createFromUri($url);
        $auth = $query->get('auth');
        $url = $url->withQuery($query->withPair('auth','foo')->jsonSerialize());

        $url = UriModifier::removePairs($url, 'auth');
        $url = UriModifier::appendQuery($url, 'auth='.$streamToken);
        $uri = $url-> jsonSerialize();
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
