<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160913110710 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE pratica_allegato (pratica_id UUID NOT NULL, allegato_id UUID NOT NULL, PRIMARY KEY(pratica_id, allegato_id))');
        $this->addSql('CREATE INDEX IDX_1E92B34B24038DEB ON pratica_allegato (pratica_id)');
        $this->addSql('CREATE INDEX IDX_1E92B34B68F4D369 ON pratica_allegato (allegato_id)');
        $this->addSql('ALTER TABLE pratica_allegato ADD CONSTRAINT FK_1E92B34B24038DEB FOREIGN KEY (pratica_id) REFERENCES pratica (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pratica_allegato ADD CONSTRAINT FK_1E92B34B68F4D369 FOREIGN KEY (allegato_id) REFERENCES allegato (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE allegato DROP CONSTRAINT fk_622bc05724038deb');
        $this->addSql('DROP INDEX idx_622bc05724038deb');
        $this->addSql('ALTER TABLE allegato RENAME COLUMN pratica_id TO owner_id');
        $this->addSql('ALTER TABLE allegato ADD CONSTRAINT FK_622BC0577E3C61F9 FOREIGN KEY (owner_id) REFERENCES utente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_622BC0577E3C61F9 ON allegato (owner_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE pratica_allegato');
        $this->addSql('ALTER TABLE allegato DROP CONSTRAINT FK_622BC0577E3C61F9');
        $this->addSql('DROP INDEX IDX_622BC0577E3C61F9');
        $this->addSql('ALTER TABLE allegato RENAME COLUMN owner_id TO pratica_id');
        $this->addSql('ALTER TABLE allegato ADD CONSTRAINT fk_622bc05724038deb FOREIGN KEY (pratica_id) REFERENCES pratica (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_622bc05724038deb ON allegato (pratica_id)');
    }
}
