<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181127182434 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica ALTER modalita_di_adesione TYPE TEXT');
        $this->addSql('ALTER TABLE pratica ALTER modalita_di_adesione DROP DEFAULT');
        $this->addSql('ALTER TABLE pratica ALTER attivita TYPE TEXT');
        $this->addSql('ALTER TABLE pratica ALTER attivita DROP DEFAULT');
        $this->addSql('ALTER TABLE pratica ALTER obiettivi TYPE TEXT');
        $this->addSql('ALTER TABLE pratica ALTER obiettivi DROP DEFAULT');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE craue_form_flow_storage (key VARCHAR(255) NOT NULL, value TEXT NOT NULL, PRIMARY KEY(key))');
        $this->addSql('COMMENT ON COLUMN craue_form_flow_storage.value IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE pratica ALTER modalita_di_adesione TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE pratica ALTER modalita_di_adesione DROP DEFAULT');
        $this->addSql('ALTER TABLE pratica ALTER attivita TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE pratica ALTER attivita DROP DEFAULT');
        $this->addSql('ALTER TABLE pratica ALTER obiettivi TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE pratica ALTER obiettivi DROP DEFAULT');
    }
}
