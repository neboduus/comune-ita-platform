<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161110105957 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_immobile_provincia VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_immobile_comune VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_immobile_indirizzo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_immobile_numero_civico VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_immobile_cap VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_immobile_scala VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_immobile_piano VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_immobile_interno VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_immobile_catasto_categoria VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_immobile_catasto_codice_comune VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_immobile_catasto_foglio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_immobile_catasto_sezione VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_immobile_catasto_mappale VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_immobile_catasto_subalterno VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_immobile_qualifica VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_tipo_intervento VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_tipo_allaccio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_tipo_uso VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_diametro_rete_interna VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_use_alternate_contact BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_alternate_contact_via VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_alternate_contact_civico VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_alternate_contact_cap VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD allacciamento_acquedotto_alternate_contact_comune VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_immobile_provincia');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_immobile_comune');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_immobile_indirizzo');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_immobile_numero_civico');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_immobile_cap');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_immobile_scala');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_immobile_piano');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_immobile_interno');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_immobile_catasto_categoria');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_immobile_catasto_codice_comune');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_immobile_catasto_foglio');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_immobile_catasto_sezione');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_immobile_catasto_mappale');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_immobile_catasto_subalterno');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_immobile_qualifica');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_tipo_intervento');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_tipo_allaccio');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_tipo_uso');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_diametro_rete_interna');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_use_alternate_contact');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_alternate_contact_via');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_alternate_contact_civico');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_alternate_contact_cap');
        $this->addSql('ALTER TABLE pratica DROP allacciamento_acquedotto_alternate_contact_comune');
    }
}
