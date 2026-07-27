<?php

namespace S2low\Services;

use S2low\Enum\UserRole;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\GroupSQL;

/**
 * Périmètre sur lequel un utilisateur agit, tel qu'il est affiché dans le menu.
 *
 * Partagé par le composant Twig et le menu historique (S2lowLegacy\Class\MenuHTML).
 */
class UserAffiliation
{
    public const TYPE_AUTHORITY = 'authority';
    public const TYPE_GROUP = 'group';

    public function __construct(
        private readonly AuthoritySQL $authoritySQL,
        private readonly GroupSQL $groupSQL
    ) {
    }

    public function getType(?string $role): ?string
    {
        $userRole = UserRole::fromRole($role);
        if ($userRole === null || $userRole->isSuperAdmin()) {
            return null;
        }

        return $userRole->isGroupAdmin() ? self::TYPE_GROUP : self::TYPE_AUTHORITY;
    }

    public function getName(?string $role, ?int $authorityId, ?int $authorityGroupId): ?string
    {
        $type = $this->getType($role);
        if ($type === null) {
            return null;
        }

        if ($type === self::TYPE_GROUP) {
            return $authorityGroupId ? $this->readName($this->groupSQL->getInfo($authorityGroupId)) : null;
        }

        return $authorityId ? $this->readName($this->authoritySQL->getInfo($authorityId)) : null;
    }

    /** @param mixed $info */
    private function readName($info): ?string
    {
        return is_array($info) && !empty($info['name']) ? $info['name'] : null;
    }
}
