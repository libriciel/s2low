<?php

namespace S2low\Twig\Components;

use S2low\Security\LegacyAuthenticationBridge;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\MessageAdminSQL;
use S2lowLegacy\Model\UserSQL;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('menu')]
class MenuComponent
{
    public function __construct(
        private readonly MessageAdminSQL $messageAdminSQL,
        private readonly AuthoritySQL $authoritySQL,
        private readonly UserSQL $userSQL,
        private readonly LegacyAuthenticationBridge $authenticationBridge
    ) {
    }

    public function getAuthorityNameForAuthorityAdmin(): ?string
    {
        $user = $this->authenticationBridge->getAuthenticatedUser();
        if (! $user || ! $user->isAuthorityAdmin()) {
            return null;
        }

        return $this->authoritySQL->getInfo($user->getAuthorityId())['name'] ?? null;
    }

    public function isCertificateSharedWithOtherUsers(): bool
    {
        $user = $this->authenticationBridge->getAuthenticatedUser();
        if (! $user) {
            return false;
        }

        return (int) $this->userSQL->getNbUserWithMyCertificate($user->getCertificateHash()) > 1;
    }

    public function getMessageAdminTitle(): ?string
    {
        $messageAdminOrNull = $this->messageAdminSQL->getMessages();
        if (!empty($messageAdminOrNull)) {
                $title = $messageAdminOrNull['titre'];
        } else {
            $title = null;
        }

        return $title;
    }

    public function getCssLevel(): int
    {
        $messageAdminOrNull = $this->messageAdminSQL->getMessages();
        $cssLevel = 0;

        if (!empty($messageAdminOrNull)) {
                $cssLevel = $messageAdminOrNull['niveau'];
        }

        return $cssLevel;
    }
}
