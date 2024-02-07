<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use PHPUnit\Framework\TestCase;
use Exception;
use S2lowLegacy\Class\actes\ActesNameArchive;

class ActesNameArchiveTest extends TestCase
{
    private const ARCHIVE_NAME = "abc-TACT--123456789--20150729-1.tar.gz";
    private const TRIGRAMME = "abc";
    private const QUADRIGRAMME = "TACT";

    /**
     * @var ActesNameArchive
     */
    private $actesNameArchive;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actesNameArchive = new ActesNameArchive(self::TRIGRAMME, self::QUADRIGRAMME);
    }

    public function testIsNameOK()
    {
        $this->assertTrue($this->actesNameArchive->verifNameOK(self::ARCHIVE_NAME));
    }

    public function testVerifNameBadName()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Le nom de l'archive n'est pas correct");
        $this->actesNameArchive->verifNameOK("foo");
    }

    public function testVerifNameBadTrigramme()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Le trigramme trouvé (def) ne correspond pas au trigramme attendu (abc)");
        $this->actesNameArchive->verifNameOK("def-TACT--123456789--20150729-1.tar.gz");
    }

    public function testVerifNameBadQuadrigramme()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Le quadrigramme trouvé (EACT) ne correspond pas au quadrigramme attendu (TACT)");
        $this->actesNameArchive->verifNameOK("abc-EACT--123456789--20150729-1.tar.gz");
    }
}
