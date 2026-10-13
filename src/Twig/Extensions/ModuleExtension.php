<?php

namespace S2low\Twig\Extensions;

use S2low\DTO\ModuleMenu;
use S2low\Enum\Module;
use S2low\Enum\UserRole;
use S2low\Security\SecurityUser;
use S2lowLegacy\Model\ModuleSQL;
use S2lowLegacy\Model\UserSQL;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ModuleExtension extends AbstractExtension
{
    public function __construct(
        private readonly ModuleSQL $moduleSQL,
        private readonly UserSQL $userSQL,
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
        $userInfo = $this->userSQL->getInfo($user->getId());

        return new ModuleMenu(UserRole::from($userInfo['role']), $this->moduleSQL->getModulesForUser($userInfo));
    }

    public function getUserPermOnModule(string $moduleName, int $userId): string
    {
        return $this->moduleSQL->getInfoPerms(Module::fromName($moduleName)->id(), $userId);
    }
}
