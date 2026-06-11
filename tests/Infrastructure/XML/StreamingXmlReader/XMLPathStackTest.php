<?php

namespace Infrastructure\XML\StreamingXmlReader;

use PHPUnit\Framework\TestCase;
use S2low\Exceptions\XMLParsingDesynchronizationException;
use S2low\Infrastructure\XML\StreamingXmlReader\XMLPathTracker;
use Symfony\Component\Config\Util\Exception\XmlParsingException;
use UnexpectedValueException;

class XMLPathStackTest extends TestCase
{
    public function testSimpleMatch(): void
    {
        $xmlPathStack = new XMLPathTracker(['test']);
        $xmlPathStack->enterElement('test');
        self::assertTrue($xmlPathStack->hasMatched());
    }

    public function testMatchOnLongerPath(): void
    {
        $xmlPathStack = new XMLPathTracker(['test1', 'test2']);
        $xmlPathStack->enterElement('test1');
        self::assertSame(1, $xmlPathStack->getCurrentMatchingDepth());
        $xmlPathStack->enterElement('test2');
        self::assertSame(2, $xmlPathStack->getCurrentMatchingDepth());
        self::assertTrue($xmlPathStack->hasMatched());
    }
    public function testCannotBeMatched(): void
    {
        $xmlPathStack = new XMLPathTracker(['test1', 'test2']);
        $xmlPathStack->enterElement('test1');
        self::assertSame(1, $xmlPathStack->getCurrentMatchingDepth());
        $xmlPathStack->leaveElement('test1');
        self::assertSame(0, $xmlPathStack->getCurrentMatchingDepth());
        self::assertFalse($xmlPathStack->hasMatched());
        self::assertTrue($xmlPathStack->cannotBeMatched());
    }
    public function testMatchOnPathWithChildBefore(): void
    {
        $xmlPathStack = new XMLPathTracker(['test1', 'test3']);
        $xmlPathStack->enterElement('test1');
        self::assertSame(1, $xmlPathStack->getCurrentMatchingDepth());
        $xmlPathStack->enterElement('test2');
        self::assertSame(1, $xmlPathStack->getCurrentMatchingDepth());
        $xmlPathStack->leaveElement('test2');
        self::assertSame(1, $xmlPathStack->getCurrentMatchingDepth());
        $xmlPathStack->enterElement('test3');
        self::assertSame(2, $xmlPathStack->getCurrentMatchingDepth());
        self::assertTrue($xmlPathStack->hasMatched());
        self::assertFalse($xmlPathStack->cannotBeMatched());
    }
    /*public function testXMLDesynchronizationOnEntering(): void
    {
        $xmlPathStack = new XMLPathTracker(['test1', 'test3']);
        self::expectException(XMLParsingDesynchronizationException::class);
        self::expectExceptionMessage('Invalid XML depth transition on entering test1 with depth 5');

        $xmlPathStack->enterElement('test1',5);

    }

    public function testLeavingOnEmptyMatch()
    {
        $xmlPathStack = new XMLPathTracker(['test1', 'test3']);

        self::expectException(XMLParsingDesynchronizationException::class);
        self::expectExceptionMessage('No element to leave');

        $xmlPathStack->leaveElement('whatever',5);
    }

    public function testXMLDesynchronizationOnLeaving(): void
    {
        $xmlPathStack = new XMLPathTracker(['test1', 'test3']);
        $xmlPathStack->enterElement('test1',0);

        self::expectException(XMLParsingDesynchronizationException::class);
        self::expectExceptionMessage('Invalid XML depth transition on leaving whatever with depth 1');

        $xmlPathStack->leaveElement('whatever',1);

    }*/
}
