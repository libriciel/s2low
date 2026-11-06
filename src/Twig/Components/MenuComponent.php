<?php

namespace S2low\Twig\Components;

use S2low\DTO\GroupAffiliation;
use S2low\Helpers\UserIdentityHelper;
use S2low\Security\LegacyAuthenticationBridge;
use S2low\Services\UserAffiliation;
use S2lowLegacy\Model\MessageAdminSQL;
use S2lowLegacy\Model\UserSQL;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('menu')]
class MenuComponent
{
    public function __construct(
        private readonly MessageAdminSQL $messageAdminSQL,
        private readonly UserAffiliation $userAffiliation,
        private readonly UserSQL $userSQL,
        private readonly LegacyAuthenticationBridge $authenticationBridge
    ) {
    }

    public function getInitials(): string
    {
        $user = $this->authenticationBridge->getAuthenticatedUser();
        if (! $user) {
            return '';
        }

        return UserIdentityHelper::getInitials($user->getGivenname(), $user->getName());
    }

    /**
     * @return list<GroupAffiliation>
     */
    public function getGroups(): array
    {
        $user = $this->authenticationBridge->getAuthenticatedUser();
        if (! $user) {
            return [];
        }

        return $this->userAffiliation->getGroups(
            $user->getRole(),
            $user->getAuthorityId(),
            $user->getAuthorityGroupId()
        );
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
