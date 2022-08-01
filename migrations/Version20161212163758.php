<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161212163758 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE utente ADD accepted_terms TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE utente DROP terms_accepted');
        $this->addSql('ALTER TABLE termini_utilizzo ADD mandatory BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE termini_utilizzo ADD latest_revision INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE termini_utilizzo DROP mandatory');
        $this->addSql('ALTER TABLE termini_utilizzo DROP latest_revision');
        $this->addSql('ALTER TABLE utente ADD terms_accepted BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE utente DROP accepted_terms');
    }
}
