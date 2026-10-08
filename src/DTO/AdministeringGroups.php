<?php

declare(strict_types=1);

namespace S2low\DTO;

use S2low\Enum\AdministeredModule;

final readonly class AdministeringGroups
{
    private array $groupIdByModule;

    /**
     * @param array<int, int> $groupIdByModule indexé par AdministeredModule::value
     */
    private function __construct(array $groupIdByModule)
    {
        $this->groupIdByModule = array_filter($groupIdByModule, static fn(int $groupId): bool => $groupId !== 0);
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
            $groupIdByModule[$module->value] = (int)($authorityInfo[$module->groupColumn()] ?? 0);
        }

        return new self($groupIdByModule);
    }

    public function designate(AdministeredModule $module, int $groupId): self
    {
        $groupIdByModule = $this->groupIdByModule;
        $groupIdByModule[$module->value] = $groupId;

        return new self($groupIdByModule);
    }

    public function withdraw(AdministeredModule $module): self
    {
        $groupIdByModule = $this->groupIdByModule;
        unset($groupIdByModule[$module->value]);

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
