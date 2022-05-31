<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220528095406 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica DROP CONSTRAINT fk_448253acad5dc05d');
        $this->addSql('DROP INDEX idx_448253acad5dc05d');
        $this->addSql('ALTER TABLE pratica ALTER payment_type TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE pratica ALTER payment_type DROP DEFAULT');
        $this->addSql('UPDATE pratica SET payment_type = (SELECT identifier FROM payment_gateway WHERE payment_gateway.id::text LIKE pratica.payment_type)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica ALTER payment_type TYPE UUID');
        $this->addSql('ALTER TABLE pratica ALTER payment_type DROP DEFAULT');
        $this->addSql('ALTER TABLE pratica ADD CONSTRAINT fk_448253acad5dc05d FOREIGN KEY (payment_type) REFERENCES payment_gateway (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_448253acad5dc05d ON pratica (payment_type)');
    }
}
