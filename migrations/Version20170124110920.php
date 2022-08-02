<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170124110920 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica ADD cf_org_richiedente VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD piva_org_richiedente VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD email_org_richiedente VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD tel_org_richiedente VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD tipologia_attivita VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD uso_contributo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD descrizione_contributo TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD anno_attivita VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica DROP cf_org_richiedente');
        $this->addSql('ALTER TABLE pratica DROP piva_org_richiedente');
        $this->addSql('ALTER TABLE pratica DROP email_org_richiedente');
        $this->addSql('ALTER TABLE pratica DROP tel_org_richiedente');
        $this->addSql('ALTER TABLE pratica DROP tipologia_attivita');
        $this->addSql('ALTER TABLE pratica DROP uso_contributo');
        $this->addSql('ALTER TABLE pratica DROP descrizione_contributo');
        $this->addSql('ALTER TABLE pratica DROP anno_attivita');
    }
}
