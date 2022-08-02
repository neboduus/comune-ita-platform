<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190131124142 extends AbstractMigration
{
    public function up(Schema $schema): void : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE servizio ADD additional_data JSON DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN servizio.additional_data IS \'(DC2Type:json_array)\'');
    }

    public function down(Schema $schema): void : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE servizio DROP additional_data');
    }
}
