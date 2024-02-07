<?php

declare(strict_types=1);

namespace PHPUnit\helios;

use PHPUnit\S2lowTestCase;
use S2lowLegacy\Model\HeliosRetourSQL;

class HeliosRetourSQLTest extends S2lowTestCase
{
    public function testAdd()
    {
        $siret = "12345678900035";
        $heliosRetourSQL = new HeliosRetourSQL($this->getSQLQuery());
        $helios_retour_id = $heliosRetourSQL->add(1, $siret, "toto.txt", 10, "sha1");
        $info = $heliosRetourSQL->getInfo($helios_retour_id);
        $this->assertEquals($siret, $info['siret']);
    }

    public function testAddMany()
    {
        $siret = "12345678900035";
        $heliosRetourSQL = new HeliosRetourSQL($this->getSQLQuery());
        $helios_retour_id = $heliosRetourSQL->add(1, $siret, "toto.txt", 10, "sha1");
        $helios_retour_id = $heliosRetourSQL->add(1, $siret, "titi.txt", 10, "sha1");
        $info = $heliosRetourSQL->getInfo($helios_retour_id);
        $this->assertEquals($siret, $info['siret']);
    }

    public function testGetInfoFromFilename()
    {
        $siret = "12345678900035";
        $heliosRetourSQL = new HeliosRetourSQL($this->getSQLQuery());
        $helios_retour_id = $heliosRetourSQL->add(1, $siret, "toto.txt", 10, "sha1");
        $info = $heliosRetourSQL->getInfoFromFilename(1, "toto.txt");
        $this->assertEquals($siret, $info['siret']);
    }
}
