<?php

namespace S2lowLegacy\Class\actes;

class ActesArchive
{
    private $tmpFolder;
    private $fileContent;
    private $file;

    public function __construct($tmp_folder)
    {
        $this->tmpFolder = $tmp_folder;
    }

    public function addContent($fileName, $fileContent)
    {
        $this->fileContent[] = array($fileName,$fileContent);
    }

    public function addFile($fileName, $filePath)
    {
        $this->file[] = array($fileName,$filePath);
    }

    public function getFileName($enveloppe_filename)
    {
        return ACTES_APPLI_TRIGRAMME . '-' . preg_replace("/^(.*)\.([^.]+)$/", "\${1}", $enveloppe_filename) . '.tar.gz';
    }

    public function generate($enveloppe_filename)
    {
        $tmp_f = $this->tmpFolder . "/" . uniqid();
        mkdir($tmp_f);
        $file2Tar = array();
        foreach ($this->fileContent as $file) {
            file_put_contents($tmp_f . "/" . $file[0], $file[1]);
            $file2Tar[] = $file[0];
        }
        foreach ($this->file as $file) {
            copy($file[1], $tmp_f . "/" . $file[0]);
            $file2Tar[] = $file[0];
        }


        $destination_file =  $this->tmpFolder . $this->getFileName($enveloppe_filename);

        $command = "tar cvzf $destination_file --directory $tmp_f " . implode(" ", $file2Tar);

        $status = exec($command);
        foreach ($file2Tar as $file) {
            unlink($tmp_f . "/" . $file);
        }

        rmdir($tmp_f);
        return $destination_file;
    }
}
