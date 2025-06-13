<?php

use S2lowLegacy\Class\actes\ActesConventions;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Model\AuthoritySQL;

class ActesConventionsTest extends S2lowTestCase
{
    /** @var  ActesConventions */
    private $actesConventions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actesConventions = $this->getActeConventions();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testHasNoConvention()
    {
        $this->assertFalse($this->actesConventions->hasConvention(101));
    }

    public function testHasConvention()
    {
        $this->actesConventions->setConvention(101, __DIR__ . "/fixtures/convention-exemple.pdf");
        $this->assertTrue($this->actesConventions->hasConvention(101));
    }

    public function testGetConventionFilename()
    {
        $this->actesConventions->setConvention(101, __DIR__ . "/fixtures/convention-exemple.pdf");
        $this->assertEquals(
            "123456789-convention-actes.pdf",
            $this->actesConventions->getConventionFilename(101)
        );
    }

    public function testGetConventionFilepath()
    {
        $this->actesConventions->setConvention(101, __DIR__ . "/fixtures/convention-exemple.pdf");
        $this->assertEquals(
            $this->tmpPathFolder . "/123456789/123456789-convention-actes.pdf",
            $this->actesConventions->getConventionFilepath(101)
        );
    }

    public function testHasNoConventionNoArgs()
    {
        $this->assertFalse($this->actesConventions->hasConvention(0));
    }

    public function testGetConventionFilenameNoException()
    {
        $this->setExpectedException("Exception", "Aucune convention présente pour la collectivité 0");
        $this->actesConventions->getConventionFilename(0);
    }

    public function testGetConventionFilenameNoException2()
    {
        $this->setExpectedException("Exception", "Aucune convention présente pour la collectivité 18");
        $this->actesConventions->getConventionFilename(18);
    }

    private function getActeConventions(): ActesConventions
    {
        return new ActesConventions(
            self::getContainer()->get(AuthoritySQL::class),
            $this->tmpPathFolder
        );
    }
}
