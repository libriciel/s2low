<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616121933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initialise les données nécéssaires au bon fonctionnement de s2low (authority_types, modules, actes_natures, actes_status, authority_departments, authority_districts, helios_status).';
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
