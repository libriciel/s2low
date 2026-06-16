<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616121933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $sqlFilePath = __DIR__ . '/../db/init_data.sql';

        $sqlContent = file_get_contents($sqlFilePath);
        $queries = explode(';', $sqlContent);

        foreach ($queries as $query) {
            $query = trim($query);

            if (!empty($query)) {
                $this->addSql($query);
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
