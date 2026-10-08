<?php

declare(strict_types=1);

use Symfony\Bundle\SecurityBundle\Security;

class LegacyContainerTest extends S2lowTestCase
{
    // Le conteneur de test expose aussi les services privés : seul le conteneur réel dit ce
    // qu'un script legacy peut obtenir de l'ObjectInstancier.
    public function testLegacyScriptsReachTheSecurityHelper(): void
    {
        $realContainer = self::bootKernel()->getContainer();

        static::assertTrue($realContainer->has(Security::class));
    }
}
