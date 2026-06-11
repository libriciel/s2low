<?php

namespace S2low\Tests\Services\XMLFromDGFIP\PesBuilder;

use PHPUnit\Framework\TestCase;
use S2low\Services\XMLFromDGFiP\ParsablesPes\PesDocumentType;
use S2low\Services\XMLFromDGFiP\ParsablesPes\Values\CodCol;
use S2low\Services\XMLFromDGFiP\ParsablesPes\Values\NomFich;
use S2low\Services\XMLFromDGFiP\ParsablesPes\Values\Siret;
use S2low\Services\XMLFromDGFiP\PesBuilder\ParsedPes;
use S2low\Services\XMLFromDGFiP\PesBuilder\PesBuilder;

class PesBuilderTest extends TestCase
{
    /**
     * @var \S2low\Services\XMLFromDGFiP\PesBuilder\PesBuilder
     */
    private PesBuilder $pesBuilder;

    public function __construct()
    {
        parent::__construct();
        $this->pesBuilder = new PesBuilder();
    }
    public function testCreatePesAcquit()
    {
        $pes = $this->pesBuilder->createFromParsedValues(
            'PES_ACQUIT',
            PesDocumentType::PES_ACQUIT,
            [
                CodCol::KEY => 'CodColl',
                NomFich::KEY => 'NomFich'
            ],
            []
        );

        self::assertInstanceOf(ParsedPes::class, $pes);

        self::assertTrue(
            $pes->hasType(PesDocumentType::PES_ACQUIT)
        );
        self::assertSame(
            'NomFich',
            $pes->getStringValue(NomFich::KEY)
        );
        self::assertSame(
            'CodColl',
            $pes->getStringValue(CodCol::KEY)
        );
    }

    public function testCreatePesRetour()
    {
        $pes = $this->pesBuilder->createFromParsedValues(
            'PES_Retour',
            PesDocumentType::PES_RETOUR,
            [
                Siret::KEY => 'Siret'
            ],
            []
        );

        self::assertInstanceOf(ParsedPes::class, $pes);

        self::assertTrue(
            $pes->hasType(PesDocumentType::PES_RETOUR)
        );
        self::assertSame(
            'Siret',
            $pes->getStringValue(Siret::KEY)
        );
    }
    public function testCreateUnknowFile()
    {
        $pes = $this->pesBuilder->createFromParsedValues(
            'Whatever',
            PesDocumentType::UNKNOWN_ROOT,
            [
                CodCol::KEY => 'CodColl',
                NomFich::KEY => 'NomFich'
            ],
            []
        );

        self::assertInstanceOf(ParsedPes::class, $pes);

        self::assertTrue(
            $pes->hasType(PesDocumentType::UNKNOWN_ROOT)
        );
    }
}
