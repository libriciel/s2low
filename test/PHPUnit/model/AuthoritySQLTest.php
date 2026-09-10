<?php

use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\PastellProperties;

class AuthoritySQLTest extends S2lowTestCase
{
    /**
     * @var AuthoritySQL
     */
    private $authoritySQL;

    public function setUp(): void
    {
        parent::setUp();
        $this->authoritySQL = self::getContainer()->get(AuthoritySQL::class);
    }

    public function testGetInfo()
    {
        $info = $this->authoritySQL->getInfo(1);
        $this->assertEquals("Bourg-en-Bresse", $info['name']);
    }

    public function testGetIdBySiren()
    {
        $id = $this->authoritySQL->getIdBySIREN("123456789");
        $this->assertEquals(1, $id);
    }

    public function testGetAll()
    {
        $info = $this->authoritySQL->getAll();
        $this->assertEquals("Bourg-en-Bresse", $info[1]);
    }

    public function testGetSAEProperties()
    {
        $this->authoritySQL->getSAEProperties();
        self::expectNotToPerformAssertions();
    }

    public function testGetSAEPropertiesType()
    {
        $this->assertEquals('text', $this->authoritySQL->getSAEPropertiesType('pastell_url'));
    }

    public function testUpdateSAE()
    {
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "test";
        $pastellProperties->login = "login";
        $pastellProperties->password = "password";
        $pastellProperties->id_e = 42;
        $this->authoritySQL->updateSAE(1, $pastellProperties);
        $info = $this->authoritySQL->getInfo(1);
        $this->assertEquals("test", $info['pastell_url']);
        $this->assertEquals("password", $info['pastell_password']);
    }

    public function testVerifDepartementAndDistrict()
    {
        $this->assertEquals(0, $this->authoritySQL->verifDepartmentAndDistrict(999, 001));
    }

    public function testGetList()
    {
        $this->assertEmpty($this->authoritySQL->getList(1, 1, 'toto', '123', '1234', 0, 10));
    }

    public function testGetNb()
    {
        $this->assertEquals(0, $this->authoritySQL->getNb(1, 1, 'toto', '123', "1234"));
    }

    public function testGetAllAdministeredBy()
    {
        $result = $this->authoritySQL->getAllAdministeredBy(1);
        $this->assertSame('Saint-Andre de Corcy', $result[2]);
    }

    public function testGetAllAdministeredByOnASingleModule()
    {
        $this->getSQLQuery()->query('UPDATE authorities SET actes_group_id = 2, helios_group_id = NULL WHERE id = 2');

        $result = $this->authoritySQL->getAllAdministeredBy(2);

        $this->assertSame('Saint-Andre de Corcy', $result[2]);
    }

    public function testGetAllAdministeredByAGroupWithoutDesignation()
    {
        $this->assertSame([], $this->authoritySQL->getAllAdministeredBy(2));
    }

    public function testGetListKeepsAnAuthorityAdministeredForASingleModule()
    {
        $this->getSQLQuery()->query('UPDATE authorities SET actes_group_id = 2, helios_group_id = 1 WHERE id = 2');

        $result = $this->authoritySQL->getList(2, false, false, false, false, 0, 10);

        $this->assertSame([2], array_map('intval', array_column($result, 'id')));
    }

    public function testGetListNamesBothAdministeringGroups()
    {
        $this->getSQLQuery()->query('UPDATE authorities SET actes_group_id = 1, helios_group_id = 2 WHERE id = 2');

        $result = $this->authoritySQL->getList(false, false, 'Saint-Andre', false, false, 0, 10);

        $this->assertSame('Groupe de test', $result[0]['actes_group_name']);
        $this->assertSame('second groupe', $result[0]['helios_group_name']);
    }

    public function testGetNbCountsAuthoritiesAdministeredByTheGroup()
    {
        $this->getSQLQuery()->query('UPDATE authorities SET actes_group_id = 2, helios_group_id = 2 WHERE id = 2');

        $this->assertSame(2, (int)$this->authoritySQL->getNb(1, false, false, false, false));
        $this->assertSame(1, (int)$this->authoritySQL->getNb(2, false, false, false, false));
    }

    public function testGetAllForExportNamesBothAdministeringGroups()
    {
        $this->getSQLQuery()->query('UPDATE authorities SET actes_group_id = 1, helios_group_id = 2 WHERE id = 2');

        $result = $this->authoritySQL->getAllForExport(2);

        $this->assertCount(1, $result);
        $this->assertSame('Groupe de test', $result[0]['actes_group_name']);
        $this->assertSame('second groupe', $result[0]['helios_group_name']);
    }

    public function testGetListInsensitive()
    {
        $this->assertEquals(1, count($this->authoritySQL->getList(false, false, "BOURG", false, false, 0, 10)));
    }

    public function testGetNbInsensitive()
    {
        $this->assertEquals(1, $this->authoritySQL->getNb(false, false, "BOURG", false, false));
    }

    public function testUpdateVerifNomFic()
    {
        $this->authoritySQL->updateDoNotVerifyNomFicUnicity(1, true);
        $info = $this->authoritySQL->getInfo(1);
        $this->assertTrue($info['helios_do_not_verify_nom_fic_unicity']);
    }

    public function testUpdateVerifNomFicFalse()
    {
        $this->authoritySQL->updateDoNotVerifyNomFicUnicity(1, false);
        $info = $this->authoritySQL->getInfo(1);
        $this->assertFalse($info['helios_do_not_verify_nom_fic_unicity']);
    }

    public function testGetAllForExport()
    {
        $info = $this->authoritySQL->getAllForExport();

        $this->assertEquals(
            [
                0 =>
                    [
                        'name' => 'Bourg-en-Bresse',
                        'email' => '',
                        'siren' => '123456789',
                        'address' => null,
                        'postal_code' => null,
                        'city' => null,
                        'telephone' => null,
                        'fax' => null,
                        'department' => '001',
                        'district' => '1',
                        'status' => 1,
                        'actes_group_name' => 'Groupe de test',
                        'helios_group_name' => 'Groupe de test',
                        'description' => 'Région',
                    ],
                1 =>
                    [
                        'name' => 'Saint-Andre de Corcy',
                        'email' => 'email',
                        'siren' => '999999999',
                        'address' => null,
                        'postal_code' => null,
                        'city' => null,
                        'telephone' => null,
                        'fax' => null,
                        'department' => null,
                        'district' => null,
                        'status' => 1,
                        'actes_group_name' => 'Groupe de test',
                        'helios_group_name' => 'Groupe de test',
                        'description' => null,
                    ],
                2 =>
                    [
                        "name" => "une nouvelle authority",
                        "email" => null,
                        "siren" => "123456780",
                        "address" => null,
                        "postal_code" => null,
                        "city" => null,
                        "telephone" => null,
                        "fax" => null,
                        "department" => null,
                        "district" => null,
                        "status" => 1,
                        "actes_group_name" => "Groupe de test",
                        "helios_group_name" => "Groupe de test",
                        "description" => null,
                    ],
            ],
            $info
        );
    }

    public function testGetAllForExportGroupAdmin()
    {
        $info = $this->authoritySQL->getAllForExport(2);
        $this->assertEquals([], $info);
    }
}
