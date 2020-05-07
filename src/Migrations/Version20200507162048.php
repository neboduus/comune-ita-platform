<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200507162048 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE reset_password_request_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE reset_password_request (id INT NOT NULL, user_id UUID DEFAULT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7CE748AA76ED395 ON reset_password_request (user_id)');
        $this->addSql('COMMENT ON COLUMN reset_password_request.requested_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN reset_password_request.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES utente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP INDEX uniq_de45b3e0c05fb297');
        $this->addSql('ALTER TABLE utente ALTER roles TYPE JSON USING roles::json');
        $this->addSql('ALTER TABLE utente ALTER roles DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN utente.roles IS NULL');
        $this->addSql('ALTER TABLE subscription ALTER created_at SET DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE subscription_service ALTER created_at SET DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE calendar ALTER external_calendars TYPE JSON');
        $this->addSql('ALTER TABLE calendar ALTER external_calendars DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN calendar.external_calendars IS \'(DC2Type:json_array)\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE reset_password_request_id_seq CASCADE');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('ALTER TABLE utente ALTER roles TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER roles DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN utente.roles IS \'(DC2Type:array)\'');
        $this->addSql('CREATE UNIQUE INDEX uniq_de45b3e0c05fb297 ON utente (confirmation_token)');
        $this->addSql('ALTER TABLE subscription_service ALTER created_at SET DEFAULT \'now()\'');
        $this->addSql('ALTER TABLE subscription ALTER created_at SET DEFAULT \'now()\'');
        $this->addSql('ALTER TABLE calendar ALTER external_calendars TYPE TEXT');
        $this->addSql('ALTER TABLE calendar ALTER external_calendars DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN calendar.external_calendars IS \'(DC2Type:array)\'');
    }
}
