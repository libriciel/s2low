<?php

namespace S2lowLegacy\Class;

use S2low\Enum\UserRole;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\GroupSQL;
use S2lowLegacy\Model\ModuleSQL;
use S2lowLegacy\Model\UserSQL;

class Droit
{
    private $userSQL;
    private $authoritySQL;
    private $groupSQL;
    private $moduleSQL;

    public function __construct(
        UserSQL $userSQL,
        AuthoritySQL $authoritySQL,
        GroupSQL $groupSQL,
        ModuleSQL $moduleSQL
    ) {
        $this->userSQL = $userSQL;
        $this->authoritySQL = $authoritySQL;
        $this->groupSQL = $groupSQL;
        $this->moduleSQL = $moduleSQL;
    }




    //Repond à la question : est-ce que tel utilisateur peut accéder au module XYZ
    //TODO : A tester et remplacer la fonction plus bas
    public function canAccessNG($user_id, $module_name = '')
    {

        $userInfo = $this->userSQL->getInfo($user_id);

        $authorityInfo = $this->authoritySQL->getInfo($userInfo['authority_id']);


        $groupeInfo = false;
        if ($authorityInfo['authority_group_id']) {
            $groupeInfo = $this->groupSQL->getInfo($authorityInfo['authority_group_id']);
        }

        $droit_specific = array();
        if ($module_name == 'actes') {
            $droit_specific = array('CS','TT');
        }

        if (! empty($module_name)) {
            $moduleInfo = $this->moduleSQL->getInfoByName($module_name);
            $droitModuleInfo = $this->moduleSQL->getInfoModuleAuthority($moduleInfo['id'], $userInfo['authority_id']);
            $permUser = $this->moduleSQL->getInfoPerms($moduleInfo['id'], $user_id);

            return $this->canAccess($moduleInfo, $userInfo, $authorityInfo, $groupeInfo, $droitModuleInfo, $permUser, $droit_specific);
        }
        return true;
    }

    public function canAccess($moduleInfo, $userInfo, $authorityInfo, $groupeInfo, $droitModuleInfo, $permUser, $droit_specific = array())
    {

        if (! $moduleInfo) {
            return false;
        }

        if ($moduleInfo['status'] != 1) {
            return false;
        }

        if (! $userInfo || $userInfo['status'] != 1) {
            return false;
        }

        if (! $authorityInfo || $authorityInfo['status'] != 1) {
            return false;
        }
        if ($groupeInfo && $groupeInfo['status'] != 1) {
            return false;
        }
        if ($this->isGroupOrSuperAdmin($userInfo)) {
            return true;
        }
        if (! $droitModuleInfo) {
            return false;
        }
        if (! in_array($permUser, array('RO','RW'))) {
            if (! in_array($permUser, $droit_specific)) {
                return false;
            }
        }

        return true;
    }

    public function isGroupOrSuperAdmin(array $userInfo)
    {
        return UserRole::fromRole($userInfo['role'] ?? '')?->isGroupOrSuperAdmin() ?? false;
    }

    public function isSuperAdmin(array $userInfo)
    {
        return UserRole::fromRole($userInfo['role'] ?? '')?->isSuperAdmin() ?? false;
    }

    public function isAnyAdmin(array $userInfo)
    {
        return UserRole::fromRole($userInfo['role'] ?? '')?->isAnyAdmin() ?? false;
    }

    public function isGroupAdmin(array $userInfo)
    {
        return UserRole::fromRole($userInfo['role'] ?? '')?->isGroupAdmin() ?? false;
    }

    public function isAuthorityAdmin(array $userInfo)
    {
        return UserRole::fromRole($userInfo['role'] ?? '')?->isAuthorityAdmin() ?? false;
    }

    public function hasDroit(array $userInfo, array $authorityInfo)
    {

        if ($this->isSuperAdmin($userInfo)) {
            return true;
        }

        if (! $this->isGroupAdmin($userInfo) && ! $this->isAuthorityAdmin($userInfo)) {
            return false;
        }
        return $authorityInfo['authority_group_id'] == $userInfo['authority_group_id'];
    }
}
