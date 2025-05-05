<?php

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Lib\Recuperateur;

class RecuperateurTest extends TestCase
{
    private const WITH_UTF8_ENCODE = true;
    private const WITH_NOT_UTF8_ENCODE = false;
    private const PHRASE_ISO_FORMAT = "L'\xe9l\xe8ve a \xe9t\xe9 not\xe9 tr\xe8s s\xe9v\xe8rement.";

    public function testGet()
    {
        $get = array('foo' => 'bar');
        $recuperateur = new Recuperateur($get);
        $this->assertEquals('bar', $recuperateur->get('foo'));
    }

    public function testGetEmpty()
    {
        $get = array('foo' => 'bar');
        $recuperateur = new Recuperateur($get);
        $this->assertFalse($recuperateur->get('baz'));
    }

    public function testGetInt()
    {
        $get = array('foo' => '42bar');
        $recuperateur = new Recuperateur($get);
        $this->assertEquals(42, $recuperateur->getInt('foo'));
    }

    public function testGetWithUTF8Encode()
    {
        $recuperateur = new Recuperateur(
            [ "test" => self::PHRASE_ISO_FORMAT ],
            self::WITH_UTF8_ENCODE
        );

        $resultat = $recuperateur->get("test");

        self::assertEquals("L'élève a été noté très sévèrement.", $resultat);
    }

    public function testGetWithNotUTF8Encode()
    {
        $recuperateur = new Recuperateur(
            [ "test" => self::PHRASE_ISO_FORMAT ],
            self::WITH_NOT_UTF8_ENCODE
        );

        $resultat = $recuperateur->get("test");

        self::assertEquals(self::PHRASE_ISO_FORMAT, $resultat);
    }
}
