<?php

namespace S2lowLegacy\Class;

class Mailer
{
    protected const FILESIZE_LIMIT =  10485760; /* 10 Mio */

    protected $recipients;
    protected $lastError;
    protected $fichier;
    protected $dataAsFile;

    public function __construct()
    {
        $this->recipients = array();
        $this->fichier = array();
        $this->dataAsFile = array();
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function addFile($file)
    {
        $this->fichier[] = $file;
    }

    public function addStringAsFile($filename, $data)
    {
        $this->dataAsFile[] = array('filename' => $filename,'data' => $data);
    }
}
