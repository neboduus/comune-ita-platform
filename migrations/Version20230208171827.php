<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230208171827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_group ADD calendar_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9DDD713CA2 FOREIGN KEY (calendar_id) REFERENCES calendar (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_group DROP CONSTRAINT FK_8F02BF9DDD713CA2');
        $this->addSql('ALTER TABLE user_group DROP calendar_id');
    }
}
