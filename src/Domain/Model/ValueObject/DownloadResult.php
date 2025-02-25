<?php

namespace S2low\Domain\Model\ValueObject;

class DownloadResult
{
    public function __construct(
        private readonly bool $success,
        private readonly ?string $localPath = null,
        private readonly ?string $errorMessage = null
    ) {

    }

    public function isSuccess(): bool { return $this->success; }
    public function getLocalPath(): ?string { return $this->localPath; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
}
