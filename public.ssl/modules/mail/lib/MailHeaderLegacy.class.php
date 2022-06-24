<?php

class MailHeaderLegacy extends AbstractMailHeader implements IMailHeader
{
    private function getFromField(): string
    {
        return $this->fromMail;
    }

    public function getFromEnveloppeAdressOption(): string
    {
        return "-f{$this->fromMail}";
    }

    public function getHeader(): array
    {
        return array(
            'From'    => $this->getFromField(),
            'Subject' => $this->getSubject(),
            'Reply-To' => $this->getFromField(),
            'Return-path' => $this->getFromField()
        );
    }
}
