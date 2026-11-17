<?php

declare(strict_types=1);

namespace S2low\Tests\DTO;

use PHPUnit\Framework\TestCase;
use S2low\DTO\GroupAffiliation;
use S2low\Enum\AdministeredModule;

class GroupAffiliationTest extends TestCase
{
    public function testCaptionNamesTheSingleAdministeredModule(): void
    {
        $affiliation = new GroupAffiliation('Groupe Claude Dev', [AdministeredModule::HELIOS]);

        static::assertSame('Groupe Helios', $affiliation->getCaption());
    }

    public function testCaptionNamesBothModulesWhenTheGroupAdministersThemAll(): void
    {
        $affiliation = new GroupAffiliation('Groupe Claude Dev', [AdministeredModule::ACTES, AdministeredModule::HELIOS]);

        static::assertSame('Groupe Actes / Helios', $affiliation->getCaption());
    }

    public function testCaptionIsPlainWithoutAdministeredModule(): void
    {
        $affiliation = new GroupAffiliation('Groupe Claude Dev', []);

        static::assertSame('Groupe', $affiliation->getCaption());
    }
}
