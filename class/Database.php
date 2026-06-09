<?php

namespace S2lowLegacy\Class;

use Exception;
use PDO;
use Psr\Log\LoggerInterface;
use S2lowLegacy\Lib\SQLQuery;

class Database
{
    /** @var SQLQuery */
    private $sqlQuery;

    private $is_in_a_transaction;

    /**
     * @var bool indique s'il y a une erreur dans la transaction
     */
    private $has_transaction_error;

    public function __construct(
        private readonly LoggerInterface $logger,
        SQLQuery $sqlQuery
    )
    {
        $this->is_in_a_transaction = false;
        $this->has_transaction_error = false;
        $this->sqlQuery = $sqlQuery;
    }

    /**
     * renvoie une ressource sur QueryResult
     * @param $query
     * @return QueryResult
     * @throws Exception
     */
    public function select($query, array $parameters = [])
    {
        $this->logger->debug($query);
        $pdoStatement = $this->sqlQuery->getPdo()->prepare($query);
        $pdoStatement->execute($parameters);
        return new QueryResult($pdoStatement);
    }

    /**
     * @param $query
     * @return bool
     * @throws Exception
     */
    public function exec($query, $params = [])
    {
        if (! is_array($params)) {
            $params = func_get_args();
            array_shift($params);
        }
        $this->sqlQuery->query($query, $params);
        return true;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function begin()
    {
        if ($this->is_in_a_transaction) {
            return 0;
        }
        $this->exec("BEGIN");
        $this->is_in_a_transaction = true;
        $this->has_transaction_error = false;
        return 1;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function commit()
    {
        if (!$this->is_in_a_transaction) {
            return 0;
        }
        $this->exec("COMMIT");
        $this->is_in_a_transaction = false;
        if (! $this->has_transaction_error) {
            return 1;
        }
        return 0;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function rollback()
    {
        if (!$this->is_in_a_transaction) {
            return 0;
        }
        $this->exec("ROLLBACK");
        $this->is_in_a_transaction = false;
        return 1;
    }

    /**
     * @param $sql
     * @return array
     * @throws Exception
     */
    public function getOneLine($sql, $params = [])
    {
        if (! is_array($params)) {
            $params = func_get_args();
            array_shift($params);
        }
        return $this->sqlQuery->queryOne($sql, $params);
    }

    /**
     * @param $sql
     * @return bool|mixed
     * @throws Exception
     */
    public function getOneValue($sql, $params = [])
    {
        if (! is_array($params)) {
            $params = func_get_args();
            array_shift($params);
        }
        return $this->sqlQuery->queryOne($sql, $params);
    }

    /**
     * @param $sql
     * @return array
     * @throws Exception
     */
    public function fetchAll($sql, $param = [])
    {
        return $this->sqlQuery->query($sql, $param);
    }

    public function getPdo(): PDO
    {
        return $this->sqlQuery->getPdo();
    }

    public function disconnect(): void
    {
        $this->sqlQuery->disconnect();
    }
}
