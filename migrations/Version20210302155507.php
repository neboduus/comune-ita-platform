<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210302155507 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE webhook (id UUID NOT NULL, ente_id UUID DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, endpoint VARCHAR(255) DEFAULT NULL, method VARCHAR(255) DEFAULT NULL, trigger VARCHAR(255) DEFAULT NULL, filters JSON DEFAULT NULL, headers TEXT DEFAULT NULL, active BOOLEAN DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8A741756EFB68F0A ON webhook (ente_id)');
        $this->addSql('ALTER TABLE webhook ADD CONSTRAINT FK_8A741756EFB68F0A FOREIGN KEY (ente_id) REFERENCES ente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE webhook');
    }
}
