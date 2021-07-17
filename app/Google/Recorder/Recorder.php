<?php

namespace App\Google\Recorder;

use App\Models\LiveStream;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use React\EventLoop\Loop;
use Symfony\Component\Process\Process;
use Webmozart\PathUtil\Path;

class Recorder
{
    public const EVENT_START = 'start';
    public const EVENT_TICK = 'tick';
    public const EVENT_EXTEND = 'extend';
    public const EVENT_STOP = 'stop';
    public const EVENT_SPLIT = 'split';

    protected int $ticks = 0;

    protected Process $streamProcess;

    protected PathManager $paths;

    protected Dispatcher $events;

    protected bool $extended = false;

    protected int $splitTime;

    protected $mainFilePath;

    protected $tempFilePath;

    protected $recordings = [];

    protected Record $currentRecord;

    /**
     * The timer related logic is to save the current Record info as json
     * @var \React\EventLoop\Timer\Timers
     */

    protected Timer $timer;

    protected int $timerInterval = 20;

    public function __construct(protected LiveStream $ls)
    {
        $this->events = new Dispatcher();
        $this->paths  = App::make(PathManager::class);
        $this->paths->setDevice($ls->getDevice());
    }

    public function start()
    {
        Loop::addPeriodicTimer(1, [ $this, 'tick' ]);
        $this->resetSplitTime();
        $this->startStream();
        $this->currentRecord = new Record($this->mainFilePath);
        $this->currentRecord->start();
        $this->timer = new Timer($this->timerInterval, [ $this->currentRecord, 'saveInfo' ]);
        $this->fire(self::EVENT_START);
        Loop::run();
    }

    public function tick()
    {
        $this->ticks++;
        $this->splitTime--;
        $this->timer->tick();
        $this->fire(self::EVENT_TICK);
//        $this->runCallbacks($this->tickCallbacks, [ $this->ls ]);
        if ($this->splitTime < 2) {
            $this->split();
            return;
        }

        $expiresIn = $this->ls->getSecondsUntilExpires();
        if ($expiresIn < 20 && $this->extended === false) {
            $this->stopStream();
            $this->ls->google->getGoogleService()->doorbell->extendLivestream($this->ls);
            $this->startStream();
            $this->extended = true;
            $this->fire(self::EVENT_EXTEND);
            //$this->runCallbacks($this->extendCallbacks, [ $this->ls ]);
            Loop::futureTick(function () {
                $this->extended = false;
            });
        }
    }

    public function stop()
    {
        if ($this->isStreaming()) {
            $this->stopStream();
        }
        $this->timer->stop();
        Loop::stop();
        $this->fire(self::EVENT_STOP);
    }

    protected function resetSplitTime()
    {
        $this->splitTime = config('google.smd.recording.split_time');
    }

    protected function split()
    {
        $this->resetSplitTime();
        $this->stopStream();
        $this->currentRecord->stop();
        $this->currentRecord->saveInfo();
        $this->recordings[] = $this->currentRecord;
        $this->mainFilePath = null;
        $this->tempFilePath = null;
        $this->startStream();
        $this->currentRecord = new Record($this->mainFilePath);
        $this->currentRecord->start();
        $this->fire(self::EVENT_SPLIT);
    }

    protected function joinFiles()
    {
        $tmpMainFilePath = $this->mainFilePath . '.tmp.avi';
        File::move($this->mainFilePath, $tmpMainFilePath);
        $file1 = Path::getFilename($tmpMainFilePath);
        $file2 = Path::getFilename($this->tempFilePath);

        $textFilePath = Path::join($this->paths->getFileDirectoryPath(), 'inputs.txt');
        if (File::exists($textFilePath)) {
            File::delete($textFilePath);
        }
        File::put($textFilePath, "file {$file1}\nfile {$file2}");

        $command = "ffmpeg -f concat -i inputs.txt -vcodec copy -acodec copy -y {$this->mainFilePath}";
        $process = new Process(explode(' ', $command));
        $process
            ->setWorkingDirectory($this->paths->getFileDirectoryPath())
            ->setTimeout(null)
            ->setIdleTimeout(null)
            ->enableOutput()
            ->run();

        File::delete($tmpMainFilePath);
        if (File::exists($textFilePath)) {
            File::delete($textFilePath);
        }

        File::delete($this->tempFilePath);
        $this->tempFilePath = null;
    }

    protected function startStream()
    {
        if ($this->mainFilePath === null) {
            $filePath = $this->mainFilePath = $this->paths->resolveFilePath();
        } elseif ($this->tempFilePath === null) {
            $filePath = $this->tempFilePath = $this->paths->getRandomFilePath('tmp.avi');
        } else {
            $this->joinFiles();
            return $this->startStream();
        }

        $command             = "ffmpeg -i {$this->ls->url} -acodec copy -vcodec copy {$filePath}";
        $this->streamProcess = new Process(explode(' ', $command));
        $this->streamProcess
            ->setTimeout(null)
            ->setIdleTimeout(null)
            ->disableOutput();

        $this->streamProcess->start();
    }

    protected function isStreaming()
    {
        return $this->streamProcess->isRunning();
    }

    protected function stopStream()
    {
        $this->streamProcess->stop(1);
    }

    public function getTicks()
    {
        return $this->ticks;
    }

    public function on(string $event, callable $listener)
    {
        $this->events->listen($event, $listener);
        return $this;
    }

    public function off(string $event)
    {
        $this->events->forget($event);
        return $this;
    }

    protected function fire(string $event, array $payload = [])
    {
        $this->events->dispatch($event, $payload);
        return $this;
    }

    public function setSplitTime(int $splitTime)
    {
        $this->splitTime = $splitTime;
        return $this;
    }

    public function getSplitTime(): int
    {
        return $this->splitTime;
    }

    public function getMainFilePath()
    {
        return $this->mainFilePath;
    }

    public function getTempFilePath()
    {
        return $this->tempFilePath;
    }

    public function getLiveStream(): LiveStream
    {
        return $this->ls;
    }

    public function getRecordings()
    {
        return $this->recordings;
    }

    public function getCurrentRecord(): Record
    {
        return $this->currentRecord;
    }

    public function getPreviousRecord(): Record
    {
        return last($this->recordings);
    }

}

/*

1. call: doorbell->startlivestream() -> url, expiresAt, extensionToken, token
2. start a 1 sec tick loop.
3. start process ffmpeg to stream rstp url to temporary directory file a.
4. when expiresAt.
5.  1. stop process ffmpeg
    2. call: doorbell->extendlivestream(extensionToken) -> expiresAt, extensionToken, token
    3. update url with new token
    4. start process ffmpeg to stream rstp url to temporary directory file b.
6. when tick loop reaches config(split_time) seconds
    1. remember all files in temporary directory
    2. execute point 5
    1. use ffmpeg to join remembered files from temporary directory into 1 file
    2. place and name the file as the parsed template filepath string dictates
ffmpeg -f concat -i inputs.txt -vcodec copy -acodec copy all.avi
ffmpeg -i $url -acodec copy -vcodec copy $filePath
 */
