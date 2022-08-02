<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210309092242 extends AbstractMigration
{
    public function up(Schema $schema): void : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE calendar ADD drafts_duration INT DEFAULT NULL');
        $this->addSql('ALTER TABLE calendar ADD drafts_duration_increment INT DEFAULT NULL');
        $this->addSql('ALTER TABLE meeting ADD draft_expiration TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE calendar DROP drafts_duration');
        $this->addSql('ALTER TABLE calendar DROP drafts_duration_increment');
        $this->addSql('ALTER TABLE meeting DROP draft_expiration');
    }
}
