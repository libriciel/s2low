<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616092702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Suppression des timestamps et message_horodate des logs et logs historiques.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE logs DROP COLUMN IF EXISTS timestamp;');
        $this->addSql('ALTER TABLE logs DROP COLUMN IF EXISTS message_horodate;');

        $this->addSql('ALTER TABLE logs_historique DROP COLUMN IF EXISTS timestamp;');
        $this->addSql('ALTER TABLE logs_historique DROP COLUMN IF EXISTS message_horodate;');
    }

    public function down(Schema $schema): void
    {
    }
}
