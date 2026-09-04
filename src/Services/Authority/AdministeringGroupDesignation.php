<?php

declare(strict_types=1);

namespace S2low\Services\Authority;

use S2low\DTO\AdministeringGroups;
use S2low\DTO\ModuleActivationRequest;
use S2low\Enum\AdministeredModule;
use S2low\Exceptions\GroupDesignationRefusedException;
use S2low\Security\SecurityUser;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\GroupSQL;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Quels groupes administreront les modules de la collectivité après cet enregistrement ?
 *
 * Le super administrateur les choisit ; l'administrateur de groupe ne choisit pas : son propre
 * groupe prend un module qu'aucun groupe n'administre encore, ce qui n'arrive qu'à la création.
 * Décocher un module ne retire pas sa désignation, l'administrateur du groupe reste le sien et
 * peut le réactiver.
 */
final readonly class AdministeringGroupDesignation
{
    public function __construct(
        private AuthoritySQL $authoritySQL,
        private GroupSQL $groupSQL,
        private Security $security,
    ) {
    }

    /**
     * @throws GroupDesignationRefusedException
     */
    public function resolve(ModuleActivationRequest $request): AdministeringGroups
    {
        $current = $this->currentGroupsOf($request->authorityId());
        $designated = $current;

        foreach (AdministeredModule::cases() as $module) {
            if (! $request->activates($module)) {
                continue;
            }

            $designated = $designated->designate(
                $module,
                $this->groupTaking($module, $request, $current)
            );
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
    private function groupTaking(
        AdministeredModule $module,
        ModuleActivationRequest $request,
        AdministeringGroups $current
    ): int {
        $user = $this->security->getUser();

        if ($user instanceof SecurityUser && $user->isSuperAdmin()) {
            return $this->chosenGroup($module, $request->chosenGroupIdFor($module), $current);
        }

        if ($current->isDesignatedFor($module)) {
            return $current->groupIdFor($module);
        }

        $ownGroupId = $user instanceof SecurityUser ? (int)$user->getAuthorityGroupId() : 0;

        if ($ownGroupId === 0) {
            throw new GroupDesignationRefusedException(
                "Aucun groupe n'administre le module {$module->label()} pour cette collectivité."
            );
        }

        return $ownGroupId;
    }

    /**
     * Un groupe désactivé après coup reste désignable tant qu'on ne le change pas : sinon la
     * collectivité ne serait plus enregistrable sans lui changer de groupe en silence.
     *
     * @throws GroupDesignationRefusedException
     */
    private function chosenGroup(AdministeredModule $module, int $chosenGroupId, AdministeringGroups $current): int
    {
        if ($chosenGroupId === 0) {
            throw new GroupDesignationRefusedException(
                "Le module {$module->label()} est activé : désignez le groupe qui l'administre."
            );
        }

        if ($chosenGroupId !== $current->groupIdFor($module) && ! $this->groupSQL->isActive($chosenGroupId)) {
            throw new GroupDesignationRefusedException(
                "Le groupe désigné pour le module {$module->label()} est désactivé."
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
