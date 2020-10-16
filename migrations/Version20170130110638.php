<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170130110638 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE utente ALTER luogo_nascita TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER luogo_nascita DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER provincia_nascita TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER provincia_nascita DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER stato_nascita TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER stato_nascita DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER x509certificate_issuerdn TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER x509certificate_issuerdn DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER x509certificate_subjectdn TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER x509certificate_subjectdn DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER x509certificate_base64 TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER x509certificate_base64 DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_telefono TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER cps_telefono DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_cellulare TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER cps_cellulare DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_email TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER cps_email DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_email_personale TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER cps_email_personale DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_titolo TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER cps_titolo DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_indirizzo_domicilio TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER cps_indirizzo_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_cap_domicilio TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER cps_cap_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_citta_domicilio TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER cps_citta_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_provincia_domicilio TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER cps_provincia_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_stato_domicilio TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER cps_stato_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_indirizzo_residenza TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER cps_indirizzo_residenza DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_cap_residenza TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER cps_cap_residenza DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_citta_residenza TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER cps_citta_residenza DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_provincia_residenza TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER cps_provincia_residenza DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_stato_residenza TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER cps_stato_residenza DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_indirizzo_domicilio TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER sdc_indirizzo_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_cap_domicilio TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER sdc_cap_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_citta_domicilio TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER sdc_citta_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_provincia_domicilio TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER sdc_provincia_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_stato_domicilio TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER sdc_stato_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_indirizzo_residenza TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER sdc_indirizzo_residenza DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_cap_residenza TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER sdc_cap_residenza DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_citta_residenza TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER sdc_citta_residenza DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_provincia_residenza TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER sdc_provincia_residenza DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_stato_residenza TYPE TEXT');
        $this->addSql('ALTER TABLE utente ALTER sdc_stato_residenza DROP DEFAULT');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE utente ALTER luogo_nascita TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER luogo_nascita DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER provincia_nascita TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER provincia_nascita DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER stato_nascita TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER stato_nascita DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER x509certificate_issuerdn TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER x509certificate_issuerdn DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER x509certificate_subjectdn TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER x509certificate_subjectdn DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER x509certificate_base64 TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER x509certificate_base64 DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_telefono TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER cps_telefono DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_cellulare TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER cps_cellulare DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_email TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER cps_email DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_email_personale TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER cps_email_personale DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_titolo TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER cps_titolo DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_indirizzo_domicilio TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER cps_indirizzo_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_cap_domicilio TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER cps_cap_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_citta_domicilio TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER cps_citta_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_provincia_domicilio TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER cps_provincia_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_stato_domicilio TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER cps_stato_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_indirizzo_residenza TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER cps_indirizzo_residenza DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_cap_residenza TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER cps_cap_residenza DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_citta_residenza TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER cps_citta_residenza DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_provincia_residenza TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER cps_provincia_residenza DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER cps_stato_residenza TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER cps_stato_residenza DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_indirizzo_domicilio TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER sdc_indirizzo_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_cap_domicilio TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER sdc_cap_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_citta_domicilio TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER sdc_citta_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_provincia_domicilio TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER sdc_provincia_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_stato_domicilio TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER sdc_stato_domicilio DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_indirizzo_residenza TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER sdc_indirizzo_residenza DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_cap_residenza TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER sdc_cap_residenza DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_citta_residenza TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER sdc_citta_residenza DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_provincia_residenza TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER sdc_provincia_residenza DROP DEFAULT');
        $this->addSql('ALTER TABLE utente ALTER sdc_stato_residenza TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE utente ALTER sdc_stato_residenza DROP DEFAULT');
    }
}
