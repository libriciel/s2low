<?php

namespace S2low\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    public function __construct(
        public readonly array $user
    ) {
    }


    /**
     * @inheritDoc
     */
    public function getRoles(): array
    {
        return [$this->user['role']];
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function getUserIdentifier(): string
    {
        return $this->user['id'];
    }
}
