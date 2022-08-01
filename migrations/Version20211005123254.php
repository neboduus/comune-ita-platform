<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211005123254 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE recipient (id UUID NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6804FB49989D9B62 ON recipient (slug)');
        $this->addSql('CREATE TABLE servizio_recipient (servizio_id UUID NOT NULL, recipient_id UUID NOT NULL, PRIMARY KEY(servizio_id, recipient_id))');
        $this->addSql('CREATE INDEX IDX_AD3BC94F5513F0B4 ON servizio_recipient (servizio_id)');
        $this->addSql('CREATE INDEX IDX_AD3BC94FE92F8F78 ON servizio_recipient (recipient_id)');
        $this->addSql('ALTER TABLE servizio_recipient ADD CONSTRAINT FK_AD3BC94F5513F0B4 FOREIGN KEY (servizio_id) REFERENCES servizio (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE servizio_recipient ADD CONSTRAINT FK_AD3BC94FE92F8F78 FOREIGN KEY (recipient_id) REFERENCES recipient (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE servizio_recipient DROP CONSTRAINT FK_AD3BC94FE92F8F78');
        $this->addSql('DROP TABLE recipient');
        $this->addSql('DROP TABLE servizio_recipient');
    }
}
