<?php

require_once __DIR__."/../../../../../../public.ssl/modules/mail/lib/MailHeader.class.php";

class MailHeaderTest extends S2lowTestCase{
    public function testDefaults(){
        $mailHeader = new MailHeader("subject","fromM@il","description");
        $header = $mailHeader->getHeader();

        $this->assertEquals("description <fromM@il>",$header['From']);
        $this->assertEquals("subject",$header['Subject']);
        $this->assertEquals("fromM@il",$header['Reply-To']);
        $this->assertEquals("fromM@il",$header['Return-path']);
    }

    public function testSetSubjet(){
        $mailHeader = new MailHeader("subject","fromM@il","description");
        $mailHeader->setSubjet("subjectModified");
        $header = $mailHeader->getHeader();

        $this->assertEquals("description <fromM@il>",$header['From']);
        $this->assertEquals("subjectModified",$header['Subject']);
        $this->assertEquals("fromM@il",$header['Reply-To']);
        $this->assertEquals("fromM@il",$header['Return-path']);
    }

    public function testSetFrom(){
        $mailHeader = new MailHeader("subject","fromM@il","description");
        $mailHeader->setFromMail('fromM@ilmodifi.ed');
        $header = $mailHeader->getHeader();

        $this->assertEquals("description <fromM@ilmodifi.ed>",$header['From']);
        $this->assertEquals("subject",$header['Subject']);
        $this->assertEquals("fromM@il",$header['Reply-To']);
        $this->assertEquals("fromM@il",$header['Return-path']);
    }

    public function testSetReplyToMail(){
        $mailHeader = new MailHeader("subject","fromM@il","description");
        $mailHeader->setReplyToMail('replyToM@a.il');
        $header = $mailHeader->getHeader();

        $this->assertEquals("description <fromM@il>",$header['From']);
        $this->assertEquals("subject",$header['Subject']);
        $this->assertEquals("replyToM@a.il",$header['Reply-To']);
        $this->assertEquals("replyToM@a.il",$header['Return-path']);
    }
}