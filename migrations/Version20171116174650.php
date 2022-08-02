<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171116174650 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica ADD delega_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD delega_data JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD tipologia_certificato_anagrafico VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD uso_certificato_anagrafico VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD stato_estero_certificato_anagrafico VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD richiedente_codice_fiscale VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica DROP delega_type');
        $this->addSql('ALTER TABLE pratica DROP delega_data');
        $this->addSql('ALTER TABLE pratica DROP tipologia_certificato_anagrafico');
        $this->addSql('ALTER TABLE pratica DROP uso_certificato_anagrafico');
        $this->addSql('ALTER TABLE pratica DROP stato_estero_certificato_anagrafico');
        $this->addSql('ALTER TABLE pratica DROP richiedente_codice_fiscale');
    }
}
