<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160919140224 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) :void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE pratica_modulo_compilato (pratica_id UUID NOT NULL, modulo_compilato_id UUID NOT NULL, PRIMARY KEY(pratica_id, modulo_compilato_id))');
        $this->addSql('CREATE INDEX IDX_1E4BE17A24038DEB ON pratica_modulo_compilato (pratica_id)');
        $this->addSql('CREATE INDEX IDX_1E4BE17A3FE6E893 ON pratica_modulo_compilato (modulo_compilato_id)');
        $this->addSql('ALTER TABLE pratica_modulo_compilato ADD CONSTRAINT FK_1E4BE17A24038DEB FOREIGN KEY (pratica_id) REFERENCES pratica (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pratica_modulo_compilato ADD CONSTRAINT FK_1E4BE17A3FE6E893 FOREIGN KEY (modulo_compilato_id) REFERENCES allegato (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pratica ADD submission_time INT DEFAULT NULL');
        $this->addSql('ALTER TABLE allegato ADD type VARCHAR(255) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) :void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE pratica_modulo_compilato');
        $this->addSql('ALTER TABLE allegato DROP type');
        $this->addSql('ALTER TABLE pratica DROP submission_time');
    }
}
