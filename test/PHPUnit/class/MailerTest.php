<?php

use PHPUnit\Framework\TestCase;
use S2low\Services\MailActesNotifications\MailerSymfony;

class MailerTest extends S2lowTestCase
{
    public function testIsValidMail()
    {
        $mailer = self::getContainer()->get(MailerSymfony::class);
        $this->assertTrue($mailer->isValidMail("noreply@libriciel.coop"));
        $this->assertTrue($mailer->isValidMail("test <noreply@libriciel.coop>"));
        $this->assertFalse($mailer->isValidMail("test <noreply@libriciel.coop> ; test3 <noreply2@libriciel.coop> "));
        $this->assertFalse($mailer->isValidMail("aaa"));
        $this->assertFalse($mailer->isValidMail("a>>aa&&@@<<<<"));
        $this->assertFalse($mailer->isValidMail("dtrcv@localhost"));
    }
}
