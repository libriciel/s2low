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
        $command = "tar xvzf $archivePath --directory {$this->tmpFolder} $name";
        $status = exec($command);
        if (! $status) {
            return false;
        }
        return true;
    }
}
