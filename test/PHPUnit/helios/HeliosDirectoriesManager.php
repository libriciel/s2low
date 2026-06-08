<?php

use PHPUnit\helios\PesTestFile;

class HeliosDirectoriesManager
{
    public const PATH_TO_PES_RETOUR = __DIR__ . '/fixtures/pes_retour.xml';
    public const PATH_TO_PES_RETOUR_NON_ABONNE = __DIR__ . '/fixtures/pes_retour_nonabonne.xml';

    public $helios_ftp_response_tmp_local_path;
    public $helios_response_root;
    public $helios_ocre;
    public $helios_responses_error_path;

    public string $helios_files_upload_root;
    private string $baseDirectory;

    public function createDirectories()
    {
        $this->baseDirectory = sys_get_temp_dir() . '/' . uniqid('phpunit');
        mkdir($this->baseDirectory);
        $this->helios_ftp_response_tmp_local_path = $this->baseDirectory . '/helios_ftp_response_tmp_local_path/';
        $this->helios_response_root = $this->baseDirectory . '/helios_response_root/';
        $this->helios_responses_error_path = $this->baseDirectory . '/helios_responses_error_path/';
        $this->helios_ocre = $this->baseDirectory . '/helios_ocre/';
        $this->helios_files_upload_root = $this->baseDirectory . '/helios_files_upload_root/';
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
    public function putInTmpDirectory(PesTestFile $file)
    {
        file_put_contents(
            $this->helios_ftp_response_tmp_local_path . $file->value,
            file_get_contents($file->getPath())
        );
    }

    public function putInErrorDirectory(PesTestFile $file)
    {
        file_put_contents(
            $this->helios_responses_error_path . $file->value,
            file_get_contents($file->getPath())
        );
    }

    public function isInResponseDirectory(PesTestFile $file)
    {
        return file_exists($this->helios_response_root . '/' . $file->getFilename());
    }

    public function isInTmpDirectory(PesTestFile $file)
    {
        return file_exists($this->helios_ftp_response_tmp_local_path . '/' . $file->getFilename());
    }

    public function isInErrorDirectory(PesTestFile $file)
    {
        return file_exists($this->helios_responses_error_path . '/' . $file->getFilename());
    }

    public function setResponseRootToUnwritable()
    {
        chmod($this->helios_response_root, 0555);
    }
}
