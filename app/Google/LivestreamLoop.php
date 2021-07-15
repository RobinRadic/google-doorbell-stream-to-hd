<?php

namespace App\Google;

use App\Google\DataModels\Device;
use App\Google\Services\DoorbellServiceExtension;
use App\Google\Services\GoogleService;
use App\Models\LiveStream;
use Illuminate\Support\Facades\File;
use React\EventLoop\Loop;

class LivestreamLoop
{

    protected DoorbellServiceExtension $doorbell;

    protected int $ticks = 0;

    protected array $startCallbacks = [];

    protected array $tickCallbacks = [];

    protected array $stopCallbacks = [];

    public function onStart(callable $callback)
    {
        $this->startCallbacks[] = $callback;
        return $this;
    }
    public function onTick(callable $callback)
    {
        $this->tickCallbacks[] = $callback;
        return $this;
    }
    public function onStop(callable $callback)
    {
        $this->stopCallbacks[] = $callback;
        return $this;
    }

    /**
     * @param array|callable[] $callbacks
     * @param array $arguments
     * @return void
     */
    protected function runCallbacks(array $callbacks, array $arguments = [])
    {
        foreach($callbacks as $callback){
            $callback(...$arguments);
        }
    }

    /**
     * LivestreamLoop constructor.
     */
    public function __construct(protected GoogleService $service,
                                protected LiveStream $ls,
                                protected Device $device)
    {
        $this->doorbell = $this->service->doorbell;
        Loop::addPeriodicTimer(1, [ $this, 'tick' ]);
    }

    public function tick()
    {
        if ($this->hasReceivedTerminationSignal()) {
            $this->stop();
        }
        if ($this->ls->isExpired()) {
            $this->stop();
        }
        $expiresIn = $this->ls->getSecondsUntilExpires();
        if ($expiresIn < 30) {
            $this->ls = $this->doorbell->extendLivestream($this->device, $this->ls->extension_token);
        }
        $this->ticks++;
        $this->writeUptimeDataToFile($expiresIn);
        $this->runCallbacks($this->tickCallbacks);
    }

    protected function writeUptimeDataToFile(int $expiresIn)
    {
        $lines   = [];
        $lines[] = "ticks:          {$this->ticks}";
        $lines[] = "expires in:     {$expiresIn}";
        $lines[] = "expires at:     {$this->ls->expires_at}";
        $lines[] = "project id:     {$this->ls->project_id}";
        $lines[] = "device id:      {$this->ls->device_id}";
        $lines[] = "url:            {$this->ls->url}";
        File::put(static::getDataFilePath(), implode("\n", $lines));
    }

    public function start()
    {
        $this->removeTerminationSignal();
        $this->runCallbacks($this->startCallbacks);
        Loop::run();
    }

    public function stop()
    {
        Loop::stop();
        $this->removeTerminationSignal();
        File::put(static::getDataFilePath(), 'not running');
        $this->runCallbacks($this->stopCallbacks);
    }

    protected function hasReceivedTerminationSignal()
    {
        return File::exists(static::getTerminationSignalFilePath());
    }

    protected function removeTerminationSignal()
    {
        return File::delete(static::getTerminationSignalFilePath());
    }

    public function getTicks()
    {
        return $this->ticks;
    }

    public function getLiveStream()
    {
        return $this->ls;
    }

    public function getDevice()
    {
        return $this->device;
    }

    public static function getDataFilePath()
    {
        return storage_path('livestreamloop.info');
    }

    protected static function getTerminationSignalFilePath()
    {
        return storage_path('livestreamloop.terminate');
    }

    public static function terminateProcess()
    {
        File::put(static::getTerminationSignalFilePath(), '');
        File::put(static::getDataFilePath(), 'not running');
    }



}
