<?php

class ActesResponsesError {

    private $actes_response_error_path;
    private $tmpFolder;

    public function __construct($actes_response_error_path,TmpFolder $tmpFolder) {
        $this->actes_response_error_path = $actes_response_error_path;
        $this->tmpFolder = $tmpFolder;
    }

    public function getNbError(){
        $fi = $this->getFilesystemIterator();
        return iterator_count($fi);
    }

    public function getFilesystemIterator(){
        return new FilesystemIterator($this->actes_response_error_path, FilesystemIterator::SKIP_DOTS);
    }

    public function getFilepath($filename){
        $filepath = realpath($this->actes_response_error_path."/".$filename);

        if (dirname($filepath) != $this->actes_response_error_path){
            throw new Exception("Impossible de lire le fichier.");
        }

        if (! file_exists($filepath)){
            throw new Exception("Le fichier $filename n'existe pas.");
        }
        return $filepath;
    }

    public function delete($filename){
        $filepath = $this->getFilepath($filename);
        $this->tmpFolder->delete($filepath);
    }

    public function download($filename){
        $filepath = $this->getFilepath($filename);
        $output_directory = $this->tmpFolder->create();
        $pharData = new \PharData($output_directory."/$filename.tar");

        foreach(glob("$filepath/*") as $file) {
            $pharData->addFile($file, basename($file));
        }

        $pharData->compress(\Phar::GZ);
        unlink($output_directory."/$filename.tar");

        header("Content-type: application/tar+gzip;");
        header("Content-disposition: attachment;filename=$filename.tar.gz");
        readfile($output_directory."/$filename.tar.gz");
    }

}