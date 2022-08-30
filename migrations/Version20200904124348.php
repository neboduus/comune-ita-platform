<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200904124348 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE message (id UUID NOT NULL, user_id UUID NOT NULL, pratica_id UUID NOT NULL, attachment_id UUID DEFAULT NULL, message TEXT NOT NULL, visibility VARCHAR(255) NOT NULL, created_at INT NOT NULL, sent_at INT DEFAULT NULL, read_at INT DEFAULT NULL, clicked_at INT DEFAULT NULL, protocol_required BOOLEAN DEFAULT \'true\', protocolled_at INT DEFAULT NULL, protocol_number VARCHAR(255) DEFAULT NULL, call_to_actions JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B6BD307FA76ED395 ON message (user_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307F24038DEB ON message (pratica_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B6BD307F464E68B ON message (attachment_id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FA76ED395 FOREIGN KEY (user_id) REFERENCES utente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F24038DEB FOREIGN KEY (pratica_id) REFERENCES pratica (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F464E68B FOREIGN KEY (attachment_id) REFERENCES allegato (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE message');
    }
}
