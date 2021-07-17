<?php

namespace App\Google\Recorder;

class Timer
{
    protected int $ticks = 0;

    protected bool $called = false;

    protected bool $running = false;

    public function __construct(
        protected int $interval,
        protected $callback,
        protected bool $repeat = true,
        bool $autoStart = true)
    {
        if ($autoStart) {
            $this->start();
        }
    }

    public function tick()
    {
        if ($this->running === false) {
            return;
        }
        if ($this->repeat === false && $this->called === true) {
            return;
        }

        $this->ticks++;

        if ($this->ticks === $this->interval) {
            $this->ticks = 0;
            $this->call($this->callback);
        }
    }

    protected function call(callable $callback)
    {
        $callback();
        $this->called = true;
    }

    public function stop()
    {
        $this->running = false;
        return $this;
    }

    public function start()
    {
        $this->running = true;
        return $this;
    }

    public function isRunning()
    {
        return $this->running;
    }
}
