<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170322170646 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE allegato ADD id_documento_protocollo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE allegato ADD numeri_protocollo TEXT DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN allegato.numeri_protocollo IS \'(DC2Type:array)\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('COMMENT ON COLUMN craue_form_flow_storage.value IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE allegato DROP id_documento_protocollo');
        $this->addSql('ALTER TABLE allegato DROP numeri_protocollo');
    }
}
