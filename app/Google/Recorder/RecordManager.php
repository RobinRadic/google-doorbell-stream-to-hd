<?php

namespace App\Google\Recorder;

use Illuminate\Support\Facades\File;
use Webmozart\PathUtil\Path;

class RecordManager
{
    protected PathManager $paths;

    protected string $infoPath;

    public function __construct(PathManager $paths)
    {
        $this->paths = $paths;
        $this->infoPath = $paths->getInfoDirectoryPath();
    }

    public function getRecords()
    {
        /** @var \Symfony\Component\Finder\SplFileInfo[]|\Illuminate\Support\Collection $files */
        $files = collect(File::files($this->infoPath));
        $records = $files->map->getPathname()->mapWithKeys(function($filePath){
            $key = Path::getFilenameWithoutExtension($filePath);
            $value = Record::fromJsonFile($filePath);
            return [$key=>$value];
        });
        return $records;
    }
}
