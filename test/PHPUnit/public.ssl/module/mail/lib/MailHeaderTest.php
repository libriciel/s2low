<?php

require_once __DIR__."/../../../../../../public.ssl/modules/mail/lib/MailHeader.class.php";

class MailHeaderTest extends S2lowTestCase{
    public function testDefaults(){
        $mailHeader = new MailHeader("subject","s2lowFromM@il","description");
        $header = $mailHeader->getHeader();

        $this->assertEquals("description <s2lowFromM@il>",$header['From']);
        $this->assertEquals("subject",$header['Subject']);
        $this->assertEquals("s2lowFromM@il",$header['Reply-To']);
        $this->assertEquals("s2lowFromM@il",$header['Return-path']);
    }

    public function testSetSubjet(){
        $mailHeader = new MailHeader("subject","s2lowFromM@il","description");
        $mailHeader->setAuthorityName("name");
        $header = $mailHeader->getHeader();

        $this->assertEquals("description <s2lowFromM@il>",$header['From']);
        $this->assertEquals("[name] subject",$header['Subject']);
        $this->assertEquals("s2lowFromM@il",$header['Reply-To']);
        $this->assertEquals("s2lowFromM@il",$header['Return-path']);
    }

    public function testSetFrom(){
        $mailHeader = new MailHeader("subject","s2lowFromM@il","description");
        $mailHeader->setFromMail('fromM@ilmodifi.ed');
        $header = $mailHeader->getHeader();

        $this->assertEquals("description <s2lowFromM@il>",$header['From']);
        $this->assertEquals("subject",$header['Subject']);
        $this->assertEquals("fromM@ilmodifi.ed",$header['Reply-To']);
        $this->assertEquals("fromM@ilmodifi.ed",$header['Return-path']);
    }
}