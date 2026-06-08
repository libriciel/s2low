<?php

namespace S2low\DTO;

use S2low\Enum\IncomingFileHandlingStatus;

class PesEntrantHandlingResult
{
    public function __construct(
        private readonly IncomingFileHandlingStatus $status = IncomingFileHandlingStatus::Success,
        private readonly ?string $message = null,
    ) {
    }

    /**
     * @return \S2low\Enum\IncomingFileHandlingStatus
     */
    public function getStatus(): IncomingFileHandlingStatus
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }
}
