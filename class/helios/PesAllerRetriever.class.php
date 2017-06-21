<?php

class PesAllerRetriever {


    private $helios_files_upload_root;
    private $openStackSwiftWrapper;

    public function __construct($helios_files_upload_root,
        OpenStackSwiftWrapper $openStackSwiftWrapper
    ){
        $this->helios_files_upload_root = $helios_files_upload_root;
        $this->openStackSwiftWrapper = $openStackSwiftWrapper;
    }

    public function getPath($pes_sha1){

        try {
            $result = $this->openStackSwiftWrapper->retrieveFile(
                PesAllerStorage::CONTAINER_NAME,
                $this->helios_files_upload_root . "/" . $pes_sha1
            );
        } catch (Exception $e){
            return false;
        }

        return $result;
    }

    public function getPathForNonExistingFile($pes_sha1){
        return $this->helios_files_upload_root . "/". $pes_sha1;
    }

}