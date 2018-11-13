<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170123172625 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE servizio_erogatori (servizio_id UUID NOT NULL, erogatore_id UUID NOT NULL, PRIMARY KEY(servizio_id, erogatore_id))');
        $this->addSql('CREATE INDEX IDX_995B50E35513F0B4 ON servizio_erogatori (servizio_id)');
        $this->addSql('CREATE INDEX IDX_995B50E39EAAC4FB ON servizio_erogatori (erogatore_id)');
        $this->addSql('CREATE TABLE erogatore (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE erogatore_ente (erogatore_id UUID NOT NULL, ente_id UUID NOT NULL, PRIMARY KEY(erogatore_id, ente_id))');
        $this->addSql('CREATE INDEX IDX_1B52A1C19EAAC4FB ON erogatore_ente (erogatore_id)');
        $this->addSql('CREATE INDEX IDX_1B52A1C1EFB68F0A ON erogatore_ente (ente_id)');
        $this->addSql('ALTER TABLE servizio_erogatori ADD CONSTRAINT FK_995B50E35513F0B4 FOREIGN KEY (servizio_id) REFERENCES servizio (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE servizio_erogatori ADD CONSTRAINT FK_995B50E39EAAC4FB FOREIGN KEY (erogatore_id) REFERENCES erogatore (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE erogatore_ente ADD CONSTRAINT FK_1B52A1C19EAAC4FB FOREIGN KEY (erogatore_id) REFERENCES erogatore (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE erogatore_ente ADD CONSTRAINT FK_1B52A1C1EFB68F0A FOREIGN KEY (ente_id) REFERENCES ente (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE servizio_enti');
        $this->addSql('ALTER TABLE pratica ADD erogatore_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD CONSTRAINT FK_448253AC9EAAC4FB FOREIGN KEY (erogatore_id) REFERENCES erogatore (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_448253AC9EAAC4FB ON pratica (erogatore_id)');
        $this->addSql('ALTER TABLE ente ALTER protocollo_parameters DROP DEFAULT');
        $this->addSql('ALTER TABLE ente ALTER protocollo_parameters SET NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica DROP CONSTRAINT FK_448253AC9EAAC4FB');
        $this->addSql('ALTER TABLE servizio_erogatori DROP CONSTRAINT FK_995B50E39EAAC4FB');
        $this->addSql('ALTER TABLE erogatore_ente DROP CONSTRAINT FK_1B52A1C19EAAC4FB');
        $this->addSql('CREATE TABLE servizio_enti (servizio_id UUID NOT NULL, ente_id UUID NOT NULL, PRIMARY KEY(servizio_id, ente_id))');
        $this->addSql('CREATE INDEX idx_44b1812c5513f0b4 ON servizio_enti (servizio_id)');
        $this->addSql('CREATE INDEX idx_44b1812cefb68f0a ON servizio_enti (ente_id)');
        $this->addSql('ALTER TABLE servizio_enti ADD CONSTRAINT fk_44b1812c5513f0b4 FOREIGN KEY (servizio_id) REFERENCES servizio (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE servizio_enti ADD CONSTRAINT fk_44b1812cefb68f0a FOREIGN KEY (ente_id) REFERENCES ente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE servizio_erogatori');
        $this->addSql('DROP TABLE erogatore');
        $this->addSql('DROP TABLE erogatore_ente');
        $this->addSql('ALTER TABLE ente ALTER protocollo_parameters SET DEFAULT \'a:0:{}\'');
        $this->addSql('ALTER TABLE ente ALTER protocollo_parameters DROP NOT NULL');
        $this->addSql('DROP INDEX IDX_448253AC9EAAC4FB');
        $this->addSql('ALTER TABLE pratica DROP erogatore_id');
    }
}
