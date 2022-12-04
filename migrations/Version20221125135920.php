<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221125135920 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact_point (id UUID NOT NULL, name VARCHAR(255), email VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, pec VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE user_group ADD core_contact_point_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE user_group ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE user_group ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('UPDATE user_group SET created_at = NOW(), updated_at = NOW()');
        $this->addSql('ALTER TABLE user_group ALTER created_at SET NOT NULL');
        $this->addSql('ALTER TABLE user_group ALTER created_at SET NOT NULL');
        $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9D3FC07731 FOREIGN KEY (core_contact_point_id) REFERENCES contact_point (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_8F02BF9D3FC07731 ON user_group (core_contact_point_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_group DROP CONSTRAINT FK_8F02BF9D3FC07731');
        $this->addSql('DROP TABLE contact_point');
        $this->addSql('DROP INDEX IDX_8F02BF9D3FC07731');
        $this->addSql('ALTER TABLE user_group DROP core_contact_point_id');
        $this->addSql('ALTER TABLE user_group DROP created_at');
        $this->addSql('ALTER TABLE user_group DROP updated_at');
    }
}
