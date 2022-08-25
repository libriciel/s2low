<?php

use S2lowLegacy\Class\Mailer;
use PHPUnit\Framework\TestCase;

class MailerTest extends TestCase
{
    public function testIsValidMail()
    {

        $mailer = new Mailer();
        $this->assertTrue($mailer->isValidMail("noreply@libriciel.coop"));
        $this->assertTrue($mailer->isValidMail("test <noreply@libriciel.coop>"));
        $this->assertTrue($mailer->isValidMail("test <noreply@libriciel.coop> , test2 <noreply2@libriciel.coop> "));
        $this->assertFalse($mailer->isValidMail("test <noreply@libriciel.coop> ; test3 <noreply2@libriciel.coop> "));
        $this->assertFalse($mailer->isValidMail("aaa"));
        $this->assertFalse($mailer->isValidMail("a>>aa&&@@<<<<"));
    }
}
