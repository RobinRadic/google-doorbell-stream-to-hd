<?php

namespace App\Console;

use App\Google\Recorder\RecordManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class TestCommand extends Command
{
    protected $signature = 'test';

    protected $description = 'DONT RUN DEV CODE STUFF';

    public function handle()
    {
        /** @var RecordManager $rm */
        $rm      = App::make(RecordManager::class);
        $records = $rm->getRecords();
        /** @var \App\Google\Recorder\Record $record */
        $record = $records->first();
        $isRecording = $record->isRecording();
        $data = $record->toArray();
        $this->line("Record {$record->getFilename()} is now recording for {$record->getDuration()} minutes");
        return;
    }
}
