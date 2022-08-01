<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220131095158 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE subscriber ALTER address DROP NOT NULL');
        $this->addSql('ALTER TABLE subscriber ALTER house_number DROP NOT NULL');
        $this->addSql('ALTER TABLE subscriber ALTER municipality DROP NOT NULL');
        $this->addSql('ALTER TABLE subscriber ALTER postal_code DROP NOT NULL');
    }

    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE subscriber ALTER address SET NOT NULL');
        $this->addSql('ALTER TABLE subscriber ALTER house_number SET NOT NULL');
        $this->addSql('ALTER TABLE subscriber ALTER municipality SET NOT NULL');
        $this->addSql('ALTER TABLE subscriber ALTER postal_code SET NOT NULL');
    }
}
