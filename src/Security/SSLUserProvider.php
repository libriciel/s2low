<?php

namespace S2low\Security;

use S2low\Entity\User;
use S2lowLegacy\Model\UserSQL;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SSLUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly UserSQL $userSql,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        return $user;
    }

    /**
     * @inheritDoc
     */
    public function supportsClass(string $class): bool
    {
        return $class === UserInterface::class;
    }

    /**
     * @inheritDoc
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userSql->getInfo($identifier);

        return new User(
            $user
        );
    }
}
