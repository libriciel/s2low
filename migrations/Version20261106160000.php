<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261106160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du contact affiché aux utilisateurs d\'un groupe : téléphone, e-mail, horaires et message, chacun avec son interrupteur d\'affichage.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE authority_groups
                    ADD contact_phone VARCHAR(13) DEFAULT '' NOT NULL,
                    ADD contact_phone_shown BOOLEAN DEFAULT TRUE NOT NULL,
                    ADD contact_email VARCHAR(255) DEFAULT '' NOT NULL,
                    ADD contact_email_shown BOOLEAN DEFAULT TRUE NOT NULL,
                    ADD contact_hours VARCHAR(80) DEFAULT '' NOT NULL,
                    ADD contact_hours_shown BOOLEAN DEFAULT TRUE NOT NULL,
                    ADD contact_message VARCHAR(1000) DEFAULT '' NOT NULL,
                    ADD contact_message_shown BOOLEAN DEFAULT TRUE NOT NULL,
                    ADD CONSTRAINT authority_groups_contact_phone_check CHECK (contact_phone ~ '^((0|[+]33|0033)[1-9][0-9]{8})?$')"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE authority_groups
                    DROP CONSTRAINT authority_groups_contact_phone_check,
                    DROP COLUMN contact_phone,
                    DROP COLUMN contact_phone_shown,
                    DROP COLUMN contact_email,
                    DROP COLUMN contact_email_shown,
                    DROP COLUMN contact_hours,
                    DROP COLUMN contact_hours_shown,
                    DROP COLUMN contact_message,
                    DROP COLUMN contact_message_shown'
        );
    }
}
