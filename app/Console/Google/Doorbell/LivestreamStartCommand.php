<?php

namespace App\Console\Google\Doorbell;

use App\Google\DataModels\Device;
use App\Google\LivestreamLoop;
use App\Google\Recorder;
use App\Models\Google;
use App\Models\LiveStream;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;

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
        $recorder = new Recorder\RecorderLoop($liveStream);
        $recorder->start();

    }
}
