<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230302160057 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pratica ADD user_group_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD CONSTRAINT FK_448253AC1ED93D47 FOREIGN KEY (user_group_id) REFERENCES user_group (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_448253AC1ED93D47 ON pratica (user_group_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE pratica DROP CONSTRAINT FK_448253AC1ED93D47');
        $this->addSql('DROP INDEX IDX_448253AC1ED93D47');
        $this->addSql('ALTER TABLE pratica DROP user_group_id');
    }
}
