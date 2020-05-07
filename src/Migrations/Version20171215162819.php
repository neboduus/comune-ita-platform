<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171215162819 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) :void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ente ADD contatti TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ente ADD email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ente ADD email_certificata VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) :void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ente DROP contatti');
        $this->addSql('ALTER TABLE ente DROP email');
        $this->addSql('ALTER TABLE ente DROP email_certificata');
    }
}
