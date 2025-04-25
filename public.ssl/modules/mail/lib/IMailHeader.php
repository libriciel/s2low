<?php

namespace S2lowLegacy\Mail;

interface IMailHeader
{
    public function setAuthorityName(string $name): void;

    public function setFromMail(string $fromMail): void;

    public function setFromDescription(string $fromDescription): void;

    public function getFromEnveloppeAdressOption(): string;

    public function getHeader(): array;
}
