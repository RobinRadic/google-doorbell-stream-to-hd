<?php

namespace App\Console\Google\Doorbell;

use App\Google\DataModels\Device;
use App\Google\Recorder\Record;
use App\Google\Recorder\Recorder;
use App\Google\Recorder\Recorder as R;
use App\Models\Google;
use Illuminate\Console\Command;
use Symfony\Component\Console\Style\OutputStyle;
use Webmozart\PathUtil\Path;

class LivestreamStartCommand extends Command
{
    protected $signature = 'google:doorbell:start {name?}';

    protected $description = 'Records the livestream of the doorbell camera';

    protected Recorder $recorder;

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
        $this->info('Livestream started');
        $recorder = $this->recorder = new Recorder($liveStream);

        $recorder->on(R::EVENT_START, function () {
            $out = $this->getOutput();
            $out->newLine();
            $out->writeln("<fg=yellow>Livestream recording started. Recording to file:</>");
            $out->writeln("<fg=white;options=bold,underscore>{$this->recorder->getCurrentRecord()->getFilePath()}</>");
            $out->newLine();
        });
        $recorder->on(R::EVENT_SPLIT, function () {
            $out = $this->getOutput();
            $out->newLine();
            $out->writeln("<fg=yellow>Split time recording reached. Now recording to file:</>");
            $out->writeln("<fg=white;options=underscore>{$this->recorder->getCurrentRecord()->getFilePath()}</>");
            $out->newLine();
        });
        $recorder->on(R::EVENT_TICK, fn() => $this->getOutput()->write($recorder->getTicks() . ' ', false, OutputStyle::VERBOSITY_VERY_VERBOSE));
        $recorder->on(R::EVENT_EXTEND, fn() => $this->comment('extended', OutputStyle::VERBOSITY_VERBOSE));

        $recorder->on(R::EVENT_TICK, function () {
            if ($this->recorder->getTicks() === 20) {
                $this->showRecordDetails($this->recorder->getCurrentRecord());
            }
            if ($this->recorder->getTicks() === 120) {
                $this->showRecordDetails($this->recorder->getCurrentRecord());
            }
        });
        $recorder->start();
    }

    protected function showRecordDetails(Record $record)
    {
        $seconds  = $record->getDuration(Record::DURATION_SECONDS);
        $minutes  = $record->getDuration(Record::DURATION_MINUTES);
        $start    = $record->getStartTime()->toString();
        $end      = $record->isRecording() ? '<comment>Still recording</comment>' : $record->getEndTime()->toString();
        $duration = $seconds < 60 ? $seconds . ' seconds' : $minutes . ' minutes';
        $filename = $record->getFilename();
        $path     = "<options=underscore>{$record->getFilePath()}</>";
        $out      = $this->getOutput();
        $out->newLine();
        $out->writeln(" <fg=yellow>Record details</> ");
        $out->horizontalTable([ 'start', 'end', 'duration', 'filename', 'path' ], [
            [
                $start,
                $end,
                $duration,
                $filename,
                $path,
            ],
        ]);
    }
}
