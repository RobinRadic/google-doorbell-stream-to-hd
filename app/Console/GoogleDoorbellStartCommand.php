<?php

namespace App\Console;

use App\Google\DataModels\Device;
use App\Google\LivestreamLoop;
use App\Google\Recorder;
use App\Models\Google;
use App\Models\LiveStream;
use Illuminate\Console\Command;
use Symfony\Component\VarDumper\VarDumper;

class GoogleDoorbellStartCommand extends Command
{
    protected $signature = 'google:doorbell:start';

    protected $description = 'start livestreaming';

    public function handle()
    {
        $google  = Google::first();
        $service = $google->getGoogleService();
        /** @var Device $device */
        $device = $service->getDevices()->firstWhere('type', Device::DOORBELL_TYPE);
        $ls     = $service->doorbell->startLivestream($device, function (LiveStream $ls, LivestreamLoop $loop) {
            $recorder = new Recorder($loop);
            $this->line('---------------------------------');
            $this->line('Token: ' . $ls->getToken());
            $this->line('---------------------------------');
            $this->info($ls->getUrl());
            $this->line('---------------------------------');
            $this->info('Livestream started');
        });
    }
}
