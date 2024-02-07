<?php

declare(strict_types=1);

namespace PHPUnit\class;

use Exception;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\SimpleXMLWrapper;

class SimpleXMLWrapperTest extends TestCase
{
    /**
     * @var SimpleXMLWrapper
     */
    private $simpleXMLWrapper;

    public function setUp(): void
    {
        $this->simpleXMLWrapper = new SimpleXMLWrapper();
    }

    /**
     * @throws Exception
     */
    public function testLoadBadString()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("XML incorrect");
        $this->simpleXMLWrapper->loadString("foo");
    }

    /**
     * @throws Exception
     */
    public function testLoadString()
    {
        $this->assertEquals("foo", $this->simpleXMLWrapper->loadString("<foo></foo>")->getName());
    }

    /**
     * @throws Exception
     */
    public function testLoadFile()
    {
        $file_path = $this->getFilePath("<foo></foo>");
        $xml = $this->simpleXMLWrapper->loadFile($file_path);
        $this->assertEquals("foo", $xml->getName());
    }

    /**
     * @throws Exception
     */
    public function testLoadBadFile()
    {
        $file_path = $this->getFilePath("foo");
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Le fichier vfs://test/fichier.xml n'est pas un XML correct");
        $this->simpleXMLWrapper->loadFile($file_path);
    }

    private function getFilePath($file_content)
    {
        vfsStream::setup('test');
        $testStreamUrl = vfsStream::url('test');
        $file_path = $testStreamUrl . "/fichier.xml";
        file_put_contents($file_path, $file_content);
        return $file_path;
    }
}
