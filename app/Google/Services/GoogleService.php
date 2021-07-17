<?php

namespace App\Google\Services;

use App\Google\DataModels\Device;
use App\Google\DataModels\DeviceCollection;
use App\Models\Google;
use Google\Client as GoogleClient;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use StringTemplate\Engine as StringParser;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @property \App\Google\Services\DoorbellServiceExtension $doorbell
 */
class GoogleService
{
    protected GoogleClient $client;

    protected GuzzleClient $http;

    protected StringParser $parser;

    protected string $baseUrl = 'https://smartdevicemanagement.googleapis.com/v1';

    protected static array $extensions = [];

    public function __construct(protected Google $google)
    {
        $this->parser = new StringParser();
        $this->client = $google->getGoogleClient();
        if ($this->client->isAccessTokenExpired()) {
            $token = $this->client->fetchAccessTokenWithRefreshToken($google->refresh_token);
            $this->google->setToken($token);
        }
        $this->client->setAccessToken($google->access_token);
        $this->client->refreshToken($google->refresh_token);
        $this->client->setDeveloperKey(env('GOOGLE_API_KEY', ''));

        $this->http = $this->client->authorize();
    }

    protected function ensureAuthorization()
    {
        if ($this->client->isAccessTokenExpired()) {
            $token = $this->client->fetchAccessTokenWithRefreshToken($this->google->refresh_token);
            $this->google->setToken($token);
            $this->client->setAccessToken($this->google->access_token);
            $this->client->refreshToken($this->google->refresh_token);
            $this->http = $this->client->authorize();
        }
    }

    public static function make(Google $auth)
    {
        return new static($auth);
    }

    /**
     * @return Device[]|DeviceCollection
     */
    public function getDevices()
    {
        $devices = $this->getJson("/enterprises/{project_id}/devices");
        if (isset($devices[ 'devices' ])) {
            return DeviceCollection::make($devices[ 'devices' ])->map(fn(array $device) => new Device($device));
        }
        return new DeviceCollection;
    }

    public function get(string $url, array $options = [])
    {
        $this->applyOptions($options);
        return $this->http->get($this->parse($url), $options);
    }

    public function post(string $url, array $body = [], array $options = [])
    {
        $options[ 'body' ] = json_encode($body, JSON_THROW_ON_ERROR);
        $options           = $this->applyOptions($options);
        $url               = $this->parse($url);
        return $this->http->post($url, $options);
    }

    public function getJson(string $url, array $options = [])
    {
        $response = $this->get($url, $options);
        return $this->handleJsonResponse($url, $response);
    }

    public function postJson(string $url, array $body = [], array $options = [])
    {
        $response = $this->post($url, $body, $options);
        return $this->handleJsonResponse($url, $response);
    }

    public function executeCommand(string|Device $device, string $command, array $params = [])
    {
        $body = [ 'command' => $command ];
        if ( ! empty($params)) {
            $body[ 'params' ] = $params;
        }
        $id  = $this->resolveDeviceId($device);
        $res = $this->postJson("/enterprises/{project_id}/devices/{$id}:executeCommand", $body);

        return $res;
    }

    protected function handleJsonResponse($url, ResponseInterface $response)
    {
        try {
            $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            if ($response->getStatusCode() > 299) {
                $msg = $response->getBody()->getContents();
                throw new HttpException($response->getStatusCode(), "Request to [{$url}] failed\nStatus code: {$response->getStatusCode()}\nReason: {$response->getReasonPhrase()}\n{$msg}", null, $response->getHeaders());
            }
            return $data;
        }
        catch (\JsonException $e) {
            throw $e;
        }
    }

    protected function applyOptions(array &$options)
    {
        $this->ensureAuthorization();
        $options = array_replace_recursive([
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => $this->google->token_type . ' ' . $this->google->access_token,
            ],
        ], $options);
        return $options;
    }

    public function resolveDeviceId(string|Device $device): string
    {
        return $device instanceof Device ? $device->getDeviceId() : $device;
    }

    protected function parse($url)
    {
        if ( ! Str::startsWith($url, 'http')) {
            $url = $this->baseUrl . $url;
        }
        return (string)$this->parser->render($url, $this->google->getAttributes());
    }

    public static function extend(string $name, string $class)
    {
        static::$extensions[ $name ] = $class;
    }

    /** @noinspection MagicMethodsValidityInspection */
    public function __get($name)
    {
        if (isset(static::$extensions[ $name ])) {
            $class = static::$extensions[ $name ];
            if (is_string($class)) {
                static::$extensions[ $name ] = App::make($class, [ 'google' => $this ]);
            }
            return static::$extensions[ $name ];
        }
        throw new \InvalidArgumentException("__get('{$name}') to get extension does not exist");
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getHttp()
    {
        return $this->http;
    }

    public function setParser($parser)
    {
        $this->parser = $parser;
        return $this;
    }

}
