<?php

declare(strict_types=1);

namespace S2low\DTO;

use S2low\Enum\AdministeredModule;

/**
 * Ce que le formulaire de collectivité demande pour les modules dont l'administration est déléguée :
 * quels modules sont activés, et quel groupe le super administrateur a choisi pour chacun.
 *
 * Un identifiant de collectivité à 0 désigne une création.
 */
final readonly class ModuleActivationRequest
{
    /**
     * @param array<int, bool> $activatedByModule indexé par AdministeredModule::value
     * @param array<int, int> $chosenGroupIdByModule indexé par AdministeredModule::value
     */
    public function __construct(
        private int $authorityId,
        private string $siren,
        private array $activatedByModule,
        private array $chosenGroupIdByModule,
    ) {
    }

    public function authorityId(): int
    {
        return $this->authorityId;
    }

    public function siren(): string
    {
        return $this->siren;
    }

    public function isCreation(): bool
    {
        return $this->authorityId === 0;
    }

    public function activates(AdministeredModule $module): bool
    {
        return $this->activatedByModule[$module->value] ?? false;
    }

    public function chosenGroupIdFor(AdministeredModule $module): int
    {
        return $this->chosenGroupIdByModule[$module->value] ?? 0;
    }
}
