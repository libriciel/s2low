<?php

namespace S2lowLegacy\Class;

class ActesWorkspace
{
    public function __construct(
        private string $actes_files_upload_root,
        private string $actes_response_tmp_local_path,
        private string $actes_response_error_path,
        private string $repertoireActesEnveloppeSansTransaction,
    ) {
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
        return $this->actes_response_tmp_local_path;
    }

    public function getResponseErrorPath(): string
    {
        return $this->actes_response_error_path;
    }
}
