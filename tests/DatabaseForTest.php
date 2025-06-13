<?php

namespace S2low\Tests;

use S2lowLegacy\Class\Database;

/**
 * Cette classe permet de remplacer Database
 * en environnement de test et donc d'eviter de faire des
 * 'COMMIT' et 'ROLLBACK'
 */
class DatabaseForTest extends Database
{
    public function begin(): bool
    {
        return true;
    }

    public function commit(): bool
    {
        return true;
    }

    public function rollback(): bool
    {
        return true;
    }
}
