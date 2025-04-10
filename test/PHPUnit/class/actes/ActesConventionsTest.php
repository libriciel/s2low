<?php

use S2lowLegacy\Class\actes\ActesConventions;
use S2lowLegacy\Class\ActesWorkspace;
use S2lowLegacy\Class\ActesWorkspaceForTests;
use S2lowLegacy\Class\IActesWorkspace;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Model\AuthoritySQL;

class ActesConventionsTest extends S2lowTestCase
{
    /** @var  ActesConventions */
    private $actesConventions;
    private ActesWorkspaceForTests $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workspace = new ActesWorkspaceForTests();
        $this->actesConventions = new ActesConventions(
            $this->getObjectInstancier()->get(AuthoritySQL::class),
            $this->workspace
        );
    }

    protected function tearDown(): void
    {
        $this->workspace->clear();
    }

    public function testHasNoConvention()
    {
        $this->assertFalse($this->actesConventions->hasConvention(1));
    }

    public function testHasConvention()
    {
        $this->actesConventions->setConvention(1, __DIR__ . "/fixtures/convention-exemple.pdf");
        $this->assertTrue($this->actesConventions->hasConvention(1));
    }

    public function testGetConventionFilename()
    {
        $this->actesConventions->setConvention(1, __DIR__ . "/fixtures/convention-exemple.pdf");
        $this->assertEquals(
            "123456789-convention-actes.pdf",
            $this->actesConventions->getConventionFilename(1)
        );
    }

    public function testGetConventionFilepath()
    {
        $this->actesConventions->setConvention(1, __DIR__ . "/fixtures/convention-exemple.pdf");
        $this->assertEquals(
            $this->workspace->getFilesUploadRoot() . "/123456789/123456789-convention-actes.pdf",
            $this->actesConventions->getConventionFilepath(1)
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
}
