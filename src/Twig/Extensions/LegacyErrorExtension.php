<?php

namespace S2low\Twig\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class LegacyErrorExtension extends AbstractExtension
{
    /**
     * Set to false to completely disable retrieval and rendering of legacy $_SESSION['error'] messages.
     */
    private const ENABLE_LEGACY_ERRORS = true;

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_legacy_error', [$this, 'getLegacyError']),
        ];
    }

    /**
     * Retrieve the legacy error from $_SESSION['error'] and clear it.
     */
    public function getLegacyError(): ?string
    {
        if (!self::ENABLE_LEGACY_ERRORS) {
            return null;
        }

        if ((session_status() === PHP_SESSION_ACTIVE || isset($_SESSION)) && isset($_SESSION['error'])) {
            $error = $_SESSION['error'];
            unset($_SESSION['error']);
            return (string) $error;
        }

        return null;
    }
}
