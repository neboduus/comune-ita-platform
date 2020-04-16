<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200415114850 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE servizio ADD compilation_info TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD completed_info TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD email_message TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD hash TEXT DEFAULT NULL');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE servizio DROP compilation_info');
        $this->addSql('ALTER TABLE servizio DROP completed_info');
        $this->addSql('ALTER TABLE servizio DROP email_message');
        $this->addSql('ALTER TABLE pratica DROP hash');
    }
}
