<?php

require_once __DIR__."/../../../../../../public.ssl/modules/mail/lib/MailHeader.class.php";

class MailHeaderTest extends S2lowTestCase{
    public function testDefaults(){
        $mailHeader = new MailHeader("subject","fromM@il","description");
        $header = $mailHeader->getHeader();

        $this->assertEquals($header['From'],"description <fromM@il>");
        $this->assertEquals($header['Subject'],"subject");
        $this->assertEquals($header['Reply-To'],"fromM@il");
        $this->assertEquals($header['Return-path'],"fromM@il");
    }

    public function testSetSubjet(){
        $mailHeader = new MailHeader("subject","fromM@il","description");
        $mailHeader->setSubjet("subjectModified");
        $header = $mailHeader->getHeader();

        $this->assertEquals($header['From'],"description <fromM@il>");
        $this->assertEquals($header['Subject'],"subjectModified");
        $this->assertEquals($header['Reply-To'],"fromM@il");
        $this->assertEquals($header['Return-path'],"fromM@il");
    }

    public function testSetFrom(){
        $mailHeader = new MailHeader("subject","fromM@il","description");
        $mailHeader->setFromMail('fromM@ilmodifi.ed');
        $header = $mailHeader->getHeader();

        $this->assertEquals($header['From'],"description <fromM@ilmodifi.ed>");
        $this->assertEquals($header['Subject'],"subject");
        $this->assertEquals($header['Reply-To'],"fromM@il");
        $this->assertEquals($header['Return-path'],"fromM@il");
    }

    public function testSetReplyToMail(){
        $mailHeader = new MailHeader("subject","fromM@il","description");
        $mailHeader->setReplyToMail('replyToM@a.il');
        $header = $mailHeader->getHeader();

        $this->assertEquals($header['From'],"description <fromM@il>");
        $this->assertEquals($header['Subject'],"subject");
        $this->assertEquals($header['Reply-To'],"replyToM@a.il");
        $this->assertEquals($header['Return-path'],"replyToM@a.il");
    }
}