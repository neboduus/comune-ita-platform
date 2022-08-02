<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200807141539 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE service_group (id UUID NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, register_in_folder BOOLEAN DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C4B2A9225E237E06 ON service_group (name)');
        $this->addSql('ALTER TABLE pratica ADD service_group_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD folder_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD CONSTRAINT FK_448253AC722827A FOREIGN KEY (service_group_id) REFERENCES service_group (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_448253AC722827A ON pratica (service_group_id)');
        $this->addSql('ALTER TABLE servizio ADD service_group_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD CONSTRAINT FK_D8716AD5722827A FOREIGN KEY (service_group_id) REFERENCES service_group (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_D8716AD5722827A ON servizio (service_group_id)');
    }

    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica DROP CONSTRAINT FK_448253AC722827A');
        $this->addSql('ALTER TABLE servizio DROP CONSTRAINT FK_D8716AD5722827A');
        $this->addSql('DROP TABLE service_group');
        $this->addSql('DROP INDEX IDX_D8716AD5722827A');
        $this->addSql('ALTER TABLE servizio DROP service_group_id');
        $this->addSql('DROP INDEX IDX_448253AC722827A');
        $this->addSql('ALTER TABLE pratica DROP service_group_id');
        $this->addSql('ALTER TABLE pratica DROP folder_id');
    }
}
