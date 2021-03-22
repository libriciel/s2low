<?php

class MailHeaderLegacy extends AbstractMailHeader Implements IMailHeader{

    private function getFromField() : string
    {
        return $this->fromMail;
    }

    public function getHeader() : array
    {
        return array(
            'From'    => $this->getFromField(),
            'Subject' => $this->getSubject(),
            'Reply-To' => $this->getFromField(),
            'Return-path' => $this->getFromField()
        );
    }
}