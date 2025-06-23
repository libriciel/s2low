<?php

namespace S2low\Services\MailActesNotifications;

use Symfony\Component\Mailer\MailerInterface;

/**
 *
 */
class MailerSymfonyFactory
{
    /**
     * @var \Symfony\Component\Mailer\MailerInterface
     */
    private MailerInterface $mailer;

    /**
     * @param \Symfony\Component\Mailer\MailerInterface $mailer
     */

    public function __construct(
        MailerInterface $mailer,
        private readonly string $tdt_from_email
    ) {
        $this->mailer = $mailer;
    }

    /**
     * @return MailerSymfony
     */
    public function getInstance(): MailerSymfony
    {
        return new MailerSymfony(
            $this->mailer,
            $this->tdt_from_email
        );
    }
}
