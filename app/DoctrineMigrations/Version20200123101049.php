<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200123101049 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE subscription_service (id UUID NOT NULL, code VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, subscription_begin TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, subscription_end TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, subscription_amount NUMERIC(10, 0) DEFAULT \'0\' NOT NULL, begin_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, subscribers_limit INT DEFAULT NULL, subscription_message TEXT DEFAULT NULL, begin_message TEXT DEFAULT NULL, end_message TEXT DEFAULT NULL, status INT NOT NULL, payments JSON DEFAULT NULL, tags TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_92887A4977153098 ON subscription_service (code)');
        $this->addSql('COMMENT ON COLUMN subscription_service.payments IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN subscription_service.tags IS \'(DC2Type:array)\'');
        $this->addSql('DROP INDEX subscription_unique');
        $this->addSql('ALTER TABLE subscription ADD subscription_service_id UUID NOT NULL');
        $this->addSql('ALTER TABLE subscription DROP code');
        $this->addSql('ALTER TABLE subscription DROP start_date');
        $this->addSql('ALTER TABLE subscription DROP end_date');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D32FB9E983 FOREIGN KEY (subscription_service_id) REFERENCES subscription_service (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_A3C664D32FB9E983 ON subscription (subscription_service_id)');
        $this->addSql('CREATE UNIQUE INDEX subscription_unique ON subscription (subscriber_id, subscription_service_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE subscription DROP CONSTRAINT FK_A3C664D32FB9E983');
        $this->addSql('DROP TABLE subscription_service');
        $this->addSql('DROP INDEX IDX_A3C664D32FB9E983');
        $this->addSql('DROP INDEX subscription_unique');
        $this->addSql('ALTER TABLE subscription ADD code VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE subscription ADD start_date DATE NOT NULL');
        $this->addSql('ALTER TABLE subscription ADD end_date DATE NOT NULL');
        $this->addSql('ALTER TABLE subscription DROP subscription_service_id');
        $this->addSql('CREATE UNIQUE INDEX subscription_unique ON subscription (subscriber_id, code)');
    }
}
