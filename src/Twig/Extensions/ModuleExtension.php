<?php

namespace S2low\Twig\Extensions;

use S2low\DTO\ModuleMenu;
use S2low\Enum\Module;
use S2low\Enum\UserRole;
use S2low\Security\SecurityUser;
use S2lowLegacy\Model\GroupSQL;
use S2lowLegacy\Model\ModuleSQL;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ModuleExtension extends AbstractExtension
{
    public function __construct(
        private readonly ModuleSQL $moduleSQL,
        private readonly GroupSQL $groupSQL,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getModuleMenu', [$this, 'getModuleMenu']),
            new TwigFunction('getUserPermOnModule', [$this, 'getUserPermOnModule']),
        ];
    }

    public function getModuleMenu(SecurityUser $user): ModuleMenu
    {
        $accessibleModules = $this->moduleSQL->getModulesForUser([
            'id' => $user->getId(),
            'role' => $user->getRole(),
            'authority_id' => $user->getAuthorityId(),
        ]);

        $modulesAdministeredByGroup = $user->isGroupAdmin()
            ? $this->groupSQL->getAdministeredModules((int)$user->getAuthorityGroupId())
            : [];

        return new ModuleMenu(UserRole::from($user->getRole()), $accessibleModules, $modulesAdministeredByGroup);
    }

    public function getUserPermOnModule(string $moduleName, int $userId): string
    {
        return $this->moduleSQL->getInfoPerms(Module::fromName($moduleName)->id(), $userId);
    }
}
