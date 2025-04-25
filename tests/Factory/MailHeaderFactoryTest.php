<?php

namespace S2low\Tests\Factory;

use S2low\Factory\MailHeaderFactory;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Mail\MailHeader;
use S2lowLegacy\Mail\MailHeaderLegacy;

class MailHeaderFactoryTest extends TestCase
{
    private const CREATE_LEGACY_SECURE_MAIL = true;
    private const DONT_CREATE_LEGACY_SECURE_MAIL = false;

    public function setup(): void
    {
        $mailHeader = self::createStub(MailHeader::class);
        $mailHeaderLegacy = self::createStub(MailHeaderLegacy::class);

        $this->factory = new MailHeaderFactory(
            $mailHeader,
            $mailHeaderLegacy
        );
    }

    public function testCreateLegacyMailHeader()
    {
        $createdMailHeader = $this->factory->create(
            self::CREATE_LEGACY_SECURE_MAIL
        );
        $this->assertInstanceOf(MailHeaderLegacy::class, $createdMailHeader);
    }

    public function testCreateMailHeader()
    {
        $createdMailHeader = $this->factory->create(
            self::DONT_CREATE_LEGACY_SECURE_MAIL
        );
        $this->assertInstanceOf(MailHeader::class, $createdMailHeader);
    }
}
