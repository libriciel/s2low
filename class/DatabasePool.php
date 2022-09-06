<?php

namespace S2lowLegacy\Class;

use S2lowLegacy\Lib\SQLQuery;

/**
 * Class DatabasePool
 */
class DatabasePool
{
    public static function getInstance()
    {
        $objectInstancier = \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier();
        $sqlQuery = $objectInstancier->get(SQLQuery::class);
        return new Database($sqlQuery);
    }
}
