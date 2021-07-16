<?php

namespace App\Console\Google\Doorbell;

use App\Google\DataModels\Device;
use App\Google\Recorder\Recorder;
use App\Models\Google;
use Illuminate\Console\Command;

class LivestreamStartCommand extends Command
{
    protected $signature = 'google:doorbell:start {name?}
                                                  {--r|record : Records the livestream to the configured file(s) }

                                                 ';

    protected $description = 'Initiates the livestream of the doorbell camera';

    public function handle()
    {
        $google  = Google::first();
        $service = $google->getGoogleService();
        /** @var Device $device */
        $devices = $service->getDevices()->where('type', Device::DOORBELL_TYPE);
        $device  = null;
        if ($this->argument('name')) {
            $device = $devices->firstWhere('roomName', $this->argument('name'));
        } else {
            $devicesByName = $devices->map->getRoomName()->toArray();
            $name          = $this->choice('Which doorbell?', $devicesByName);
            $device        = $devices->firstWhere('roomName', $name);
        }
        if ($device === null) {
            return $this->error("That device does not exist");
        }

        $liveStream = $service->doorbell->startLivestream($device);
        $recorder   = new Recorder($liveStream);
        $recorder->onTick(fn () => $this->line($recorder->getTicks()));
        $recorder->onExtend(fn () => $this->comment('extended'));
        $recorder->start();
    }
}
