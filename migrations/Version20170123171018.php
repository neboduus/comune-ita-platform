<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170123171018 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica ADD ruolo_utente_org_richiedente VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD ragione_sociale_org_richiedente VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD comune_org_richiedente VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD indirizzo_org_richiedente VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD cap_org_richiedente VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD provincia_org_richiedente VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD civico_org_richiedente VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD indirizzo_occupazione VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD civico_occupazione VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD lunghezza_occupazione VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD larghezza_occupazione VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD metri_quadri_occupazione VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD motivazione_occupazione TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD tipologia_occupazione VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD inizio_occupazione_giorno DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD inizio_occupazione_ora VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD fine_occupazione_giorno DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD fine_occupazione_ora VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD limitazione_traffico BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE ente ALTER protocollo_parameters DROP DEFAULT');
        $this->addSql('ALTER TABLE ente ALTER protocollo_parameters SET NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ente ALTER protocollo_parameters SET DEFAULT \'a:0:{}\'');
        $this->addSql('ALTER TABLE ente ALTER protocollo_parameters DROP NOT NULL');
        $this->addSql('ALTER TABLE pratica DROP ruolo_utente_org_richiedente');
        $this->addSql('ALTER TABLE pratica DROP ragione_sociale_org_richiedente');
        $this->addSql('ALTER TABLE pratica DROP comune_org_richiedente');
        $this->addSql('ALTER TABLE pratica DROP indirizzo_org_richiedente');
        $this->addSql('ALTER TABLE pratica DROP cap_org_richiedente');
        $this->addSql('ALTER TABLE pratica DROP provincia_org_richiedente');
        $this->addSql('ALTER TABLE pratica DROP civico_org_richiedente');
        $this->addSql('ALTER TABLE pratica DROP indirizzo_occupazione');
        $this->addSql('ALTER TABLE pratica DROP civico_occupazione');
        $this->addSql('ALTER TABLE pratica DROP lunghezza_occupazione');
        $this->addSql('ALTER TABLE pratica DROP larghezza_occupazione');
        $this->addSql('ALTER TABLE pratica DROP metri_quadri_occupazione');
        $this->addSql('ALTER TABLE pratica DROP motivazione_occupazione');
        $this->addSql('ALTER TABLE pratica DROP tipologia_occupazione');
        $this->addSql('ALTER TABLE pratica DROP inizio_occupazione_giorno');
        $this->addSql('ALTER TABLE pratica DROP inizio_occupazione_ora');
        $this->addSql('ALTER TABLE pratica DROP fine_occupazione_giorno');
        $this->addSql('ALTER TABLE pratica DROP fine_occupazione_ora');
        $this->addSql('ALTER TABLE pratica DROP limitazione_traffico');
    }
}
