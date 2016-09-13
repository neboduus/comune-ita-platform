<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160913114121 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE pratica (id UUID NOT NULL, user_id UUID NOT NULL, servizio_id UUID NOT NULL, ente_id UUID DEFAULT NULL, operatore_id UUID DEFAULT NULL, asilo_id UUID DEFAULT NULL, creation_time INT NOT NULL, status INT NOT NULL, numero_fascicolo VARCHAR(255) DEFAULT NULL, numero_protocollo VARCHAR(255) DEFAULT NULL, numeri_protocollo TEXT DEFAULT NULL, data TEXT DEFAULT NULL, commenti TEXT DEFAULT NULL, latest_status_change_timestamp INT DEFAULT NULL, latest_cpscommunication_timestamp INT DEFAULT NULL, latest_operatore_communication_timestamp INT DEFAULT NULL, type VARCHAR(255) NOT NULL, accetto_istruzioni BOOLEAN DEFAULT NULL, accetto_utilizzo BOOLEAN DEFAULT NULL, struttura_orario VARCHAR(255) DEFAULT NULL, periodo_iscrizione_da DATE DEFAULT NULL, periodo_iscrizione_a DATE DEFAULT NULL, richiedente_nome VARCHAR(255) DEFAULT NULL, richiedente_cognome VARCHAR(255) DEFAULT NULL, richiedente_luogo_nascita VARCHAR(255) DEFAULT NULL, richiedente_data_nascita TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, richiedente_indirizzo_residenza VARCHAR(255) DEFAULT NULL, richiedente_cap_residenza INT DEFAULT NULL, richiedente_citta_residenza VARCHAR(255) DEFAULT NULL, richiedente_telefono VARCHAR(255) DEFAULT NULL, richiedente_email VARCHAR(255) DEFAULT NULL, bambino_nome VARCHAR(255) DEFAULT NULL, bambino_cognome VARCHAR(255) DEFAULT NULL, bambino_luogo_nascita VARCHAR(255) DEFAULT NULL, bambino_data_nascita DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_448253ACA76ED395 ON pratica (user_id)');
        $this->addSql('CREATE INDEX IDX_448253AC5513F0B4 ON pratica (servizio_id)');
        $this->addSql('CREATE INDEX IDX_448253ACEFB68F0A ON pratica (ente_id)');
        $this->addSql('CREATE INDEX IDX_448253ACDD8402AC ON pratica (operatore_id)');
        $this->addSql('CREATE INDEX IDX_448253AC2D9F8A53 ON pratica (asilo_id)');
        $this->addSql('COMMENT ON COLUMN pratica.numeri_protocollo IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE pratica_allegato (pratica_id UUID NOT NULL, allegato_id UUID NOT NULL, PRIMARY KEY(pratica_id, allegato_id))');
        $this->addSql('CREATE INDEX IDX_1E92B34B24038DEB ON pratica_allegato (pratica_id)');
        $this->addSql('CREATE INDEX IDX_1E92B34B68F4D369 ON pratica_allegato (allegato_id)');
        $this->addSql('CREATE TABLE utente (id UUID NOT NULL, ente_id UUID DEFAULT NULL, username VARCHAR(255) NOT NULL, username_canonical VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, email_canonical VARCHAR(255) NOT NULL, enabled BOOLEAN NOT NULL, salt VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, last_login TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, locked BOOLEAN NOT NULL, expired BOOLEAN NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, confirmation_token VARCHAR(255) DEFAULT NULL, password_requested_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, roles TEXT NOT NULL, credentials_expired BOOLEAN NOT NULL, credentials_expire_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, cognome VARCHAR(255) NOT NULL, nome VARCHAR(255) NOT NULL, email_contatto VARCHAR(255) DEFAULT NULL, cellulare_contatto VARCHAR(255) DEFAULT NULL, type VARCHAR(255) NOT NULL, terms_accepted BOOLEAN DEFAULT NULL, codice_fiscale VARCHAR(255) DEFAULT NULL, cap_domicilio VARCHAR(255) DEFAULT NULL, cap_residenza VARCHAR(255) DEFAULT NULL, cellulare VARCHAR(255) DEFAULT NULL, citta_domicilio VARCHAR(255) DEFAULT NULL, citta_residenza VARCHAR(255) DEFAULT NULL, data_nascita TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, email_alt VARCHAR(255) DEFAULT NULL, indirizzo_domicilio VARCHAR(255) DEFAULT NULL, indirizzo_residenza VARCHAR(255) DEFAULT NULL, luogo_nascita VARCHAR(255) DEFAULT NULL, provincia_domicilio VARCHAR(255) DEFAULT NULL, provincia_nascita VARCHAR(255) DEFAULT NULL, provincia_residenza VARCHAR(255) DEFAULT NULL, sesso VARCHAR(255) DEFAULT NULL, stato_domicilio VARCHAR(255) DEFAULT NULL, stato_nascita VARCHAR(255) DEFAULT NULL, stato_residenza VARCHAR(255) DEFAULT NULL, telefono VARCHAR(255) DEFAULT NULL, titolo VARCHAR(255) DEFAULT NULL, x509certificate_issuerdn VARCHAR(255) DEFAULT NULL, x509certificate_subjectdn VARCHAR(255) DEFAULT NULL, x509certificate_base64 VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DE45B3E092FC23A8 ON utente (username_canonical)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DE45B3E0A0D96FBF ON utente (email_canonical)');
        $this->addSql('CREATE INDEX IDX_DE45B3E0EFB68F0A ON utente (ente_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DE45B3E045924CB2 ON utente (codice_fiscale)');
        $this->addSql('COMMENT ON COLUMN utente.roles IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE servizio (id UUID NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, area VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, testo_istruzioni TEXT DEFAULT NULL, status INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D8716AD5989D9B62 ON servizio (slug)');
        $this->addSql('CREATE TABLE servizio_enti (servizio_id UUID NOT NULL, ente_id UUID NOT NULL, PRIMARY KEY(servizio_id, ente_id))');
        $this->addSql('CREATE INDEX IDX_44B1812C5513F0B4 ON servizio_enti (servizio_id)');
        $this->addSql('CREATE INDEX IDX_44B1812CEFB68F0A ON servizio_enti (ente_id)');
        $this->addSql('CREATE TABLE allegato (id UUID NOT NULL, owner_id UUID DEFAULT NULL, filename VARCHAR(255) NOT NULL, original_filename VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, numero_protocollo VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_622BC0577E3C61F9 ON allegato (owner_id)');
        $this->addSql('CREATE TABLE termini_utilizzo (id UUID NOT NULL, name VARCHAR(100) NOT NULL, text TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE componente_nucleo_familiare (id UUID NOT NULL, pratica_id UUID DEFAULT NULL, nome VARCHAR(255) DEFAULT NULL, cognome VARCHAR(255) DEFAULT NULL, rapporto_parentela VARCHAR(255) DEFAULT NULL, codice_fiscale VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_67032D0D24038DEB ON componente_nucleo_familiare (pratica_id)');
        $this->addSql('CREATE TABLE ente (id UUID NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_197E1722989D9B62 ON ente (slug)');
        $this->addSql('CREATE TABLE ente_asili (ente_id UUID NOT NULL, asilo_id UUID NOT NULL, PRIMARY KEY(ente_id, asilo_id))');
        $this->addSql('CREATE INDEX IDX_2B51E709EFB68F0A ON ente_asili (ente_id)');
        $this->addSql('CREATE INDEX IDX_2B51E7092D9F8A53 ON ente_asili (asilo_id)');
        $this->addSql('CREATE TABLE asilo_nido (id UUID NOT NULL, name VARCHAR(100) NOT NULL, scheda_informativa TEXT DEFAULT NULL, orari TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE pratica ADD CONSTRAINT FK_448253ACA76ED395 FOREIGN KEY (user_id) REFERENCES utente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pratica ADD CONSTRAINT FK_448253AC5513F0B4 FOREIGN KEY (servizio_id) REFERENCES servizio (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pratica ADD CONSTRAINT FK_448253ACEFB68F0A FOREIGN KEY (ente_id) REFERENCES ente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pratica ADD CONSTRAINT FK_448253ACDD8402AC FOREIGN KEY (operatore_id) REFERENCES utente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pratica ADD CONSTRAINT FK_448253AC2D9F8A53 FOREIGN KEY (asilo_id) REFERENCES asilo_nido (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pratica_allegato ADD CONSTRAINT FK_1E92B34B24038DEB FOREIGN KEY (pratica_id) REFERENCES pratica (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pratica_allegato ADD CONSTRAINT FK_1E92B34B68F4D369 FOREIGN KEY (allegato_id) REFERENCES allegato (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE utente ADD CONSTRAINT FK_DE45B3E0EFB68F0A FOREIGN KEY (ente_id) REFERENCES ente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE servizio_enti ADD CONSTRAINT FK_44B1812C5513F0B4 FOREIGN KEY (servizio_id) REFERENCES servizio (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE servizio_enti ADD CONSTRAINT FK_44B1812CEFB68F0A FOREIGN KEY (ente_id) REFERENCES ente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE allegato ADD CONSTRAINT FK_622BC0577E3C61F9 FOREIGN KEY (owner_id) REFERENCES utente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE componente_nucleo_familiare ADD CONSTRAINT FK_67032D0D24038DEB FOREIGN KEY (pratica_id) REFERENCES pratica (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ente_asili ADD CONSTRAINT FK_2B51E709EFB68F0A FOREIGN KEY (ente_id) REFERENCES ente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ente_asili ADD CONSTRAINT FK_2B51E7092D9F8A53 FOREIGN KEY (asilo_id) REFERENCES asilo_nido (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE pratica_allegato DROP CONSTRAINT FK_1E92B34B24038DEB');
        $this->addSql('ALTER TABLE componente_nucleo_familiare DROP CONSTRAINT FK_67032D0D24038DEB');
        $this->addSql('ALTER TABLE pratica DROP CONSTRAINT FK_448253ACA76ED395');
        $this->addSql('ALTER TABLE pratica DROP CONSTRAINT FK_448253ACDD8402AC');
        $this->addSql('ALTER TABLE allegato DROP CONSTRAINT FK_622BC0577E3C61F9');
        $this->addSql('ALTER TABLE pratica DROP CONSTRAINT FK_448253AC5513F0B4');
        $this->addSql('ALTER TABLE servizio_enti DROP CONSTRAINT FK_44B1812C5513F0B4');
        $this->addSql('ALTER TABLE pratica_allegato DROP CONSTRAINT FK_1E92B34B68F4D369');
        $this->addSql('ALTER TABLE pratica DROP CONSTRAINT FK_448253ACEFB68F0A');
        $this->addSql('ALTER TABLE utente DROP CONSTRAINT FK_DE45B3E0EFB68F0A');
        $this->addSql('ALTER TABLE servizio_enti DROP CONSTRAINT FK_44B1812CEFB68F0A');
        $this->addSql('ALTER TABLE ente_asili DROP CONSTRAINT FK_2B51E709EFB68F0A');
        $this->addSql('ALTER TABLE pratica DROP CONSTRAINT FK_448253AC2D9F8A53');
        $this->addSql('ALTER TABLE ente_asili DROP CONSTRAINT FK_2B51E7092D9F8A53');
        $this->addSql('DROP TABLE pratica');
        $this->addSql('DROP TABLE pratica_allegato');
        $this->addSql('DROP TABLE utente');
        $this->addSql('DROP TABLE servizio');
        $this->addSql('DROP TABLE servizio_enti');
        $this->addSql('DROP TABLE allegato');
        $this->addSql('DROP TABLE termini_utilizzo');
        $this->addSql('DROP TABLE componente_nucleo_familiare');
        $this->addSql('DROP TABLE ente');
        $this->addSql('DROP TABLE ente_asili');
        $this->addSql('DROP TABLE asilo_nido');
    }
}
