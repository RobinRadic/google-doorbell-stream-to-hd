<?php

namespace App\Google\Services;

use App\Google\DataModels\Device;
use App\Google\LivestreamLoop;
use App\Models\LiveStream;
use Illuminate\Encryption\Encrypter;

class DoorbellServiceExtension extends AbstractGoogleServiceExtension
{
    public const COMMAND_START_LIVESTREAM = 'sdm.devices.commands.CameraLiveStream.GenerateRtspStream';
    public const COMMAND_EXTEND_LIVESTREAM = 'sdm.devices.commands.CameraLiveStream.ExtendRtspStream';
    public const COMMAND_STOP_LIVESTREAM = 'sdm.devices.commands.CameraLiveStream.StopRtspStream';
    public const COMMAND_PICTURE = 'sdm.devices.commands.CameraEventImage.GenerateImage';
    public const TYPE = 'sdm.devices.types.DOORBELL';

    protected string $previousRandomKey;


    public function makePicture(Device $device)
    {
        $data = $this->executeCommand($device, self::COMMAND_PICTURE);
        return $data;
    }

    public function startLivestream(Device $device, callable $startingCallback = null): LiveStream
    {
        $data = $this->executeCommand($device, self::COMMAND_START_LIVESTREAM);
        $ls   = LiveStream::start($device, $data);
        return $ls;
    }

    public function extendLivestream(LiveStream $ls): LiveStream
    {
        $data = $this->executeCommand($ls->getDevice(), self::COMMAND_EXTEND_LIVESTREAM, [ 'streamExtensionToken' => $ls->extension_token ]);
        return $ls->extend($data);
    }

    public function stopLivestream(LiveStream $ls): LiveStream
    {
        $data = $this->executeCommand($ls->getDevice(), self::COMMAND_STOP_LIVESTREAM, [ 'streamExtensionToken' => $ls->extension_token ]);
        $ls->stop();
    }

    protected function generateRandomKey(): string
    {
        return $this->previousRandomKey = base64_encode(Encrypter::generateKey(config('app.cipher')));
    }

}
