<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170321214701 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) :void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica ADD risposta_operatore_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD esito BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD motivazione_esito TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD CONSTRAINT FK_448253AC1ED5842A FOREIGN KEY (risposta_operatore_id) REFERENCES allegato (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_448253AC1ED5842A ON pratica (risposta_operatore_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) :void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica DROP CONSTRAINT FK_448253AC1ED5842A');
        $this->addSql('DROP INDEX IDX_448253AC1ED5842A');
        $this->addSql('ALTER TABLE pratica DROP risposta_operatore_id');
        $this->addSql('ALTER TABLE pratica DROP esito');
        $this->addSql('ALTER TABLE pratica DROP motivazione_esito');
    }
}
