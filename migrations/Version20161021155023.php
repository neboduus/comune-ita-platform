<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161021155023 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica ADD iban VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD intestatario_conto VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD tipo_pannolini INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD nome_punto_vendita VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD data_acquisto TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD totale_spesa INT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica DROP iban');
        $this->addSql('ALTER TABLE pratica DROP intestatario_conto');
        $this->addSql('ALTER TABLE pratica DROP tipo_pannolini');
        $this->addSql('ALTER TABLE pratica DROP nome_punto_vendita');
        $this->addSql('ALTER TABLE pratica DROP data_acquisto');
        $this->addSql('ALTER TABLE pratica DROP totale_spesa');
    }
}
