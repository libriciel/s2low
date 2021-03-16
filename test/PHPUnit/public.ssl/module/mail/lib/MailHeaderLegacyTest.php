<?php

require_once __DIR__."/../../../../../../public.ssl/modules/mail/lib/MailHeader.class.php";

class MailHeaderLegacyTest extends S2lowTestCase{
    public function testDefaults(){
        $mailHeader = new MailHeaderLegacy("subject","from");
        $header = $mailHeader->getHeader();

        $this->assertEquals($header['From'],"from");
        $this->assertEquals($header['Subject'],"subject");
        $this->assertEquals($header['Reply-To'],"from");
        $this->assertEquals($header['Return-path'],"from");
    }

    public function testSetSubjet(){
        $mailHeader = new MailHeaderLegacy("subject","from");
        $mailHeader->setSubjet("subjectModified");
        $header = $mailHeader->getHeader();

        $this->assertEquals($header['From'],"from");
        $this->assertEquals($header['Subject'],"subjectModified");
        $this->assertEquals($header['Reply-To'],"from");
        $this->assertEquals($header['Return-path'],"from");
    }

    public function testSetFrom(){
        $mailHeader = new MailHeaderLegacy("subject","from");
        $mailHeader->setFromMail('fromModified');
        $header = $mailHeader->getHeader();

        $this->assertEquals($header['From'],"fromModified");
        $this->assertEquals($header['Subject'],"subject");
        $this->assertEquals($header['Reply-To'],"fromModified");
        $this->assertEquals($header['Return-path'],"fromModified");
    }
}
