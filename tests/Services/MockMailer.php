<?php

namespace S2low\Tests\Services;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

class MockMailer implements MailerInterface
{
    public function send(RawMessage $message, Envelope $envelope = null): void
    {
        // TODO: Implement send() method.
    }
}
