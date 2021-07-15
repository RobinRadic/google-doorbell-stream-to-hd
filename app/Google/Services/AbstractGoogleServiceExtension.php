<?php

namespace App\Google\Services;

use App\Google\DataModels\Device;
use GuzzleHttp\ClientInterface;

/**
 * @method \Psr\Http\Message\ResponseInterface get(string $url, array $options = [])
 * @method \Psr\Http\Message\ResponseInterface post(string $url, array $body = [], array $options = [])
 * @method array executeCommand(string|Device $device, string $command, array $params = [])
 * @method array getJson(string $url, array $options = [])
 * @method array postJson(string $url,array $body = [],  array $options = [])
 * @method string getBaseUrl()
 * @method void setBaseUrl(string $baseUrl)
 * @method \Google\Client getClient()
 * @method ClientInterface getHttp()
 * @method \App\Models\Google getGoogle()
 * @method string resolveDeviceId(string|Device $device)
 */
abstract class AbstractGoogleServiceExtension
{
    protected GoogleService $google;

    public function __construct(GoogleService $google)
    {
        $this->google = $google;
    }

    public function __call(string $name, $arguments = [])
    {
//        $name = 'get' . Str::camel($name);
        if (method_exists($this->google, $name)) {
            return $this->google->{$name}(...$arguments);
        }
        throw new \BadMethodCallException("({$name})");
    }

}
