<?php

namespace App\Console\Google;

use Illuminate\Console\Command;
use function route;

class GoogleAuthorizeCommand extends Command
{
    protected $signature = 'google:authorize';

    protected $description = 'Starts the OAuth2 procedure to authorize this application.';

    public function handle()
    {
        $this->line('Open your browser and navigate to:');
        $this->line(route('google.login'));
    }
}
