<?php

use S2lowLegacy\Class\Droit;

class DroitTest extends S2lowTestCase
{
    private function getDroit(): Droit
    {
        return $this->getContainer()->get(Droit::class);
    }

    public function testCanAccess()
    {

        $moduleInfo = array();
        $userInfo = array('status' => 0,'role' => '');
        $authorityInfo = array();
        $groupeInfo = array();
        $droitModuleInfo = array();
        $permUser = array();
        $droit_specific = array();

        $droit = $this->getDroit();

        $this->assertFalse($droit->canAccess($moduleInfo, $userInfo, $authorityInfo, $groupeInfo, $droitModuleInfo, $permUser, $droit_specific));

        $moduleInfo['status'] = 0;
        $this->assertFalse($droit->canAccess($moduleInfo, $userInfo, $authorityInfo, $groupeInfo, $droitModuleInfo, $permUser, $droit_specific));

        $moduleInfo['status'] = 1;
        $this->assertFalse($droit->canAccess($moduleInfo, $userInfo, $authorityInfo, $groupeInfo, $droitModuleInfo, $permUser, $droit_specific));

        $userInfo['status'] = 1;
        $this->assertFalse($droit->canAccess($moduleInfo, $userInfo, $authorityInfo, $groupeInfo, $droitModuleInfo, $permUser, $droit_specific));

        $authorityInfo['status'] = 1;
        $this->assertFalse($droit->canAccess($moduleInfo, $userInfo, $authorityInfo, $groupeInfo, $droitModuleInfo, $permUser, $droit_specific));

        $groupeInfo['status'] = 0;
        $this->assertFalse($droit->canAccess($moduleInfo, $userInfo, $authorityInfo, $groupeInfo, $droitModuleInfo, $permUser, $droit_specific));

        $groupeInfo['status'] = 1;
        $this->assertFalse($droit->canAccess($moduleInfo, $userInfo, $authorityInfo, $groupeInfo, $droitModuleInfo, $permUser, $droit_specific));

        $userInfo['role'] = 'SADM';
        $this->assertTrue($droit->canAccess($moduleInfo, $userInfo, $authorityInfo, $groupeInfo, $droitModuleInfo, $permUser, $droit_specific));

        $userInfo['role'] = 'ADM';
        $this->assertFalse($droit->canAccess($moduleInfo, $userInfo, $authorityInfo, $groupeInfo, $droitModuleInfo, $permUser, $droit_specific));

        $droitModuleInfo = 'toto';
        $this->assertFalse($droit->canAccess($moduleInfo, $userInfo, $authorityInfo, $groupeInfo, $droitModuleInfo, $permUser, $droit_specific));

        $permUser = 'RO';
        $this->assertTrue($droit->canAccess($moduleInfo, $userInfo, $authorityInfo, $groupeInfo, $droitModuleInfo, $permUser, $droit_specific));

        $permUser = "TT";
        $droit_specific = array("TT");
        $this->assertTrue($droit->canAccess($moduleInfo, $userInfo, $authorityInfo, $groupeInfo, $droitModuleInfo, $permUser, $droit_specific));
    }

    public function testIsSuperAdmin()
    {
        $droit = $this->getDroit();
        $this->assertTrue($droit->isSuperAdmin(array('role' => 'SADM')));
        $this->assertFalse($droit->isSuperAdmin(array('role' => 'GADM')));
        $this->assertFalse($droit->isSuperAdmin(array('role' => 'ADM')));
        $this->assertFalse($droit->isSuperAdmin(array('role' => 'USER')));
    }

    public function testIsAnyAdmin()
    {
        $droit = $this->getDroit();
        $this->assertTrue($droit->isAnyAdmin(array('role' => 'SADM')));
        $this->assertTrue($droit->isAnyAdmin(array('role' => 'GADM')));
        $this->assertTrue($droit->isAnyAdmin(array('role' => 'ADM')));
        $this->assertFalse($droit->isAnyAdmin(array('role' => 'USER')));
    }

    public function testIsGroupAdmin()
    {
        $droit = $this->getDroit();
        $this->assertFalse($droit->isGroupAdmin(array('role' => 'SADM')));
        $this->assertTrue($droit->isGroupAdmin(array('role' => 'GADM')));
        $this->assertFalse($droit->isGroupAdmin(array('role' => 'ADM')));
        $this->assertFalse($droit->isGroupAdmin(array('role' => 'USER')));
    }

    public function testIsAuthorityAdmin()
    {
        $droit = $this->getDroit();
        $this->assertFalse($droit->isAuthorityAdmin(array('role' => 'SADM')));
        $this->assertFalse($droit->isAuthorityAdmin(array('role' => 'GADM')));
        $this->assertTrue($droit->isAuthorityAdmin(array('role' => 'ADM')));
        $this->assertFalse($droit->isAuthorityAdmin(array('role' => 'USER')));
    }

    public function testAdministersHeliosSuperAdmin()
    {
        $droit = $this->getDroit();
        $this->assertTrue($droit->administersHelios(['role' => 'SADM'], []));
    }

    public function testAdministersHeliosGroupAdminDesignatedForHelios()
    {
        $droit = $this->getDroit();
        $this->assertTrue($droit->administersHelios(
            ['role' => 'GADM', 'authority_group_id' => 42],
            ['helios_group_id' => 42, 'actes_group_id' => 7]
        ));
    }

    public function testAdministersHeliosGroupAdminDesignatedForActesOnly()
    {
        $droit = $this->getDroit();
        $this->assertFalse($droit->administersHelios(
            ['role' => 'GADM', 'authority_group_id' => 42],
            ['helios_group_id' => 7, 'actes_group_id' => 42]
        ));
    }

    public function testAdministersHeliosGroupAdminWithoutDesignation()
    {
        $droit = $this->getDroit();
        $this->assertFalse($droit->administersHelios(
            ['role' => 'GADM', 'authority_group_id' => 42],
            []
        ));
    }

    public function testAdministersHeliosOtherRoles()
    {
        $droit = $this->getDroit();
        $this->assertFalse($droit->administersHelios(
            ['role' => 'ADM', 'authority_group_id' => 42],
            ['helios_group_id' => 42]
        ));
        $this->assertFalse($droit->administersHelios(
            ['role' => 'USER', 'authority_group_id' => 42],
            ['helios_group_id' => 42]
        ));
    }
}
