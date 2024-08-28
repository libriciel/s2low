<?php

namespace S2low\Tests\Legacy\fixtures;

use S2low\Legacy\S2lowLegacyCommandInSymfonyContainer;

class TestClass extends S2lowLegacyCommandInSymfonyContainer
{
    public function launchLegacyCommand(): void
    {
        // On fait rien, c'est juste un test
    }
}
