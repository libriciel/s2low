<?php

namespace S2lowLegacy\Class\actes;

use Exception;
use FilesystemIterator;
use S2lowLegacy\Class\ActesWorkspace;
use S2lowLegacy\Class\TmpFolder;

class ActesResponsesError
{
    private $tmpFolder;

    public function __construct(
        TmpFolder $tmpFolder,
        private readonly ActesWorkspace $workspace
    ) {
        $this->tmpFolder = $tmpFolder;
    }

    public function getNbError()
    {
        $fi = $this->getFilesystemIterator();
        return iterator_count($fi);
    }

    public function getFilesystemIterator()
    {
        return new FilesystemIterator($this->workspace->getResponseErrorPath(), FilesystemIterator::SKIP_DOTS);
    }

    /**
     * @param $filename
     * @return bool|string
     * @throws Exception
     */
    public function getFilepath($filename)
    {
        $filepath = realpath($this->workspace->getResponseErrorPath() . "/" . $filename);

        if (dirname($filepath) != $this->workspace->getResponseErrorPath()) {
            throw new Exception('Impossible de lire le fichier.');
        }

        if (! file_exists($filepath)) {
            throw new Exception("Le fichier $filename n'existe pas.");
        }
        return $filepath;
    }

    /**
     * @param $filename
     * @throws Exception
     */
    public function delete($filename)
    {
        $filepath = $this->getFilepath($filename);
        $this->tmpFolder->delete($filepath);
    }

    /**
     * @param $filename
     * @throws Exception
     */
    public function download($filename)
    {
        $filepath = $this->getFilepath($filename);
        $output_directory = $this->tmpFolder->create();
        $pharData = new \PharData($output_directory . "/$filename.tar");

        foreach (glob("$filepath/*") as $file) {
            $pharData->addFile($file, basename($file));
        }

        $pharData->compress(\Phar::GZ);
        unlink($output_directory . "/$filename.tar");

        header_wrapper("Content-type: application/tar+gzip;");
        header_wrapper("Content-disposition: attachment;filename=$filename.tar.gz");
        readfile($output_directory . "/$filename.tar.gz");
        $this->tmpFolder->delete($output_directory);
    }
}
