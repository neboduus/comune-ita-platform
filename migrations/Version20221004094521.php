<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221004094521 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE servizio ADD source JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT LOCALTIMESTAMP');
        $this->addSql('ALTER TABLE servizio ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT LOCALTIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE servizio DROP source');
        $this->addSql('ALTER TABLE servizio DROP created_at');
        $this->addSql('ALTER TABLE servizio DROP updated_at');
    }
}
