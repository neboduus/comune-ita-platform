<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171010162915 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE payment_gateway (id UUID NOT NULL, name VARCHAR(100) NOT NULL, identifier VARCHAR(100) NOT NULL, description TEXT DEFAULT NULL, disclaimer TEXT DEFAULT NULL, fcqn VARCHAR(100) DEFAULT NULL, enabled BOOLEAN DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DB7D395772E836A ON payment_gateway (identifier)');
        $this->addSql('ALTER TABLE pratica ADD payment_type UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD payment_data JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD CONSTRAINT FK_448253ACAD5DC05D FOREIGN KEY (payment_type) REFERENCES payment_gateway (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_448253ACAD5DC05D ON pratica (payment_type)');
        $this->addSql('ALTER TABLE servizio ADD payment_required BOOLEAN DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica DROP CONSTRAINT FK_448253ACAD5DC05D');
        $this->addSql('DROP TABLE payment_gateway');
        $this->addSql('ALTER TABLE servizio DROP payment_required');
        $this->addSql('DROP INDEX IDX_448253ACAD5DC05D');
        $this->addSql('ALTER TABLE pratica DROP payment_type');
        $this->addSql('ALTER TABLE pratica DROP payment_data');
    }
}
