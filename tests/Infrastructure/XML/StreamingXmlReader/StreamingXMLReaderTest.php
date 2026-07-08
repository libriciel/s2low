<?php

namespace Infrastructure\XML\StreamingXmlReader;

use ErrorException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use S2low\Infrastructure\XML\StreamingXmlReader\NodeMatcherForTests;
use S2low\Infrastructure\XML\StreamingXmlReader\XmlNodeStreamParser;
use S2lowTestCase;

class StreamingXMLReaderTest extends S2lowTestCase
{
    private const XSD_PES_ACQUIT = __DIR__ . '/../../../../xsd/schemas_pes_v5.24/PES_V2/Rev0/PES_V2_Acquit_Autonome_V2.xsd';

    public function testRead()
    {
        $reader = self::getContainer()->get(XmlNodeStreamParser::class);
        $nodeMatcher = new NodeMatcherForTests();
        [$nodes, $errors] = $reader->parse(__DIR__ . '/fixtures/simplest.xml', $nodeMatcher);
        self::assertSame(['node'], $nodes);
    }

    public function testReadSimpleElement()
    {
        $reader = self::getContainer()->get(XmlNodeStreamParser::class);
        $nodeMatcher = new NodeMatcherForTests();
        [$nodes, $errors] = $reader->parse(__DIR__ . '/fixtures/simpleelement.xml', $nodeMatcher);
        self::assertSame(['simpleElementNode'], $nodes);
    }

    public function testXmlWithSomeDepth()
    {
        $reader = self::getContainer()->get(XmlNodeStreamParser::class);
        $nodeMatcher = new NodeMatcherForTests();
        [$nodes, $errors] = $reader->parse(__DIR__ . '/fixtures/xmlwithsomedepth.xml', $nodeMatcher);
        self::assertSame(['root','header','simpleelement','list','elemement','elemement','intrus'], $nodes);
    }

    /*public function testXmlWithSomeDepth2()
    {
        $reader = new XmlNodeStreamParser(__DIR__ . '/fixtures/xmlwithsomedepth.xml');
        $nodeMatcher = new NodeMatcherForDepthTests(new XMLPathTracker());
        [$nodes, $errors] = $reader->parse($nodeMatcher);
        self::assertSame(['root','header','simpleelement','list','elemement','elemement','intrus'], $nodes);
    }*/

    public function testPathesInXmlWithSomeDepth()
    {
        $reader = self::getContainer()->get(XmlNodeStreamParser::class);
        $nodeMatcher = new NodeMatcherForTests();
        [$nodes, $errors] = $reader->parse(__DIR__ . '/fixtures/xmlwithsomedepth.xml', $nodeMatcher);
        self::assertSame(['root','header','simpleelement','list','elemement','elemement','intrus'], $nodes);
    }

    public function testPesAcquitUnexistingFile()
    {
        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage(
            'Unable to open file'
        );

        $reader = self::getContainer()->get(XmlNodeStreamParser::class);

        $reader->parse(
            __DIR__ . '/../../../../test/PHPUnit/helios/fixtures/not_a_file.xml',
            new NodeMatcherForTests(),
            self::XSD_PES_ACQUIT
        );
    }
}
