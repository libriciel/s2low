<?php

namespace S2lowLegacy\Class\actes;

use Exception;
use S2lowLegacy\Class\ActesWorkspace;
use S2lowLegacy\Class\TmpFolder;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class ActesWorspaceManager
{
    public function __construct(
        private readonly TmpFolder $tmpFolder
    ) {
    }

    /**
     * @throws Exception
     */
    public function get(
        ?string $actes_files_upload_root = null,
        ?string $actes_response_tmp_local_path = null,
        ?string $actes_response_error_path = null,
        ?string $repertoireActesEnveloppeSansTransaction = null,
    ): ActesWorkspace {
        return new ActesWorkspace(
            $this->checkDirCreateIfNull($actes_files_upload_root),
            $this->checkDirCreateIfNull($actes_response_tmp_local_path),
            $this->checkDirCreateIfNull($actes_response_error_path),
            $this->checkDirCreateIfNull($repertoireActesEnveloppeSansTransaction)
        );
    }

    /**
     * @throws Exception
     * @throws FileNotFoundException
     */
    public function checkDirCreateIfNull(?string $dir)
    {
        if (is_null($dir)) {
            return $this->tmpFolder->create();
        }
        if (!is_dir($dir)) {
            throw new FileNotFoundException("Directory '$dir' does not exist");
        }
        return $dir;
    }

    public function delete(ActesWorkspace $actes_workspace)
    {
        $this->tmpFolder->delete($actes_workspace->getFilesUploadRoot());
        $this->tmpFolder->delete($actes_workspace->getResponseErrorPath());
        $this->tmpFolder->delete($actes_workspace->getResponseTmpLocalPath());
        $this->tmpFolder->delete($actes_workspace->getRepertoireEnveloppeSansTransaction());
    }
}
