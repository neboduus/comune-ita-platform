<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161118151412 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX uniq_de45b3e0a0d96fbf');
        $this->addSql('ALTER TABLE utente ALTER email DROP NOT NULL');
        $this->addSql('ALTER TABLE utente ALTER email_canonical DROP NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE utente ALTER email SET NOT NULL');
        $this->addSql('ALTER TABLE utente ALTER email_canonical SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_de45b3e0a0d96fbf ON utente (email_canonical)');
    }
}
