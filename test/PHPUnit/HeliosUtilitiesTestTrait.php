<?php

use S2lowLegacy\Lib\SQLQuery;

trait HeliosUtilitiesTestTrait
{
    protected function createTransaction($authority_id = 1)
    {
        $sql = "INSERT INTO helios_transactions(user_id,authority_id,last_status_id,filename,sha1) VALUES (?,?,?,?,?) returning ID;";
        return $this->getSQLQuery()->queryOne($sql, 1, $authority_id, 4, "toto.txt", "ab3321d34d3fb32b52332befa534c9854fff677b");
    }

    /**
     * @return SQLQuery
     */
    abstract public function getSQLQuery();
}
