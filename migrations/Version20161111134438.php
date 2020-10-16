<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161111134438 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE pratica_allegato_operatore (pratica_id UUID NOT NULL, allegato_operatore_id UUID NOT NULL, PRIMARY KEY(pratica_id, allegato_operatore_id))');
        $this->addSql('CREATE INDEX IDX_75D99A4624038DEB ON pratica_allegato_operatore (pratica_id)');
        $this->addSql('CREATE INDEX IDX_75D99A46D32D55E2 ON pratica_allegato_operatore (allegato_operatore_id)');
        $this->addSql('ALTER TABLE pratica_allegato_operatore ADD CONSTRAINT FK_75D99A4624038DEB FOREIGN KEY (pratica_id) REFERENCES pratica (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pratica_allegato_operatore ADD CONSTRAINT FK_75D99A46D32D55E2 FOREIGN KEY (allegato_operatore_id) REFERENCES allegato (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pratica ADD allegato_operatore_richiesto BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ALTER last_compiled_step DROP DEFAULT');
        $this->addSql('ALTER TABLE allegato ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE servizio ADD pratica_flow_operatore_service_name VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE pratica_allegato_operatore');
        $this->addSql('ALTER TABLE servizio DROP pratica_flow_operatore_service_name');
        $this->addSql('ALTER TABLE allegato ALTER created_at SET DEFAULT \'now\'');
        $this->addSql('ALTER TABLE pratica DROP allegato_operatore_richiesto');
        $this->addSql('ALTER TABLE pratica ALTER last_compiled_step SET DEFAULT 0');
    }
}
