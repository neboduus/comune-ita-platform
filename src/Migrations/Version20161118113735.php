<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161118113735 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) :void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE scheduled_action (id UUID NOT NULL, service VARCHAR(255) NOT NULL, type VARCHAR(100) NOT NULL, params TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE ente ADD protocollo_parameters TEXT DEFAULT \'a:0:{}\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) :void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE scheduled_action');
        $this->addSql('ALTER TABLE ente DROP protocollo_parameters');
    }
}
