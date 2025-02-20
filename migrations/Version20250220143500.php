<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220143500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute une colonne id dans la table service_user_content pour permettre à Doctrine de generer les entites automatiquement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service_user_content ADD id SERIAL PRIMARY KEY');
        $this->addSql('ALTER TABLE actes_type_pj ADD id SERIAL PRIMARY KEY');
        $this->addSql('ALTER TABLE mail_user_groupe ADD id SERIAL PRIMARY KEY');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service_user_content DROP COLUMN id');
        $this->addSql('ALTER TABLE actes_type_pj DROP COLUMN id');
        $this->addSql('ALTER TABLE mail_user_groupe DROP COLUMN id');
    }
}
