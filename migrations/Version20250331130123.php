<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250331130123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute une sequence de maniere à autoincrementer l\'id authority_type. Cela homogeneise le foncitonnement du code ( vis a vis de la table authority_groups_id par exemple) et fix les entity auto genere par doctrine.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE authority_types_id_seq INCREMENT BY 1 START WITH 1');
        $this->addSql(
            'ALTER TABLE authority_types ALTER COLUMN id SET DEFAULT nextval(\'authority_types_id_seq\'::regclass)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE authority_types ALTER COLUMN id DROP DEFAULT');
        $this->addSql('DROP SEQUENCE authority_types_id_seq');
    }
}
