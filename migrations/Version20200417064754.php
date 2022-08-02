<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200417064754 extends AbstractMigration
{
    public function up(Schema $schema): void : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE folder ADD slug VARCHAR(100)');
        $this->addSql('ALTER TABLE document ADD last_read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD validity_begin TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD validity_end TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD expire_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD due_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD downloads_counter INT');
        $this->addSql('ALTER TABLE document DROP lastreadat');
        $this->addSql('ALTER TABLE document DROP validitybegin');
        $this->addSql('ALTER TABLE document DROP validityend');
        $this->addSql('ALTER TABLE document DROP expireat');
        $this->addSql('ALTER TABLE document DROP duedate');
        $this->addSql('ALTER TABLE document RENAME COLUMN readersallowed TO readers_allowed');
    }

    public function down(Schema $schema): void : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE folder DROP slug');
        $this->addSql('ALTER TABLE document ADD lastreadat TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD validitybegin TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD validityend TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD expireat TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD duedate TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE document DROP downloads_counter');
        $this->addSql('ALTER TABLE document DROP last_read_at');
        $this->addSql('ALTER TABLE document DROP validity_begin');
        $this->addSql('ALTER TABLE document DROP validity_end');
        $this->addSql('ALTER TABLE document DROP expire_at');
        $this->addSql('ALTER TABLE document DROP due_date');
        $this->addSql('ALTER TABLE document RENAME COLUMN readers_allowed TO readersallowed');
    }
}
