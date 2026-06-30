<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624120414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs helios_group_id et actes_group_id à la table authorities. Ils sont initialisés avec la valeur de authority_group_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE authorities
                    ADD helios_group_id INT DEFAULT NULL,
                    ADD actes_group_id INT DEFAULT NULL'
        );

        $this->addSql(
            'UPDATE authorities SET
                    helios_group_id = authority_group_id,
                    actes_group_id = authority_group_id'
        );

    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE authorities
                    DROP COLUMN helios_group_id,
                    DROP COLUMN actes_group_id'
        );
    }
}
