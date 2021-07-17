<?php

namespace App\Google\Recorder;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use JsonSerializable;
use Webmozart\PathUtil\Path;

class Record implements Arrayable, Jsonable, JsonSerializable
{
    public const DURATION_SECONDS = 'seconds';
    public const DURATION_MINUTES = 'minutes';
    public const DURATION_HOURS = 'hours';

    protected PathManager $paths;

    protected ?Carbon $startTime;

    protected ?Carbon $endTime = null;

    public static string $timeFormat = 'Y-m-d H:i:s';

    public function __construct(protected string $filePath)
    {
        $this->paths = App::make(PathManager::class);
    }

    public function start()
    {
        $this->startTime = now();
    }

    public function stop()
    {
        $this->endTime = now();
    }

    public function isRecording()
    {
        return $this->endTime === null;
    }

    public function isStopped()
    {
        return $this->endTime !== null;
    }

    public function getStartTime(): Carbon
    {
        return $this->startTime;
    }

    public function getEndTime(): Carbon
    {
        return $this->endTime;
    }

    public function getFormattedStartTime(): string
    {
        return $this->startTime->format(static::$timeFormat);
    }

    public function getFormattedEndTime(): string
    {
        return $this->endTime->format(static::$timeFormat);
    }

    public function getDuration(string $notation = self::DURATION_MINUTES)
    {
        $endTime = $this->isStopped() ? $this->endTime : now();
        switch ($notation) {
            case self::DURATION_SECONDS:
                return $this->startTime->diffInSeconds($endTime);
            case self::DURATION_MINUTES:
                return $this->startTime->diffInMinutes($endTime);
            case self::DURATION_HOURS:
                return $this->startTime->diffInHours($endTime);
            default:
                throw new \RuntimeException("Invalid notation");
        }
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

    public function getFilename(bool $withoutExtension = false)
    {
        return $withoutExtension ? Path::getFilenameWithoutExtension($this->filePath) : Path::getFilename($this->filePath);
    }

    public function getFileDirectory()
    {
        return Path::getDirectory($this->filePath);
    }

    public function getFileExtension()
    {
        return Path::getExtension($this->filePath);
    }

    public function saveInfo()
    {
        $filePath = Path::join($this->paths->getInfoDirectoryPath(), $this->getFilename(true) . '.json');
        $this->saveAsJsonFile($filePath);
        return $filePath;
    }

    public function saveAsJsonFile(string $filePath, bool $overwrite = true)
    {
        if (File::exists($filePath)) {
            if ($overwrite) {
                File::delete($filePath);
            } else {
                throw new \RuntimeException("Overwriting an existing file is not allowed");
            }
        }
        File::put($filePath, $this->toJson(JSON_PRETTY_PRINT));
        return $this;
    }

    public function toArray()
    {
        return [
            'is_recording'     => $this->isRecording(),
            'duration_seconds' => $this->getDuration(self::DURATION_SECONDS),
            'duration_minutes' => $this->getDuration(self::DURATION_MINUTES),
            'start_time'       => $this->getFormattedStartTime(),
            'end_time'         => $this->isRecording() ? null : $this->getFormattedEndTime(),
            'path'             => $this->getFilePath(),
        ];
    }

    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), JSON_THROW_ON_ERROR | $options);
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public static function fromJson(string $json)
    {
        $data   = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $record = new static($data[ 'path' ]);

        $startTime = Carbon::createFromFormat(static::$timeFormat, $data[ 'start_time' ]);
        if ($startTime === false) {
            throw new \RuntimeException("Invalid data to create Record. Start time invalid");
        }
        $record->startTime = $startTime == false ? null : $startTime;

        $endTime = null;
        if ($data[ 'end_time' ] !== null) {
            $endTime = Carbon::createFromFormat(static::$timeFormat, $data[ 'end_time' ]);
        }
        $record->endTime = $endTime;
        return $record;
    }

    public static function fromJsonFile($filepath)
    {
        return static::fromJson(File::get($filepath));
    }
}
