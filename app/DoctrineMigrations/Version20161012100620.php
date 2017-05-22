<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161012100620 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica ADD intestatario_codice_utente VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD intestatario_nome VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD intestatario_cognome VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD intestatario_indirizzo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD intestatario_cap INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD intestatario_citta VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD intestatario_telefono VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD intestatario_email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD contatore_numero VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD contatore_uso VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD contatore_unita_immobiliari VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD lettura_metri_cubi VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD lettura_data DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD note TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ALTER storico_stati TYPE TEXT');
        $this->addSql('ALTER TABLE pratica ALTER storico_stati DROP DEFAULT');
        $this->addSql('ALTER TABLE servizio ALTER pratica_fcqn DROP DEFAULT');
        $this->addSql('ALTER TABLE servizio ALTER pratica_flow_service_name DROP DEFAULT');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE pratica DROP intestatario_codice_utente');
        $this->addSql('ALTER TABLE pratica DROP intestatario_nome');
        $this->addSql('ALTER TABLE pratica DROP intestatario_cognome');
        $this->addSql('ALTER TABLE pratica DROP intestatario_indirizzo');
        $this->addSql('ALTER TABLE pratica DROP intestatario_cap');
        $this->addSql('ALTER TABLE pratica DROP intestatario_citta');
        $this->addSql('ALTER TABLE pratica DROP intestatario_telefono');
        $this->addSql('ALTER TABLE pratica DROP intestatario_email');
        $this->addSql('ALTER TABLE pratica DROP contatore_numero');
        $this->addSql('ALTER TABLE pratica DROP contatore_uso');
        $this->addSql('ALTER TABLE pratica DROP contatore_unita_immobiliari');
        $this->addSql('ALTER TABLE pratica DROP lettura_metri_cubi');
        $this->addSql('ALTER TABLE pratica DROP lettura_data');
        $this->addSql('ALTER TABLE pratica DROP note');
        $this->addSql('ALTER TABLE pratica ALTER storico_stati TYPE TEXT');
        $this->addSql('ALTER TABLE pratica ALTER storico_stati DROP DEFAULT');
        $this->addSql('ALTER TABLE servizio ALTER pratica_fcqn SET DEFAULT \' \'');
        $this->addSql('ALTER TABLE servizio ALTER pratica_flow_service_name SET DEFAULT \' \'');
    }
}
