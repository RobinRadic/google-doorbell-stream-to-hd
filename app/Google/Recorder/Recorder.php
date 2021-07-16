<?php

namespace App\Google\Recorder;

use App\Models\LiveStream;
use Illuminate\Support\Facades\File;
use React\EventLoop\Loop;
use Symfony\Component\Process\Process;
use Webmozart\PathUtil\Path;

class Recorder
{
    protected int $ticks = 0;

    protected Process $streamProcess;

    protected PathManager $paths;

    protected array $startCallbacks = [];

    protected array $tickCallbacks = [];

    protected array $stopCallbacks = [];

    protected array $extendCallbacks = [];

    protected bool $extended = false;

    protected int $splitTime;

    protected $mainFilePath;

    protected $tempFilePath;

    public function __construct(protected LiveStream $ls)
    {
        $this->paths = new PathManager();
        $this->paths->setDevice($ls->getDevice());
    }

    public function start()
    {
        Loop::addPeriodicTimer(1, [ $this, 'tick' ]);
        $this->resetSplitTime();
        $this->startStream();
        $this->runCallbacks($this->startCallbacks, [ $this->ls ]);
        Loop::run();
    }

    public function tick()
    {
        $this->ticks++;
        $this->splitTime--;
        $this->runCallbacks($this->tickCallbacks, [ $this->ls ]);
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
            $this->runCallbacks($this->extendCallbacks, [ $this->ls ]);
            Loop::futureTick(function () {
                $this->extended = false;
            });
        }
    }

    public function stop()
    {
        Loop::stop();
        $this->runCallbacks($this->stopCallbacks, [ $this->ls ]);
    }

    protected function resetSplitTime()
    {
        $this->splitTime = config('google.smd.recording.split_time');
    }

    protected function split()
    {
        $this->resetSplitTime();
        $this->stopStream();
        $this->mainFilePath=null;
        $this->tempFilePath=null;
        $this->startStream();
    }

    protected function joinFiles()
    {
        $tmpMainFilePath = $this->mainFilePath . '.tmp.avi';
        File::move($this->mainFilePath, $tmpMainFilePath);
        $file1 = Path::getFilename($tmpMainFilePath);
        $file2 = Path::getFilename($this->tempFilePath);

        $textFilePath = Path::join($this->paths->getDirectoryPath(), 'inputs.txt');
        if (File::exists($textFilePath)) {
            File::delete($textFilePath);
        }
        File::put($textFilePath, "file {$file1}\nfile {$file2}");

        $command = "ffmpeg -f concat -i inputs.txt -vcodec copy -acodec copy -y {$this->mainFilePath}";
        $process = new Process(explode(' ', $command));
        $process
            ->setWorkingDirectory($this->paths->getDirectoryPath())
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

    protected function stopStream()
    {
        $this->streamProcess->stop(1);
    }

    public function getTicks()
    {
        return $this->ticks;
    }

    public function onStart(callable $callback)
    {
        $this->startCallbacks[] = $callback;
        return $this;
    }

    public function onTick(callable $callback)
    {
        $this->tickCallbacks[] = $callback;
        return $this;
    }

    public function onExtend(callable $callback)
    {
        $this->extendCallbacks[] = $callback;
        return $this;
    }

    public function onStop(callable $callback)
    {
        $this->stopCallbacks[] = $callback;
        return $this;
    }

    /**
     * @param array|callable[] $callbacks
     * @param array            $arguments
     * @return void
     */
    protected function runCallbacks(array $callbacks, array $arguments = [])
    {
        foreach ($callbacks as $callback) {
            $callback(...$arguments);
        }
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
