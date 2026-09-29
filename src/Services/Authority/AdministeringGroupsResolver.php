<?php

declare(strict_types=1);

namespace S2low\Services\Authority;

use S2low\DTO\AdministeringGroups;
use S2low\DTO\ModuleActivationRequest;
use S2low\Enum\AdministeredModule;
use S2low\Exceptions\GroupDesignationRefusedException;
use S2low\Security\SecurityUser;
use S2lowLegacy\Model\AuthorityGroupSirenSQL;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\GroupSQL;

final readonly class AdministeringGroupsResolver
{
    public function __construct(
        private AuthoritySQL $authoritySQL,
        private AuthorityGroupSirenSQL $authorityGroupSirenSQL,
        private GroupSQL $groupSQL,
    ) {
    }

    /**
     * @throws GroupDesignationRefusedException
     */
    public function resolve(ModuleActivationRequest $request, SecurityUser $user): AdministeringGroups
    {
        $current = $this->currentGroupsOf($request->authorityId());
        $designated = $current;

        foreach (AdministeredModule::cases() as $module) {
            if (! $request->activates($module)) {
                continue;
            }

            $groupId = $user->isSuperAdmin()
                ? $this->chosenGroupFor($module, $request, $current)
                : $this->currentOrOwnGroupFor($module, $current, $user);

            $designated = $designated->designate($module, $groupId);
        }

        if ($request->isCreation() && $designated->isEmpty()) {
            throw new GroupDesignationRefusedException(
                "Une collectivité doit être administrée par au moins un groupe : activez un module et désignez son groupe."
            );
        }

        return $designated;
    }

    /**
     * @throws GroupDesignationRefusedException
     */
    private function currentOrOwnGroupFor(
        AdministeredModule $module,
        AdministeringGroups $current,
        SecurityUser $user
    ): int {
        if ($current->isDesignatedFor($module)) {
            return $current->groupIdFor($module);
        }

        $ownGroupId = (int)$user->getAuthorityGroupId();

        if ($ownGroupId === 0) {
            throw new GroupDesignationRefusedException(
                "Aucun groupe n'administre le module {$module->label()} pour cette collectivité."
            );
        }

        return $ownGroupId;
    }

    /**
     * @throws GroupDesignationRefusedException
     */
    private function chosenGroupFor(
        AdministeredModule $module,
        ModuleActivationRequest $request,
        AdministeringGroups $current
    ): int {
        $chosenGroupId = $request->chosenGroupIdFor($module);

        if ($chosenGroupId === 0) {
            throw new GroupDesignationRefusedException(
                "Le module {$module->label()} est activé : désignez le groupe qui l'administre."
            );
        }

        if ($chosenGroupId === $current->groupIdFor($module)) {
            return $chosenGroupId;
        }

        if (! $this->groupSQL->isActive($chosenGroupId)) {
            throw new GroupDesignationRefusedException(
                "Le groupe désigné pour le module {$module->label()} est désactivé."
            );
        }

        if ($request->siren() !== '' && ! $this->authorityGroupSirenSQL->exist($chosenGroupId, $request->siren())) {
            throw new GroupDesignationRefusedException(
                "Le groupe désigné pour le module {$module->label()} ne détient pas le SIREN {$request->siren()}."
            );
        }

        return $chosenGroupId;
    }

    private function currentGroupsOf(int $authorityId): AdministeringGroups
    {
        if ($authorityId === 0) {
            return AdministeringGroups::none();
        }

        return AdministeringGroups::fromAuthorityInfo($this->authoritySQL->getInfo($authorityId) ?: []);
    }
}
