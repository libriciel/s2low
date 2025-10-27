<?php

use S2lowLegacy\Model\AuthoritySiretSQL;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosRetourSQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosDirectoriesManager
{
    public $helios_ftp_response_tmp_local_path;
    public $helios_response_root;
    public $helios_ocre;
    public $helios_responses_error_path;

    public string $helios_files_upload_root;
    private string $baseDirectory;

    public function createDirectories()
    {
        $this->baseDirectory = sys_get_temp_dir() . "/" . uniqid("phpunit");
        mkdir($this->baseDirectory);
        $this->helios_ftp_response_tmp_local_path = $this->baseDirectory . "/helios_ftp_response_tmp_local_path/";
        $this->helios_response_root = $this->baseDirectory . "/helios_response_root/";
        $this->helios_responses_error_path = $this->baseDirectory . "/helios_responses_error_path/";
        $this->helios_ocre = $this->baseDirectory . "/helios_ocre/";
        $this->helios_files_upload_root = $this->baseDirectory . "/helios_files_upload_root/";
        mkdir($this->helios_ftp_response_tmp_local_path);
        mkdir($this->helios_response_root);
        mkdir($this->helios_responses_error_path);
        mkdir($this->helios_ocre);
        mkdir($this->helios_files_upload_root);
    }

    public function clearDirectories()
    {
        foreach (
            [$this->helios_ftp_response_tmp_local_path,
                $this->helios_response_root,
                $this->helios_responses_error_path,
                $this->helios_ocre,
                $this->helios_files_upload_root
                ] as $dirname
        ) {
            array_map('unlink', glob("$dirname/*"));
            rmdir($dirname);
        }
        rmdir($this->baseDirectory);
    }
}
