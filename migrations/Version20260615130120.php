<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260615130120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initialise la base de donnée.';
    }

    public function up(Schema $schema): void
    {
        $sql = file_get_contents(__DIR__ . '/../db/s2low.sql');

        foreach (explode(";", $sql) as $line) {
            $trimmedLine = trim($line);
            if ($trimmedLine === '') {
                continue;
            }

            if ($this->isAlterTable($trimmedLine)) {
                $constraintName = $this->getConstraintName($trimmedLine);
                if ($constraintName !== null && $this->constraintExists($constraintName)) {
                    continue;
                }
            }

            $this->createContrainte($trimmedLine);
        }
    }

    private function isAlterTable(string $sql): bool
    {
        return (bool) preg_match('/^\s*ALTER\s+TABLE/i', $sql);
    }

    private function getConstraintName(string $sql): ?string
    {
        if (preg_match('/ADD\s+CONSTRAINT\s+["\']?([a-zA-Z0-9_-]+)["\']?/i', $sql, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function constraintExists(string $name): bool
    {
        return (bool) $this->connection->fetchOne(
            "SELECT 1 FROM pg_constraint WHERE conname = ?",
            [$name]
        );
    }

    public function down(Schema $schema): void
    {
    }

    /**
     * @throws Exception
     */
    private function createContrainte(string $rqtContrainte): void
    {
        $this->connection->executeStatement($rqtContrainte);
    }
}
