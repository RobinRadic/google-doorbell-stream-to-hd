<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function commands()
    {
        $this->load([
            __DIR__,
            __DIR__ . '/Google',
            __DIR__ . '/Google/Doorbell',
        ]);
    }
}
