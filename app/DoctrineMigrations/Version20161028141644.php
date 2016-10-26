<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161028141644 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE allegato ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT LOCALTIMESTAMP');
        $this->addSql('ALTER TABLE allegato ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE allegato ALTER updated_at DROP DEFAULT');
        $this->addSql('ALTER TABLE pratica ADD provenienza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD comune_di_provenienza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD stato_estero_di_provenienza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD comune_estero_di_provenienza VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD altra_provenienza TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD residenza_provincia VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD residenza_comune VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD residenza_indirizzo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD residenza_numero_civico VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD residenza_scala VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD residenza_piano VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD residenza_interno VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD persone_residenti TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD tipo_occupazione VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD proprietario_catasto_sezione VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD proprietario_catasto_foglio VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD proprietario_catasto_particella VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD proprietario_catasto_subalterno VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD contratto_agenzia VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD contratto_numero VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD contratto_data DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD usufruttuario_info TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD info_accertamento TEXT DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN pratica.persone_residenti IS \'(DC2Type:array)\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE allegato DROP created_at');
        $this->addSql('ALTER TABLE allegato ALTER updated_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE allegato ALTER updated_at DROP DEFAULT');
        $this->addSql('ALTER TABLE pratica DROP provenienza');
        $this->addSql('ALTER TABLE pratica DROP comune_di_provenienza');
        $this->addSql('ALTER TABLE pratica DROP stato_estero_di_provenienza');
        $this->addSql('ALTER TABLE pratica DROP comune_estero_di_provenienza');
        $this->addSql('ALTER TABLE pratica DROP altra_provenienza');
        $this->addSql('ALTER TABLE pratica DROP residenza_provincia');
        $this->addSql('ALTER TABLE pratica DROP residenza_comune');
        $this->addSql('ALTER TABLE pratica DROP residenza_indirizzo');
        $this->addSql('ALTER TABLE pratica DROP residenza_numero_civico');
        $this->addSql('ALTER TABLE pratica DROP residenza_scala');
        $this->addSql('ALTER TABLE pratica DROP residenza_piano');
        $this->addSql('ALTER TABLE pratica DROP residenza_interno');
        $this->addSql('ALTER TABLE pratica DROP persone_residenti');
        $this->addSql('ALTER TABLE pratica DROP tipo_occupazione');
        $this->addSql('ALTER TABLE pratica DROP proprietario_catasto_sezione');
        $this->addSql('ALTER TABLE pratica DROP proprietario_catasto_foglio');
        $this->addSql('ALTER TABLE pratica DROP proprietario_catasto_particella');
        $this->addSql('ALTER TABLE pratica DROP proprietario_catasto_subalterno');
        $this->addSql('ALTER TABLE pratica DROP contratto_agenzia');
        $this->addSql('ALTER TABLE pratica DROP contratto_numero');
        $this->addSql('ALTER TABLE pratica DROP contratto_data');
        $this->addSql('ALTER TABLE pratica DROP usufruttuario_info');
        $this->addSql('ALTER TABLE pratica DROP info_accertamento');
    }
}
