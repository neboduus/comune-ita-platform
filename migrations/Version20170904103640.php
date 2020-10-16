<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170904103640 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE allegato ADD pratica_per_cui_serve_integrazione_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE allegato ADD payload JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE allegato ADD status INT DEFAULT NULL');
        $this->addSql('ALTER TABLE allegato ADD CONSTRAINT FK_622BC057A8102107 FOREIGN KEY (pratica_per_cui_serve_integrazione_id) REFERENCES pratica (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_622BC057A8102107 ON allegato (pratica_per_cui_serve_integrazione_id)');
        $this->addSql('ALTER TABLE pratica ADD user_compilation_notes TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ente ADD codice_amministrativo VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_197E1722B067413 ON ente (codice_amministrativo)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX UNIQ_197E1722B067413');
        $this->addSql('ALTER TABLE ente DROP codice_amministrativo');
        $this->addSql('ALTER TABLE allegato DROP CONSTRAINT FK_622BC057A8102107');
        $this->addSql('DROP INDEX IDX_622BC057A8102107');
        $this->addSql('ALTER TABLE allegato DROP pratica_per_cui_serve_integrazione_id');
        $this->addSql('ALTER TABLE allegato DROP payload');
        $this->addSql('ALTER TABLE allegato DROP status');
        $this->addSql('ALTER TABLE pratica DROP user_compilation_notes');
    }
}
