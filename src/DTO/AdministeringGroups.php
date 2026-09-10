<?php

declare(strict_types=1);

namespace S2low\DTO;

use S2low\Enum\AdministeredModule;

final readonly class AdministeringGroups
{
    /**
     * @param array<int, int> $groupIdByModule indexé par AdministeredModule::value
     */
    private function __construct(private array $groupIdByModule)
    {
    }

    public static function none(): self
    {
        return new self([]);
    }

    /**
     * @param array<string, mixed> $authorityInfo
     */
    public static function fromAuthorityInfo(array $authorityInfo): self
    {
        $groupIdByModule = [];

        foreach (AdministeredModule::cases() as $module) {
            $groupId = (int)($authorityInfo[$module->groupColumn()] ?? 0);
            if ($groupId !== 0) {
                $groupIdByModule[$module->value] = $groupId;
            }
        }

        return new self($groupIdByModule);
    }

    public function designate(AdministeredModule $module, int $groupId): self
    {
        $groupIdByModule = $this->groupIdByModule;
        $groupIdByModule[$module->value] = $groupId;

        return new self($groupIdByModule);
    }

    public function isDesignatedFor(AdministeredModule $module): bool
    {
        return isset($this->groupIdByModule[$module->value]);
    }

    public function groupIdFor(AdministeredModule $module): int
    {
        return $this->groupIdByModule[$module->value] ?? 0;
    }

    /**
     * Le groupe administre la collectivité dès qu'elle le désigne pour un seul de ses modules.
     *
     * Un groupe inexistant n'administre rien : sans cette garde, une collectivité qui ne désigne
     * personne se laisserait administrer par n'importe qui.
     */
    public function isAdministeredBy(int $groupId): bool
    {
        if ($groupId === 0) {
            return false;
        }

        return in_array($groupId, $this->groupIdByModule, true);
    }

    public function administers(AdministeredModule $module, int $groupId): bool
    {
        return $groupId !== 0 && $this->groupIdFor($module) === $groupId;
    }

    /**
     * @return int[]
     */
    public function groupIds(): array
    {
        return array_values(array_unique($this->groupIdByModule));
    }

    public function isEmpty(): bool
    {
        return $this->groupIdByModule === [];
    }
}
