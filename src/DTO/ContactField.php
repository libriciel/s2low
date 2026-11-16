<?php

declare(strict_types=1);

namespace S2low\DTO;

final readonly class ContactField
{
    public string $value;

    public function __construct(
        string $value,
        public bool $isShown
    ) {
        $this->value = trim($value);
    }

    public function isDisplayed(): bool
    {
        return $this->isShown && $this->value !== '';
    }
}
