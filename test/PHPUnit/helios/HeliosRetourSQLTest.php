<?php

use S2lowLegacy\Model\HeliosRetourSQL;

class HeliosRetourSQLTest extends S2lowTestCase
{
    public function testAdd()
    {
        $siret = "12345678900035";
        $heliosRetourSQL = new HeliosRetourSQL($this->getSQLQuery());
        $helios_retour_id = $heliosRetourSQL->add(1, $siret, "toto.txt");
        $info = $heliosRetourSQL->getInfo($helios_retour_id);
        $this->assertEquals($siret, $info['siret']);
    }

    public function testAddMany()
    {
        $siret = "12345678900035";
        $heliosRetourSQL = new HeliosRetourSQL($this->getSQLQuery());
        $helios_retour_id = $heliosRetourSQL->add(1, $siret, "toto.txt");
        $helios_retour_id = $heliosRetourSQL->add(1, $siret, "titi.txt");
        $info = $heliosRetourSQL->getInfo($helios_retour_id);
        $this->assertEquals($siret, $info['siret']);
    }

    public function testGetInfoFromFilename()
    {
        $siret = "12345678900035";
        $heliosRetourSQL = new HeliosRetourSQL($this->getSQLQuery());
        $helios_retour_id = $heliosRetourSQL->add(1, $siret, "toto.txt");
        $info = $heliosRetourSQL->getInfoFromFilename(1, "toto.txt");
        $this->assertEquals($siret, $info['siret']);
    }
}
