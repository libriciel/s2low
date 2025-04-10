<?php

namespace S2lowLegacy\Class;

use org\bovigo\vfs\vfsStream;

class ActesWorkspaceForTests implements IActesWorkspace
{
    private TmpFolder $tmpFolder;
    private string $actes_files_upload_root;
    private string $actes_response_tmp_local_path;
    private string $actes_response_error_path;
    private string $repertoireActesEnveloppeSansTransaction;

    public function __construct()
    {
        $this->tmpFolder = new TmpFolder();

        $this->actes_files_upload_root = $this->tmpFolder->create();
        $this->actes_response_tmp_local_path = $this->tmpFolder->create();
        $this->actes_response_error_path = $this->tmpFolder->create();
        $this->repertoireActesEnveloppeSansTransaction = $this->tmpFolder->create();
    }

    public function getRepertoireEnveloppeSansTransaction(): string
    {
        return $this->repertoireActesEnveloppeSansTransaction;
    }

    public function getFilesUploadRoot(): string
    {
        return $this->actes_files_upload_root;
    }

    public function getResponseTmpLocalPath(): string
    {
        return $this->actes_response_error_path;
    }

    public function getResponseErrorPath(): string
    {
        return $this->actes_response_tmp_local_path;
    }

    public function clear(): void
    {
        $this->tmpFolder->delete($this->actes_files_upload_root);
        $this->tmpFolder->delete($this->actes_response_tmp_local_path);
        $this->tmpFolder->delete($this->actes_response_error_path);
        $this->tmpFolder->delete($this->repertoireActesEnveloppeSansTransaction);
    }
}
