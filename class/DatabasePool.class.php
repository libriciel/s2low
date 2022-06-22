<?php

/**
 * Class DatabasePool
 */
class DatabasePool
{
    public static function getInstance()
    {
        $objectInstancier = ObjectInstancierFactory::getObjetInstancier();
        $sqlQuery = $objectInstancier->get('SQLQuery');
        return new Database($sqlQuery);
    }
}
