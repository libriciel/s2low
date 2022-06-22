<?php

class MailerFactory
{
    public function getInstance()
    {
        return new Mailer();
    }
}
