<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201021175632 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE sessioni_utente (id UUID NOT NULL, user_id UUID NOT NULL, session_data JSON NOT NULL, authentication_data JSON DEFAULT NULL, ip VARCHAR(255) NOT NULL, environment VARCHAR(255) NOT NULL, suspicious_activity BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE pratica ADD session_data_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD authentication_data JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD CONSTRAINT FK_448253AC252D2366 FOREIGN KEY (session_data_id) REFERENCES sessioni_utente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_448253AC252D2366 ON pratica (session_data_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE pratica DROP CONSTRAINT FK_448253AC252D2366');
        $this->addSql('DROP TABLE sessioni_utente');
        $this->addSql('DROP INDEX IDX_448253AC252D2366');
        $this->addSql('ALTER TABLE pratica DROP session_data_id');
        $this->addSql('ALTER TABLE pratica DROP authentication_data');
    }
}
