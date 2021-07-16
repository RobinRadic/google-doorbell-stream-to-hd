<?php

namespace App\Google\Recorder;

use App\Models\LiveStream;
use Illuminate\Support\Facades\File;
use React\EventLoop\Loop;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;
use Webmozart\PathUtil\Path;

class RecorderLoop
{
    protected int $ticks = 0;

    protected Process $streamProcess;

    protected PathManager $paths;

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
        Loop::run();
    }

    protected $extended = false;

    public function tick()
    {
        $this->ticks++;
        $this->splitTime--;
        if ($this->splitTime < 2) {
            $this->joinAndMoveFiles();
            $this->resetSplitTime();
        }

        $expiresIn = $this->ls->getSecondsUntilExpires();
        if ($expiresIn < 10 && $this->extended === false) {
            $this->stopStream();
            $this->ls->google->getGoogleService()->doorbell->extendLivestream($this->ls);
            $this->startStream();
            $this->extended = true;
            Loop::futureTick(function () {
                $this->extended = false;
            });
        }
    }

    public function stop()
    {

    }

    protected int $splitTime;

    protected function resetSplitTime()
    {
        $this->splitTime = config('google.smd.recording.command_variables.split_time');
    }

    protected array $files = [];

    protected string $currentFile;

    protected function joinAndMoveFiles()
    {
        $this->stopStream();
        $content      = implode("\n", array_map(function ($filePath) {
            $fileName = Path::getFilename($filePath);
            return "file {$fileName}";
        }, $this->files));
        $textFilePath = Path::join($this->paths->getTemporaryDirPath(), 'inputs.txt');
        if (File::exists($textFilePath)) {
            File::delete($textFilePath);
        }
        File::put($textFilePath, $content);
        $destFilePath = $this->paths->resolveFilePath();
        $command      = "ffmpeg -f concat -i {$textFilePath} -vcodec copy -acodec copy {$destFilePath}";
        $process      = new Process(explode(' ', $command));
        $process
            ->setTimeout(null)
            ->setIdleTimeout(null)
            ->disableOutput()
            ->run();
        $this->files = [];
        $this->startStream();
    }

    protected function startStream()
    {
        $this->currentFile = $this->paths->getTemporaryRandomFilePath();
        $this->files[]     = $this->currentFile;
        $command           = "ffmpeg -i {$this->ls->url} -acodec copy -vcodec copy {$this->currentFile}";
        try {
            $this->streamProcess = new Process(explode(' ', $command));
            $this->streamProcess
                ->setTimeout(null)
                ->setIdleTimeout(null)
                ->disableOutput();

            $this->streamProcess->start();
        }
        catch (LogicException $exception) {
            throw $exception;
        }
        catch (ProcessFailedException $exception) {
            throw $exception;
        }
        catch (RuntimeException $exception) {
            throw $exception;
        }
        catch (Throwable $exception) {
            throw $exception;
        }
    }

    protected function stopStream()
    {
        $this->streamProcess->stop(1);
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
