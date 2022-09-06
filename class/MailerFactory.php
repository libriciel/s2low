<?php

namespace S2lowLegacy\Class;

class MailerFactory
{
    public function getInstance()
    {
        return new Mailer();
    }
}
