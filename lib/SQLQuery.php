<?php

namespace S2lowLegacy\Lib;

use Closure;
use Exception;
use PDO;
use PDOStatement;
use S2lowLegacy\Class\LegacyObjectsManager;
use Doctrine\DBAL\Connection;

class SQLQuery
{
    private const SLOW_QUERY_IN_MS = 2000;

    private $slow_query_in_ms;

    /** @var  PDOStatement */
    private $lastPdoStatement;
    private $nextResult;
    private $hasMoreResult;

    public function __construct(
        private ?PDO $pdo,
        private readonly ?Connection $connection = null
    ) {
        $this->setSlowQuery(self::SLOW_QUERY_IN_MS);
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
        $this->pdo = null;
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

    private function handleTransactionQuery(string $query): bool
    {
        $trimmed = strtoupper(trim($query, " \t\n\r\0\x0B;"));
        if ($trimmed === 'BEGIN') {
            if ($this->connection) {
                $this->connection->beginTransaction();
            } else {
                $this->getPdo()->beginTransaction();
            }
            return true;
        } elseif ($trimmed === 'COMMIT') {
            if ($this->connection) {
                if ($this->connection->isTransactionActive()) {
                    $this->connection->commit();
                }
            } else {
                if ($this->getPdo()->inTransaction()) {
                    $this->getPdo()->commit();
                }
            }
            return true;
        } elseif ($trimmed === 'ROLLBACK') {
            if ($this->connection) {
                if ($this->connection->isTransactionActive()) {
                    $this->connection->rollBack();
                }
            } else {
                if ($this->getPdo()->inTransaction()) {
                    $this->getPdo()->rollBack();
                }
            }
            return true;
        }
        return false;
    }

    public function query($query, $param = false): array
    {
        if ($this->handleTransactionQuery($query)) {
            return [];
        }
        $start = microtime(true);
        if (!is_array($param)) {
            $param = func_get_args();
            array_shift($param);
        }
        try {
            $pdoStatement = $this->getPdo()->prepare($query);
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . " - " . $query);
        }

        try {
            $pdoStatement->execute($param);
        } catch (Exception $e) {
            throw new Exception(
                $e->getMessage() . " - " . $pdoStatement->queryString
            );
        }
        $result = array();
        if ($pdoStatement->columnCount()) {
            $result = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        }

        $duration = microtime(true) - $start;
        if ($duration > $this->slow_query_in_ms) {
            $requete = $pdoStatement->queryString . "|" . implode(",", $param);
            trigger_error("Requete lente ({$duration}ms): $requete", E_USER_WARNING);
        } // @codeCoverageIgnore

        return $result;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
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
        $this->lastPdoStatement = $this->getPdo()->prepare($query);
        $this->lastPdoStatement->execute($param);
        $this->hasMoreResult = true;
        $this->fetch();
    }

    public function fetch()
    {
        $result = $this->nextResult;
        $this->nextResult = $this->lastPdoStatement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT);

        if (!$this->nextResult) {
            $this->hasMoreResult = false;
        }
        return $result;
    }

    public function exec($query): void
    {
        if ($this->handleTransactionQuery($query)) {
            return;
        }
        $this->getPdo()->exec($query);
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
