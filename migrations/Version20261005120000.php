<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261005120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des clés étrangères de actes_group_id et helios_group_id vers authority_groups : un groupe désigné par une collectivité ne peut plus être supprimé.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE authorities
                    ADD CONSTRAINT authorities_actes_group_id_fk FOREIGN KEY (actes_group_id) REFERENCES authority_groups (id),
                    ADD CONSTRAINT authorities_helios_group_id_fk FOREIGN KEY (helios_group_id) REFERENCES authority_groups (id)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE authorities
                    DROP CONSTRAINT authorities_actes_group_id_fk,
                    DROP CONSTRAINT authorities_helios_group_id_fk'
        );
    }
}
