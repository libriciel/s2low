<?php

namespace S2lowLegacy\Class\helios;

class Workspace implements IWorkspace
{
    public function __construct(
        private readonly string $helios_files_upload_root,
        private readonly string $repertoirePesAllerSansTransaction,
        private readonly string $helios_responses_root,
        private readonly string $helios_responses_error_path
    ) {
    }

    public function getHeliosFilesUploadRoot(): string
    {
        return $this->helios_files_upload_root;
    }

    public function getRepertoirePesAllerSansTransaction(): string
    {
        return $this->repertoirePesAllerSansTransaction;
    }

    public function getHeliosResponsesRoot(): string
    {
        return $this->helios_responses_root;
    }

    public function getHeliosResponsesErrorPath(): string
    {
        return $this->helios_responses_error_path;
    }
}
