<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161114172533 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE utente ADD cps_telefono VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD cps_cellulare VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD cps_email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD cps_email_personale VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD cps_titolo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD cps_indirizzo_domicilio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD cps_cap_domicilio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD cps_citta_domicilio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD cps_provincia_domicilio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD cps_stato_domicilio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD cps_indirizzo_residenza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD cps_cap_residenza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD cps_citta_residenza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD cps_provincia_residenza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD cps_stato_residenza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD sdc_indirizzo_domicilio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD sdc_cap_domicilio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD sdc_citta_domicilio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD sdc_provincia_domicilio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD sdc_stato_domicilio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD sdc_indirizzo_residenza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD sdc_cap_residenza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD sdc_citta_residenza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD sdc_provincia_residenza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD sdc_stato_residenza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente DROP cap_domicilio');
        $this->addSql('ALTER TABLE utente DROP cap_residenza');
        $this->addSql('ALTER TABLE utente DROP cellulare');
        $this->addSql('ALTER TABLE utente DROP citta_domicilio');
        $this->addSql('ALTER TABLE utente DROP citta_residenza');
        $this->addSql('ALTER TABLE utente DROP email_alt');
        $this->addSql('ALTER TABLE utente DROP indirizzo_domicilio');
        $this->addSql('ALTER TABLE utente DROP indirizzo_residenza');
        $this->addSql('ALTER TABLE utente DROP provincia_domicilio');
        $this->addSql('ALTER TABLE utente DROP provincia_residenza');
        $this->addSql('ALTER TABLE utente DROP stato_domicilio');
        $this->addSql('ALTER TABLE utente DROP stato_residenza');
        $this->addSql('ALTER TABLE utente DROP telefono');
        $this->addSql('ALTER TABLE utente DROP titolo');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE utente ADD cap_domicilio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD cap_residenza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD cellulare VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD citta_domicilio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD citta_residenza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD email_alt VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD indirizzo_domicilio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD indirizzo_residenza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD provincia_domicilio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD provincia_residenza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD stato_domicilio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD stato_residenza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD telefono VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD titolo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utente DROP cps_telefono');
        $this->addSql('ALTER TABLE utente DROP cps_cellulare');
        $this->addSql('ALTER TABLE utente DROP cps_email');
        $this->addSql('ALTER TABLE utente DROP cps_email_personale');
        $this->addSql('ALTER TABLE utente DROP cps_titolo');
        $this->addSql('ALTER TABLE utente DROP cps_indirizzo_domicilio');
        $this->addSql('ALTER TABLE utente DROP cps_cap_domicilio');
        $this->addSql('ALTER TABLE utente DROP cps_citta_domicilio');
        $this->addSql('ALTER TABLE utente DROP cps_provincia_domicilio');
        $this->addSql('ALTER TABLE utente DROP cps_stato_domicilio');
        $this->addSql('ALTER TABLE utente DROP cps_indirizzo_residenza');
        $this->addSql('ALTER TABLE utente DROP cps_cap_residenza');
        $this->addSql('ALTER TABLE utente DROP cps_citta_residenza');
        $this->addSql('ALTER TABLE utente DROP cps_provincia_residenza');
        $this->addSql('ALTER TABLE utente DROP cps_stato_residenza');
        $this->addSql('ALTER TABLE utente DROP sdc_indirizzo_domicilio');
        $this->addSql('ALTER TABLE utente DROP sdc_cap_domicilio');
        $this->addSql('ALTER TABLE utente DROP sdc_citta_domicilio');
        $this->addSql('ALTER TABLE utente DROP sdc_provincia_domicilio');
        $this->addSql('ALTER TABLE utente DROP sdc_stato_domicilio');
        $this->addSql('ALTER TABLE utente DROP sdc_indirizzo_residenza');
        $this->addSql('ALTER TABLE utente DROP sdc_cap_residenza');
        $this->addSql('ALTER TABLE utente DROP sdc_citta_residenza');
        $this->addSql('ALTER TABLE utente DROP sdc_provincia_residenza');
        $this->addSql('ALTER TABLE utente DROP sdc_stato_residenza');
    }
}
