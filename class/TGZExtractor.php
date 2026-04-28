<?php

namespace S2lowLegacy\Class;

class TGZExtractor
{
    private $tmpFolder;

    public function __construct($tmpFolder)
    {
        $this->tmpFolder = $tmpFolder;
    }

    public function extract($archivePath, $name)
    {
        $archivePathEscaped = escapeshellarg($archivePath);
        $nameEscaped = escapeshellarg($name);

        $command = "tar xvzf $archivePathEscaped --directory {$this->tmpFolder} $nameEscaped";
        $status = exec($command);
        if (! $status) {
            return false;
        }
        return true;
    }
}
