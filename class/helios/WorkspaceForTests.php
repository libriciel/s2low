<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Class\TmpFolder;

class WorkspaceForTests implements IWorkspace
{
    private TmpFolder $tmpFolder;
    private string $helios_files_upload_root;
    private string $repertoirePesAllerSansTransaction;
    private string $helios_responses_root;
    private string $helios_responses_error_path;
    private $helios_ftp_response_tmp_local_path;
    private $helios_ocre;

    public function __construct()
    {
        $this->tmpFolder = new TmpFolder();

        $this->helios_files_upload_root = $this->tmpFolder->create();
        $this->helios_responses_root = $this->tmpFolder->create() . '/';
        $this->helios_responses_error_path = $this->tmpFolder->create();
        $this->repertoirePesAllerSansTransaction = $this->tmpFolder->create();
        $this->helios_ftp_response_tmp_local_path = $this->tmpFolder->create();
        $this->helios_ocre = $this->tmpFolder->create() . '/';
    }


    public function getHeliosFilesUploadRoot()
    {
        return $this->helios_files_upload_root;
    }

    public function getRepertoirePesAllerSansTransaction()
    {
        return $this->repertoirePesAllerSansTransaction;
    }

    public function getHeliosResponsesRoot()
    {
        return $this->helios_responses_root;
    }

    public function getHeliosResponsesErrorPath()
    {
        return $this->helios_responses_error_path;
    }

    public function getHeliosFtpResponseTmpLocalPath()
    {
        return $this->helios_ftp_response_tmp_local_path;
    }

    public function getHeliosOcre()
    {
        return $this->helios_ocre;
    }

    public function clear()
    {
        //TODO
    }
}
