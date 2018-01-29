<?php

class ActesRetriever {

    private $actes_files_upload_root;

    public function __construct($actes_files_upload_root) {
        $this->actes_files_upload_root = $actes_files_upload_root;
    }

    public function getPath($acte_path){
        return rtrim($this->actes_files_upload_root,'/') .
            "/" .
            ltrim($acte_path,'/');
    }

}