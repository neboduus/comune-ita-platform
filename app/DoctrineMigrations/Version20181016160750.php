<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181016160750 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('COMMENT ON COLUMN allegato.payload IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN pratica.related_cfs IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN pratica.dematerialized_forms IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN pratica.payment_data IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN pratica.delega_data IS \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE utente DROP locked');
        $this->addSql('ALTER TABLE utente DROP expired');
        $this->addSql('ALTER TABLE utente DROP expires_at');
        $this->addSql('ALTER TABLE utente DROP credentials_expired');
        $this->addSql('ALTER TABLE utente DROP credentials_expire_at');
        $this->addSql('ALTER TABLE utente ALTER username TYPE VARCHAR(180)');
        $this->addSql('ALTER TABLE utente ALTER username_canonical TYPE VARCHAR(180)');
        $this->addSql('ALTER TABLE utente ALTER salt DROP NOT NULL');
        $this->addSql('ALTER TABLE utente ALTER confirmation_token TYPE VARCHAR(180)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DE45B3E0C05FB297 ON utente (confirmation_token)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('COMMENT ON COLUMN allegato.payload IS NULL');
        $this->addSql('COMMENT ON COLUMN pratica.delega_data IS NULL');
        $this->addSql('COMMENT ON COLUMN pratica.related_cfs IS NULL');
        $this->addSql('COMMENT ON COLUMN pratica.payment_data IS NULL');
        $this->addSql('COMMENT ON COLUMN pratica.dematerialized_forms IS NULL');
        $this->addSql('DROP INDEX UNIQ_DE45B3E0C05FB297');
        $this->addSql('ALTER TABLE utente ADD locked BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE utente ADD expired BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE utente ADD expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD credentials_expired BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE utente ADD credentials_expire_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ALTER username TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER username_canonical TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER salt SET NOT NULL');
        $this->addSql('ALTER TABLE utente ALTER confirmation_token TYPE VARCHAR(255)');
    }
}
