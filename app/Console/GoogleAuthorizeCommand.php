<?php

namespace App\Console;

use Illuminate\Console\Command;

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
