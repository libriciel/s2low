<?php

use S2lowLegacy\Class\Droit;

class DroitTest extends S2lowTestCase
{
    private function getDroit()
    {
        return $this->getObjectInstancier()->get(Droit::class);
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

    public function testHasDroit()
    {
        $droit = $this->getDroit();
        $this->assertTrue($droit->hasDroit(array('role' => 'SADM'), array()));
        $this->assertTrue($droit->hasDroit(array('role' => 'ADM','authority_group_id' => 42), array('authority_group_id' => 42)));
        $this->assertTrue($droit->hasDroit(array('role' => 'GADM','authority_group_id' => 42), array('authority_group_id' => 42)));
        $this->assertFalse($droit->hasDroit(array('role' => 'USER','authority_group_id' => 42), array('authority_group_id' => 42)));
    }
}
