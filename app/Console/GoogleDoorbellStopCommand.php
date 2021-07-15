<?php

namespace App\Console;

use App\Google\LivestreamLoop;
use Illuminate\Console\Command;

class GoogleDoorbellStopCommand extends Command
{
    protected $signature = 'google:doorbell:stop';

    protected $description = 'stop livestreaming';

    public function handle()
    {
        LivestreamLoop::terminateProcess();
        $this->info('Livestream has been stopped');
    }
}
