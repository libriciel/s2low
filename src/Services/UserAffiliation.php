<?php

namespace S2low\Services;

use S2low\DTO\AdministeringGroups;
use S2low\DTO\GroupAffiliation;
use S2low\Enum\AdministeredModule;
use S2low\Enum\UserRole;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\GroupSQL;

class UserAffiliation
{
    public function __construct(
        private readonly AuthoritySQL $authoritySQL,
        private readonly GroupSQL $groupSQL
    ) {
    }

    /**
     * @return list<GroupAffiliation>
     */
    public function getGroups(?string $role, ?int $authorityId, ?int $authorityGroupId): array
    {
        $userRole = UserRole::fromRole($role);
        if ($userRole === null || $userRole->isSuperAdmin()) {
            return [];
        }

        if ($userRole->isGroupAdmin()) {
            return $this->affiliate([(int) $authorityGroupId => []]);
        }

        return $this->affiliate($this->modulesByAdministeringGroup($authorityId));
    }

    /**
     * @return array<int, list<AdministeredModule>>
     */
    private function modulesByAdministeringGroup(?int $authorityId): array
    {
        if (! $authorityId) {
            return [];
        }

        $administeringGroups = AdministeringGroups::fromAuthorityInfo($this->authoritySQL->getInfo($authorityId) ?: []);

        $modulesByGroupId = [];
        foreach (AdministeredModule::cases() as $module) {
            if ($administeringGroups->isDesignatedFor($module)) {
                $modulesByGroupId[$administeringGroups->groupIdFor($module)][] = $module;
            }
        }

        return $modulesByGroupId;
    }

    /**
     * @param array<int, list<AdministeredModule>> $modulesByGroupId
     * @return list<GroupAffiliation>
     */
    private function affiliate(array $modulesByGroupId): array
    {
        $affiliations = [];
        foreach ($modulesByGroupId as $groupId => $modules) {
            $groupName = $groupId ? $this->readName($this->groupSQL->getInfo($groupId)) : null;
            if ($groupName !== null) {
                $affiliations[] = new GroupAffiliation($groupName, $modules);
            }
        }

        return $affiliations;
    }

    /** @param mixed $info */
    private function readName($info): ?string
    {
        return is_array($info) && !empty($info['name']) ? $info['name'] : null;
    }
}
