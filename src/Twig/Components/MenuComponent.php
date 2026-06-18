<?php

namespace S2low\Twig\Components;

use S2lowLegacy\Model\MessageAdminSQL;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('menu')]
class MenuComponent
{
    public function __construct(
        private readonly MessageAdminSQL $messageAdminSQL
    ) {
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
