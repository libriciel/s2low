<?php

namespace S2low\Factory;

use Legacy\IMailHeader;
use Legacy\MailHeader;
use Legacy\MailHeaderLegacy;

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
