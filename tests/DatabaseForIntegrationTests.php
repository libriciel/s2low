<?php

namespace S2low\Tests;

use S2lowLegacy\Class\Database;

/**
 * Cette classe permet de remplacer Database
 * en environnement de test d'integration et donc d'eviter de faire des
 * 'COMMIT' et 'ROLLBACK'
 */
class DatabaseForIntegrationTests extends Database
{
    public function begin()
    {
        return true;
    }

    public function commit()
    {
        return true;
    }

    public function rollback()
    {
        return true;
    }
}
