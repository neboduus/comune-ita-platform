<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220826160810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('COMMENT ON COLUMN allegato.payload IS NULL');
        $this->addSql('ALTER TABLE calendar ALTER external_calendars TYPE JSON');
        $this->addSql('ALTER TABLE calendar ALTER external_calendars DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN calendar.closing_periods IS NULL');
        $this->addSql('COMMENT ON COLUMN calendar.external_calendars IS NULL');
        $this->addSql('COMMENT ON COLUMN ente.gateways IS NULL');
        $this->addSql('COMMENT ON COLUMN pratica.related_cfs IS NULL');
        $this->addSql('COMMENT ON COLUMN pratica.dematerialized_forms IS NULL');
        $this->addSql('COMMENT ON COLUMN pratica.payment_data IS NULL');
        $this->addSql('COMMENT ON COLUMN pratica.delega_data IS NULL');
        $this->addSql('COMMENT ON COLUMN servizio.payment_parameters IS NULL');
        $this->addSql('COMMENT ON COLUMN servizio.additional_data IS NULL');
        $this->addSql('COMMENT ON COLUMN servizio.flow_steps IS NULL');
        $this->addSql('COMMENT ON COLUMN servizio.protocollo_parameters IS NULL');
        $this->addSql('COMMENT ON COLUMN servizio.integrations IS NULL');
        $this->addSql('COMMENT ON COLUMN servizio.io_service_parameters IS NULL');
        $this->addSql('ALTER TABLE servizio_recipient DROP CONSTRAINT FK_AD3BC94F5513F0B4');
        $this->addSql('ALTER TABLE servizio_recipient DROP CONSTRAINT FK_AD3BC94FE92F8F78');
        $this->addSql('ALTER TABLE servizio_recipient ADD CONSTRAINT FK_AD3BC94F5513F0B4 FOREIGN KEY (servizio_id) REFERENCES servizio (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE servizio_recipient ADD CONSTRAINT FK_AD3BC94FE92F8F78 FOREIGN KEY (recipient_id) REFERENCES recipient (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('COMMENT ON COLUMN subscription_service.payments IS NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('COMMENT ON COLUMN ente.gateways IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN servizio.additional_data IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN servizio.flow_steps IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN servizio.protocollo_parameters IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN servizio.payment_parameters IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN servizio.integrations IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN servizio.io_service_parameters IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN allegato.payload IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN pratica.delega_data IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN pratica.related_cfs IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN pratica.payment_data IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN pratica.dematerialized_forms IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN subscription_service.payments IS \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE calendar ALTER external_calendars TYPE TEXT');
        $this->addSql('ALTER TABLE calendar ALTER external_calendars DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN calendar.external_calendars IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN calendar.closing_periods IS \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE servizio_recipient DROP CONSTRAINT fk_ad3bc94f5513f0b4');
        $this->addSql('ALTER TABLE servizio_recipient DROP CONSTRAINT fk_ad3bc94fe92f8f78');
        $this->addSql('ALTER TABLE servizio_recipient ADD CONSTRAINT fk_ad3bc94f5513f0b4 FOREIGN KEY (servizio_id) REFERENCES servizio (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE servizio_recipient ADD CONSTRAINT fk_ad3bc94fe92f8f78 FOREIGN KEY (recipient_id) REFERENCES recipient (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
