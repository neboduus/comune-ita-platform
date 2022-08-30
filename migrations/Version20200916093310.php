<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200916093310 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE message_allegato_messaggio (message_id UUID NOT NULL, allegato_messaggio_id UUID NOT NULL, PRIMARY KEY(message_id, allegato_messaggio_id))');
        $this->addSql('CREATE INDEX IDX_347103A6537A1329 ON message_allegato_messaggio (message_id)');
        $this->addSql('CREATE INDEX IDX_347103A61F9404D1 ON message_allegato_messaggio (allegato_messaggio_id)');
        $this->addSql('ALTER TABLE message_allegato_messaggio ADD CONSTRAINT FK_347103A6537A1329 FOREIGN KEY (message_id) REFERENCES message (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_allegato_messaggio ADD CONSTRAINT FK_347103A61F9404D1 FOREIGN KEY (allegato_messaggio_id) REFERENCES allegato (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT fk_b6bd307f464e68b');
        $this->addSql('DROP INDEX uniq_b6bd307f464e68b');
        $this->addSql('ALTER TABLE message RENAME COLUMN attachment_id TO generated_document_id');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F38668EA2 FOREIGN KEY (generated_document_id) REFERENCES allegato (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B6BD307F38668EA2 ON message (generated_document_id)');
    }

    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE message_allegato_messaggio');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307F38668EA2');
        $this->addSql('DROP INDEX UNIQ_B6BD307F38668EA2');
        $this->addSql('ALTER TABLE message RENAME COLUMN generated_document_id TO attachment_id');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT fk_b6bd307f464e68b FOREIGN KEY (attachment_id) REFERENCES allegato (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_b6bd307f464e68b ON message (attachment_id)');
    }
}
