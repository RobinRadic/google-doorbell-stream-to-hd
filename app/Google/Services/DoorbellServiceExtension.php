<?php

namespace App\Google\Services;

use App\Google\DataModels\Device;
use App\Google\DataModels\DeviceCollection;
use App\Google\LivestreamLoop;
use App\Models\LiveStream;
use Illuminate\Encryption\Encrypter;

class DoorbellServiceExtension extends AbstractGoogleServiceExtension
{
    public const COMMAND_START_LIVESTREAM = 'sdm.devices.commands.CameraLiveStream.GenerateRtspStream';
    public const COMMAND_EXTEND_LIVESTREAM = 'sdm.devices.commands.CameraLiveStream.ExtendRtspStream';
    public const COMMAND_STOP_LIVESTREAM = 'sdm.devices.commands.CameraLiveStream.StopRtspStream';
    public const COMMAND_PICTURE = 'sdm.devices.commands.CameraEventImage.GenerateImage';
    public const TYPE='sdm.devices.types.DOORBELL';

    protected string $previousRandomKey;
    protected LivestreamLoop $loop;


    public function makePicture(Device $device)
    {
        $data = $this->executeCommand($device, self::COMMAND_PICTURE);
        return $data;
    }

    public function startLivestream(Device $device, callable $startingCallback = null, bool $loop = true): LiveStream
    {
        $data = $this->executeCommand($device, self::COMMAND_START_LIVESTREAM);
        $ls = LiveStream::start($device, $data);
        if($loop) {
            $this->loop = new LivestreamLoop($this->google, $ls, $device);
            if ($startingCallback) {
                $startingCallback($ls, $this->loop);
            }
            $this->loop->start();
        }
        return $ls;
    }

    public function extendLivestream(Device $device, string $streamExtensionToken): LiveStream
    {
        $data = $this->executeCommand($device, self::COMMAND_EXTEND_LIVESTREAM, compact('streamExtensionToken'));
        return LiveStream::extend($device,$data);
    }

    public function stopLivestream(Device $device, string $streamExtensionToken): LiveStream
    {
        $data = $this->executeCommand($device, self::COMMAND_STOP_LIVESTREAM, compact('streamExtensionToken'));
        $this->loop->stop();
        return LiveStream::stop($device);
    }

    protected function generateRandomKey(): string
    {
        return $this->previousRandomKey = base64_encode(Encrypter::generateKey(config('app.cipher')));
    }

    public function getLoop()
    {
        return $this->loop;
    }
}
