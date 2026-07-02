<?php

namespace S2lowLegacy\Lib;

use Closure;
use Exception;
use S2lowLegacy\Class\LegacyObjectsManager;

class SQLQuery
{
    private const SLOW_QUERY_IN_MS = 2000;

    private $slow_query_in_ms;

    /** @var  \Doctrine\DBAL\Result */
    private $lastResult;
    private $nextResult;
    private $hasMoreResult;

    public function __construct(
        private readonly ?\Doctrine\DBAL\Connection $connection = null
    ) {
        $this->setSlowQuery(self::SLOW_QUERY_IN_MS);
    }

    public function getConnection(): \Doctrine\DBAL\Connection
    {
        if ($this->connection === null) {
            throw new Exception("Doctrine DBAL Connection was not injected into SQLQuery.");
        }
        return $this->connection;
    }

    public function setSlowQuery($millisecond): void
    {
        $this->slow_query_in_ms = $millisecond;
    }

    public function sleep($time_in_second)
    {
        $this->disconnect();
        sleep($time_in_second);
    }

    public function disconnect(): void
    {
    }

    public function queryOne($query, $param = false)
    {

        if (!is_array($param)) {
            $param = func_get_args();
            array_shift($param);
        }
        $result = $this->query($query, $param);
        if (!$result) {
            return false;
        }

        $result = $result[0];
        if (count($result) == 1) {
            return reset($result);
        }
        return $result;
    }

    public function query($query, $param = false): array
    {
        $start = microtime(true);
        if (!is_array($param)) {
            $param = func_get_args();
            array_shift($param);
        }
        try {
            $resultObject = $this->getConnection()->executeQuery($query, $param);
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . " - " . $query);
        }

        $result = array();
        if ($resultObject->columnCount()) {
            $result = $resultObject->fetchAllAssociative();
        }

        $duration = microtime(true) - $start;
        if ($duration > $this->slow_query_in_ms) {
            $requete = $query . "|" . implode(",", $param);
            trigger_error("Requete lente ({$duration}ms): $requete", E_USER_WARNING);
        } // @codeCoverageIgnore

        return $result;
    }


    public function queryOneCol($query, $param = false): array
    {
        if (!is_array($param)) {
            $param = func_get_args();
            array_shift($param);
        }
        $result = $this->query($query, $param);
        if (!$result) {
            return array();
        }
        $r = array();
        foreach ($result as $line) {
            $line = array_values($line);
            $r[] = $line[0];
        }
        return $r;
    }

    public function prepareAndExecute($query, $param = false): void
    {
        if (!is_array($param)) {
            $param = func_get_args();
            array_shift($param);
        }
        $this->lastResult = $this->getConnection()->executeQuery($query, $param);
        $this->hasMoreResult = true;
        $this->fetch();
    }

    public function fetch()
    {
        $result = $this->nextResult;
        $this->nextResult = $this->lastResult->fetchAssociative();

        if (!$this->nextResult) {
            $this->hasMoreResult = false;
        }
        return $result;
    }

    public function exec($query): void
    {
        $this->getConnection()->executeStatement($query);
    }

    public function hasMoreResult()
    {
        return $this->hasMoreResult;
    }

    public function waitStarting(Closure $log_function, $nb_retry_max = 60): bool
    {
        $connected = false;
        $nb_retry = 0;
        do {
            try {
                $nb_retry++;

                $this->query("SELECT 1;");
                $log_function("PostgreSQL est maintenant démarré");
                $connected = true;
            } catch (Exception $e) {
                $log_function("[essai $nb_retry] PostgreSQL n'a pas démarré ... on attend une seconde de plus");
                sleep(1);
            }
        } while (!$connected && $nb_retry < $nb_retry_max);

        if (!$connected) {
            $log_function("PostgreSQL n'a pas démarré après $nb_retry essai...");
        }
        return $connected;
    }
}
