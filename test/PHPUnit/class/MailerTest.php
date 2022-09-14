<?php

use S2lowLegacy\Class\Mailer;
use PHPUnit\Framework\TestCase;

class MailerTest extends TestCase
{
    public function testIsValidMail()
    {
        $mailer = new \S2low\Services\MailActesNotifications\MailerSymfony(new \S2low\Tests\Services\MockMailer());
        $this->assertTrue($mailer->isValidMail("noreply@libriciel.coop"));
        $this->assertTrue($mailer->isValidMail("test <noreply@libriciel.coop>"));
        $this->assertFalse($mailer->isValidMail("test <noreply@libriciel.coop> ; test3 <noreply2@libriciel.coop> "));
        $this->assertFalse($mailer->isValidMail("aaa"));
        $this->assertFalse($mailer->isValidMail("a>>aa&&@@<<<<"));
        $this->assertFalse($mailer->isValidMail("dtrcv@localhost"));
    }
}
