<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200123220220 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE servizio ADD integrations JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ALTER coverage TYPE TEXT');
        $this->addSql('ALTER TABLE servizio ALTER coverage DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN servizio.integrations IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN servizio.coverage IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN servizio.flow_steps IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN servizio.protocollo_parameters IS \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE ente ADD backoffice_integration_enabled BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE ente ALTER gateways TYPE JSON USING gateways::json');
        $this->addSql('ALTER TABLE ente ALTER gateways DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN ente.gateways IS \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE failure_login_attempt ALTER data TYPE TEXT');
        $this->addSql('ALTER TABLE failure_login_attempt ALTER data DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN failure_login_attempt.data IS \'(DC2Type:array)\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE servizio DROP integrations');
        $this->addSql('ALTER TABLE servizio ALTER coverage TYPE TEXT');
        $this->addSql('ALTER TABLE servizio ALTER coverage DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN servizio.coverage IS NULL');
        $this->addSql('COMMENT ON COLUMN servizio.flow_steps IS NULL');
        $this->addSql('COMMENT ON COLUMN servizio.protocollo_parameters IS NULL');
        $this->addSql('ALTER TABLE ente DROP backoffice_integration_enabled');
        $this->addSql('ALTER TABLE ente ALTER gateways TYPE TEXT');
        $this->addSql('ALTER TABLE ente ALTER gateways DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN ente.gateways IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE failure_login_attempt ALTER data TYPE TEXT');
        $this->addSql('ALTER TABLE failure_login_attempt ALTER data DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN failure_login_attempt.data IS NULL');
    }
}
