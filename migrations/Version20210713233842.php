<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210713233842 extends AbstractMigration
{
    public function up(Schema $schema): void : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql("ALTER TABLE pratica ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT '1970-01-01 00:00:00'");
        $this->addSql("ALTER TABLE pratica ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT '1970-01-01 00:00:00'");
        $this->addSql("UPDATE pratica SET created_at = creation_time::abstime::timestamp");
        $this->addSql("UPDATE pratica SET updated_at = latest_status_change_timestamp::abstime::timestamp");
    }

    public function down(Schema $schema): void : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica DROP created_at');
        $this->addSql('ALTER TABLE pratica DROP updated_at');
    }
}
