<?php

use S2lowLegacy\Model\HeliosRetourSQL;

class HeliosRetourSQLTest extends S2lowTestCase
{
    public function testAdd()
    {
        $siret = "12345678900035";
        $heliosRetourSQL = self::getContainer()->get(HeliosRetourSQL::class);
        $helios_retour_id = $heliosRetourSQL->add(1, $siret, "toto.txt", 10, "sha1");
        $info = $heliosRetourSQL->getInfo($helios_retour_id);
        $this->assertSame($siret, $info['siret']);
    }

    public function testAddMany()
    {
        $siret = "12345678900035";
        $heliosRetourSQL = self::getContainer()->get(HeliosRetourSQL::class);
        $helios_retour_id = $heliosRetourSQL->add(1, $siret, "toto.txt", 10, "sha1");
        $helios_retour_id = $heliosRetourSQL->add(1, $siret, "titi.txt", 10, "sha1");
        $info = $heliosRetourSQL->getInfo($helios_retour_id);
        $this->assertSame($siret, $info['siret']);
    }

    public function testGetInfoFromFilename()
    {
        $siret = "12345678900035";
        $heliosRetourSQL = self::getContainer()->get(HeliosRetourSQL::class);
        $heliosRetourSQL->add(1, $siret, "toto.txt", 10, "sha1");
        $info = $heliosRetourSQL->getInfoFromFilename(1, "toto.txt");
        $this->assertSame($siret, $info['siret']);
    }

    public function testChangeBulkStatusToLu(): void
    {
        $heliosRetourSQL = self::getContainer()->get(HeliosRetourSQL::class);
        $id1 = $heliosRetourSQL->add(1, "12345678900035", "toto1.txt", 10, "sha1");
        $id2 = $heliosRetourSQL->add(1, "12345678900035", "toto2.txt", 10, "sha1");

        // Verify initial status is 0 (non lu)
        $this->assertSame(0, $heliosRetourSQL->getInfo($id1)['status']);
        $this->assertSame(0, $heliosRetourSQL->getInfo($id2)['status']);

        // Bulk change to LU (1)
        $heliosRetourSQL->changeBulkStatus([$id1, $id2], HeliosRetourSQL::STATUS_LU);

        $this->assertSame(1, $heliosRetourSQL->getInfo($id1)['status']);
        $this->assertSame(1, $heliosRetourSQL->getInfo($id2)['status']);
    }

    public function testChangeBulkStatusToNonLu(): void
    {
        $heliosRetourSQL = self::getContainer()->get(HeliosRetourSQL::class);
        $id1 = $heliosRetourSQL->add(1, "12345678900035", "toto1.txt", 10, "sha1");
        $id2 = $heliosRetourSQL->add(1, "12345678900035", "toto2.txt", 10, "sha1");

        // Change status to LU (1) first
        $heliosRetourSQL->changeStatus($id1, HeliosRetourSQL::STATUS_LU);
        $heliosRetourSQL->changeStatus($id2, HeliosRetourSQL::STATUS_LU);

        $this->assertSame(1, $heliosRetourSQL->getInfo($id1)['status']);
        $this->assertSame(1, $heliosRetourSQL->getInfo($id2)['status']);

        // Bulk change to NON_LU (0)
        $heliosRetourSQL->changeBulkStatus([$id1, $id2], HeliosRetourSQL::STATUS_NON_LU);

        $this->assertSame(0, $heliosRetourSQL->getInfo($id1)['status']);
        $this->assertSame(0, $heliosRetourSQL->getInfo($id2)['status']);
    }
}
