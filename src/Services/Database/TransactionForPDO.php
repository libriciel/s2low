<?php

namespace S2low\Services\Database;

use PDO;
use PDOException;

class TransactionForPDO
{
    private const SAVEPOINT_NAME = 'savepoint_test';
    private bool $enCours = false;
    public function __construct(
        private ?PDO $pdo
    ) {
    }

    public function beginTestTransaction(): void
    {
        if ($this->enCours) {
            throw new PDOException('Transaction déja démarré');
        }

        $this->pdo->beginTransaction();
        $this->pdo->exec("SAVEPOINT " . self::SAVEPOINT_NAME);
        $this->enCours = true;
    }

    public function rollbackTestTransaction(): void
    {
        if (!$this->enCours) {
            throw new PDOException('Aucune transaction en cours');
        }

        $this->pdo->exec('ROLLBACK TO SAVEPOINT ' . self::SAVEPOINT_NAME);
        $this->pdo->exec('RELEASE SAVEPOINT ' . self::SAVEPOINT_NAME);
        $this->pdo->rollBack();
        $this->pdo = null;
        $this->enCours = false;
    }
}
