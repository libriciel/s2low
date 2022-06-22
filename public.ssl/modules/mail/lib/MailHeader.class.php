<?php

class MailHeader extends AbstractMailHeader implements IMailHeader
{
    private function getFromField(): string
    {
        return "{$this->fromDescription} <{$this->s2lowFromMail}>";
    }

    private function getFromMail(): string
    {
        return $this->fromMail;
    }

    public function getFromEnveloppeAdressOption(): string
    {
        return "-f{$this->s2lowFromMail}";
    }

    public function getHeader(): array
    {
        return array(
            'From'    => $this->getFromField(),
            'Subject' => $this->getSubject(),
            'Reply-To' => $this->getFromMail(),
            'Return-path' => $this->getFromMail()
        );
    }
}
