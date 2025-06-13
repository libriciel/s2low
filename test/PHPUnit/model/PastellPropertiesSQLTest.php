<?php

use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\PastellProperties;
use S2lowLegacy\Model\PastellPropertiesSQL;

class PastellPropertiesSQLTest extends S2lowTestCase
{
    public function testGetPastellProperties()
    {
        $pastellPropertiesSQL = self::getContainer()->get(PastellPropertiesSQL::class);
        $pastellProperties = $pastellPropertiesSQL->getPastellProperties(101);
        $this->assertEmpty($pastellProperties->url);
    }

    public function testEditProperties()
    {
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "test";
        $pastellProperties->login = "login";
        $pastellProperties->password = "password";
        $pastellProperties->id_e = 42;

        $pastellProperties->actes_flux_id = "actes-automatiques";

        $pastellPropertiesSQL = self::getContainer()->get(PastellPropertiesSQL::class);

        $pastellPropertiesSQL->editProperties(101, $pastellProperties);


        $pastellPropertiesResult = $pastellPropertiesSQL->getPastellProperties(101);

        $info = self::getContainer()->get(AuthoritySQL::class)->getInfo(101);
        $this->assertEquals("test", $info['pastell_url']);
        $this->assertEquals("password", $info['pastell_password']);
        $this->assertEquals("actes-automatiques", $pastellPropertiesResult->actes_flux_id);
    }


    public function testUpdateProperties()
    {
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "test";
        $pastellProperties->login = "login";
        $pastellProperties->password = "password";
        $pastellProperties->id_e = 42;

        $pastellProperties->actes_flux_id = "actes-automatiques";

        $pastellPropertiesSQL = self::getContainer()->get(PastellPropertiesSQL::class);

        $pastellPropertiesSQL->editProperties(101, $pastellProperties);
        $pastellPropertiesSQL->editProperties(101, $pastellProperties);

        $pastellPropertiesResult = $pastellPropertiesSQL->getPastellProperties(101);

        $info = self::getContainer()->get(AuthoritySQL::class)->getInfo(101);
        $this->assertEquals("test", $info['pastell_url']);
        $this->assertEquals("password", $info['pastell_password']);
        $this->assertEquals("actes-automatiques", $pastellPropertiesResult->actes_flux_id);
    }
}
