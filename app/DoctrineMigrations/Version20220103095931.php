<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220103095931 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE geographic_area (id UUID NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, geofence JSONB DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C4C010BD989D9B62 ON geographic_area (slug)');
        $this->addSql('CREATE TABLE service_group_geographic_area (service_group_id UUID NOT NULL, geographic_area_id UUID NOT NULL, PRIMARY KEY(service_group_id, geographic_area_id))');
        $this->addSql('CREATE INDEX IDX_8D21C1D4722827A ON service_group_geographic_area (service_group_id)');
        $this->addSql('CREATE INDEX IDX_8D21C1D47A617856 ON service_group_geographic_area (geographic_area_id)');
        $this->addSql('CREATE TABLE servizio_geographic_area (servizio_id UUID NOT NULL, geographic_area_id UUID NOT NULL, PRIMARY KEY(servizio_id, geographic_area_id))');
        $this->addSql('CREATE INDEX IDX_F654A41A5513F0B4 ON servizio_geographic_area (servizio_id)');
        $this->addSql('CREATE INDEX IDX_F654A41A7A617856 ON servizio_geographic_area (geographic_area_id)');
        $this->addSql('ALTER TABLE service_group_geographic_area ADD CONSTRAINT FK_8D21C1D4722827A FOREIGN KEY (service_group_id) REFERENCES service_group (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_group_geographic_area ADD CONSTRAINT FK_8D21C1D47A617856 FOREIGN KEY (geographic_area_id) REFERENCES geographic_area (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE servizio_geographic_area ADD CONSTRAINT FK_F654A41A5513F0B4 FOREIGN KEY (servizio_id) REFERENCES servizio (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE servizio_geographic_area ADD CONSTRAINT FK_F654A41A7A617856 FOREIGN KEY (geographic_area_id) REFERENCES geographic_area (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service_group_geographic_area DROP CONSTRAINT FK_8D21C1D47A617856');
        $this->addSql('ALTER TABLE servizio_geographic_area DROP CONSTRAINT FK_F654A41A7A617856');
        $this->addSql('DROP TABLE geographic_area');
        $this->addSql('DROP TABLE service_group_geographic_area');
        $this->addSql('DROP TABLE servizio_geographic_area');
    }
}
