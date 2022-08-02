<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211031185954 extends AbstractMigration
{
    public function up(Schema $schema): void : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE allegato ADD file_size NUMERIC(14, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE allegato ADD file_hash VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE allegato ADD expire_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE allegato DROP file_size');
        $this->addSql('ALTER TABLE allegato DROP file_hash');
        $this->addSql('ALTER TABLE allegato DROP expire_date');
    }
}
