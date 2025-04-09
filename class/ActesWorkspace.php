<?php

namespace S2lowLegacy\Class;

class ActesWorkspace implements IActesWorkspace
{
    public function __construct(
        private string $actes_files_upload_root,
        private string $actes_response_tmp_local_path,
        private string $actes_response_error_path,
        private string $repertoireActesEnveloppeSansTransaction,
    ) {
    }

    public function getRepertoireActesEnveloppeSansTransaction(): string
    {
        return $this->repertoireActesEnveloppeSansTransaction;
    }
    public function getActesFilesUploadRoot(): string
    {
        return $this->actes_files_upload_root;
    }

    public function getActesResponseTmpLocalPath(): string
    {
        return $this->actes_response_tmp_local_path;
    }

    public function getActesResponseErrorPath(): string
    {
        return $this->actes_response_error_path;
    }
}
