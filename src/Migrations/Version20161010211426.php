<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161010211426 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) :void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica ALTER storico_stati TYPE TEXT');
        $this->addSql('ALTER TABLE pratica ALTER storico_stati DROP DEFAULT');
        $this->addSql('ALTER TABLE servizio ADD pratica_fcqn VARCHAR(255) NOT NULL DEFAULT \' \' ');
        $this->addSql('ALTER TABLE servizio ADD pratica_flow_service_name VARCHAR(255) NOT NULL DEFAULT \' \' ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) :void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica ALTER storico_stati TYPE TEXT');
        $this->addSql('ALTER TABLE pratica ALTER storico_stati DROP DEFAULT');
        $this->addSql('ALTER TABLE servizio DROP pratica_fcqn');
        $this->addSql('ALTER TABLE servizio DROP pratica_flow_service_name');
    }
}
