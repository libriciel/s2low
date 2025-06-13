<?php

namespace S2lowLegacy\Lib;

use S2lowLegacy\Class\Database;

abstract class SQL
{
    public function __construct(
        private readonly SQLQuery $sqlQuery,
        protected readonly Database $database,
    ) {
    }

    public function query($query, $param = false)
    {
        if (!is_array($param)) {
            $param = func_get_args();
            array_shift($param);
        }

        return $this->database->query($query, $param);
    }

    public function queryOne($query, $params = false)
    {
        if (! is_array($params)) {
            $params = func_get_args();
            array_shift($params);
        }

        return $this->database->getOneLine($query, $params);
    }

    public function queryOneCol($query, $params = false)
    {
        if (! is_array($params)) {
            $params = func_get_args();
            array_shift($params);
        }

        return $this->database->getOneCol($query, $params);
    }

    protected function getSQLQuery()
    {
        return $this->sqlQuery;
    }
}
