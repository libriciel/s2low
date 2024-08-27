<?php

namespace S2lowLegacy\Class\helios;

use Exception;
use FilesystemIterator;

class HeliosResponsesError
{
    private $directory_path ;

    public function __construct()
    {
        $this->setDirectoryPath(HELIOS_RESPONSES_ERROR_PATH);
    }

    public function setDirectoryPath($directory_path)
    {
        $this->directory_path = $directory_path;
    }

    public function getNbError()
    {
        $fi = new FilesystemIterator($this->directory_path, FilesystemIterator::SKIP_DOTS);
        return iterator_count($fi);
    }

    public function getFilesystemIterator()
    {
        return new FilesystemIterator($this->directory_path, FilesystemIterator::SKIP_DOTS);
    }

    public function getFilepath($filename)
    {

        $filepath = realpath($this->directory_path . "/" . $filename);

        if (dirname($filepath) . "/" != $this->directory_path) {
            throw new Exception("Impossible de lire le fichier.");
        }

        if (! file_exists($filepath)) {
            throw new Exception("Le fichier $filename n'existe pas.");
        }
        return $filepath;
    }

    public function display($filename)
    {
        $filepath = $this->getFilepath($filename);
        if (filesize($filepath) == 0) {
            throw new Exception("Le fichier $filename est vide.");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE | FILEINFO_MIME_ENCODING);
        $mime_type = finfo_file($finfo, $filepath);
        finfo_close($finfo);

        header_wrapper("Content-type: $mime_type;");
        readfile($filepath);
    }

    public function delete($filename)
    {
        $filepath = $this->getFilepath($filename);
        unlink($filepath);
    }
}
