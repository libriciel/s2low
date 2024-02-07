<?php

declare(strict_types=1);

namespace PHPUnit\public_ssl\module\mail\lib;

use MailHeaderLegacy;
use PHPUnit\S2lowTestCase;

class MailHeaderLegacyTest extends S2lowTestCase
{
    public function testDefaults()
    {
        $mailHeader = new MailHeaderLegacy("subject", "from", "unused_parameter");
        $header = $mailHeader->getHeader();

        $this->assertEquals("from", $header['From']);
        $this->assertEquals("subject", $header['Subject']);
        $this->assertEquals("from", $header['Reply-To']);
        $this->assertEquals("from", $header['Return-path']);
    }

    public function testSetSubjet()
    {
        $mailHeader = new MailHeaderLegacy("subject", "from", "unused_parameter");
        $mailHeader->setAuthorityName("name");
        $header = $mailHeader->getHeader();

        $this->assertEquals("from", $header['From']);
        $this->assertEquals("[name] subject", $header['Subject']);
        $this->assertEquals("from", $header['Reply-To']);
        $this->assertEquals("from", $header['Return-path']);
    }

    public function testSetFrom()
    {
        $mailHeader = new MailHeaderLegacy("subject", "from", "unused_parameter");
        $mailHeader->setFromMail('fromModified');
        $header = $mailHeader->getHeader();

        $this->assertEquals("fromModified", $header['From']);
        $this->assertEquals("subject", $header['Subject']);
        $this->assertEquals("fromModified", $header['Reply-To']);
        $this->assertEquals("fromModified", $header['Return-path']);
    }
}
