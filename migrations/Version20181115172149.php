<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181115172149 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica ADD nome_associazione VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD natura_giuridica VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD sito VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD pagina_social VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD e_mail VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD numero_iscritti VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD modalita_di_adesione VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD attivita VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD obiettivi VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD sede_legale VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD sede_operativa VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD indirizzo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD contatti VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('COMMENT ON COLUMN craue_form_flow_storage.value IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE pratica DROP nome_associazione');
        $this->addSql('ALTER TABLE pratica DROP natura_giuridica');
        $this->addSql('ALTER TABLE pratica DROP sito');
        $this->addSql('ALTER TABLE pratica DROP pagina_social');
        $this->addSql('ALTER TABLE pratica DROP e_mail');
        $this->addSql('ALTER TABLE pratica DROP numero_iscritti');
        $this->addSql('ALTER TABLE pratica DROP modalita_di_adesione');
        $this->addSql('ALTER TABLE pratica DROP attivita');
        $this->addSql('ALTER TABLE pratica DROP obiettivi');
        $this->addSql('ALTER TABLE pratica DROP sede_legale');
        $this->addSql('ALTER TABLE pratica DROP sede_operativa');
        $this->addSql('ALTER TABLE pratica DROP indirizzo');
        $this->addSql('ALTER TABLE pratica DROP contatti');
    }
}
