<?php

namespace S2low\Tests\Services\XMLFromDGFIP;

use PHPUnit\Framework\TestCase;
use S2low\Infrastructure\XML\StreamingXmlReader\DepthTracker;
use S2low\Infrastructure\XML\StreamingXmlReader\XmlNodeStreamParser;
use S2low\Infrastructure\XML\StreamingXmlReader\XMLPathTracker;
use S2low\Services\XMLFromDGFiP\ParsablesPes\Values\CodCol;
use S2low\Services\XMLFromDGFiP\ParsablesPes\Values\NomFich;
use S2low\Services\XMLFromDGFiP\ParsablesPes\Values\Siret;
use S2low\Services\XMLFromDGFiP\PesNodeMatcher;
use S2lowTestCase;

class PesXmlReaderTest extends S2lowTestCase
{
    public function testNoValuesToFind()
    {
        $nodeStreamParser = self::getContainer()->get(XmlNodeStreamParser::class);

        $valuesTofind = [ ];

        $pesNodeMatcher = new PesNodeMatcher($valuesTofind, new DepthTracker());

        [$foundValues, $errors] = $nodeStreamParser->parse(
            TestFiles::PESRETOUR_PATH,
            $pesNodeMatcher,
            '/../../xsd/schemas_pes_v5.24/PES_V2/RETOUR/Rev0/PES_Retour.xsd'
        );

        self::assertSame(
            [],
            $foundValues
        );

        self::assertSame($errors, []);
    }

    public function testPesRetour()
    {

        $nodeStreamParser = self::getContainer()->get(XmlNodeStreamParser::class);

        $valuesTofind = [ Siret::KEY => new XMLPathTracker(Siret::PATH)  ];

        $pesNodeMatcher = new PesNodeMatcher($valuesTofind, new DepthTracker());

        [$foundValues, $errors] = $nodeStreamParser->parse(
            TestFiles::PESRETOUR_PATH,
            $pesNodeMatcher,
            '/../../xsd/schemas_pes_v5.24/PES_V2/RETOUR/Rev0/PES_Retour.xsd'
        );

        self::assertSame(
            ['siret' => '12345678900035'],
            $foundValues
        );

        self::assertSame($errors, []);
    }

    public function testPesAller()
    {
        $nodeStreamParser = self::getContainer()->get(XmlNodeStreamParser::class);

        $valuesTofind = [
            CodCol::KEY => new XMLPathTracker(CodCol::PATH),
            NomFich::KEY => new XMLPathTracker(NomFich::PATH),
        ];

        $pesNodeMatcher = new PesNodeMatcher($valuesTofind, new DepthTracker());

        [$foundValues, $errors] = $nodeStreamParser->parse(
            TestFiles::PESACQUIT_PATH,
            $pesNodeMatcher,
            '/../../xsd/schemas_pes_v5.24/PES_V2/Rev0/PES_V2_Acquit_Autonome_V2.xsd'
        );

        self::assertSame(
            [
                'nom_fic' => 'pescg291201703030412001',
                'cod_col' => '400'
            ],
            $foundValues
        );

        self::assertSame($errors, []);
    }
}
