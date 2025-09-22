<?php

namespace S2lowLegacy\Mail;

interface IMailHeader
{
    public function getFromEnveloppeAdressOption(): string;

    public function getHeader(): array;
}
