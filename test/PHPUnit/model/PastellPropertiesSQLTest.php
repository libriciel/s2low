<?php

declare(strict_types=1);

namespace PHPUnit\model;

use PHPUnit\S2lowTestCase;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\PastellProperties;
use S2lowLegacy\Model\PastellPropertiesSQL;

class PastellPropertiesSQLTest extends S2lowTestCase
{
    public function testGetPastellProperties()
    {
        $pastellPropertiesSQL = new PastellPropertiesSQL($this->getSQLQuery());
        $pastellProperties = $pastellPropertiesSQL->getPastellProperties(1);
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

        $pastellPropertiesSQL = new PastellPropertiesSQL($this->getSQLQuery());

        $pastellPropertiesSQL->editProperties(1, $pastellProperties);


        $pastellPropertiesResult = $pastellPropertiesSQL->getPastellProperties(1);

        $info = $this->getObjectInstancier()->get(AuthoritySQL::class)->getInfo(1);
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

        $pastellPropertiesSQL = new PastellPropertiesSQL($this->getSQLQuery());

        $pastellPropertiesSQL->editProperties(1, $pastellProperties);
        $pastellPropertiesSQL->editProperties(1, $pastellProperties);

        $pastellPropertiesResult = $pastellPropertiesSQL->getPastellProperties(1);

        $info = $this->getObjectInstancier()->get(AuthoritySQL::class)->getInfo(1);
        $this->assertEquals("test", $info['pastell_url']);
        $this->assertEquals("password", $info['pastell_password']);
        $this->assertEquals("actes-automatiques", $pastellPropertiesResult->actes_flux_id);
    }
}
