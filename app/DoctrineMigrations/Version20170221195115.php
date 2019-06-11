<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170221195115 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE categoria (id UUID NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, parent_id UUID DEFAULT NULL, tree_id INT NOT NULL, tree_parent_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4E10122D989D9B62 ON categoria (slug)');
        $this->addSql('ALTER TABLE servizio ALTER area DROP DEFAULT');
        $this->addSql('ALTER TABLE servizio ALTER area TYPE UUID USING (area::uuid)');
        $this->addSql('ALTER TABLE servizio ADD CONSTRAINT FK_D8716AD5D7943D68 FOREIGN KEY (area) REFERENCES categoria (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_D8716AD5D7943D68 ON servizio (area)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE servizio DROP CONSTRAINT FK_D8716AD5D7943D68');
        $this->addSql('DROP TABLE categoria');
        $this->addSql('DROP INDEX IDX_D8716AD5D7943D68');
        $this->addSql('ALTER TABLE servizio ALTER area TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE servizio ALTER area DROP DEFAULT');
    }
}
