<?php

namespace App\Console\Google\Doorbell;

use App\Google\LivestreamLoop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LivestreamStatusCommand extends Command
{
    protected $signature = 'google:doorbell:status';

    protected $description = 'livestreaming status';

    public function handle()
    {
        $path=LivestreamLoop::getDataFilePath();
        if(File::exists($path)){
            return $this->line(File::get($path));
        }
        $this->warn('Nothing is livestreaming');
    }
}
