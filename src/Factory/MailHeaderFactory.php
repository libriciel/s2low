<?php

namespace S2low\Factory;

use S2lowLegacy\Mail\IMailHeader;
use S2lowLegacy\Mail\MailHeader;
use S2lowLegacy\Mail\MailHeaderLegacy;

class MailHeaderFactory
{
    public function __construct(
        private readonly MailHeader $mailHeader,
        private readonly MailHeaderLegacy $mailHeaderLegacy
    ) {
    }

    public function create($useLegacySecureMailFields): IMailHeader
    {
        return match ($useLegacySecureMailFields) {
            true => $this->mailHeaderLegacy,
            default => $this->mailHeader,
        };
    }
}
