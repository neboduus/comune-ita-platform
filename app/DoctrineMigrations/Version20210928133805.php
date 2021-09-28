<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210928133805 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE categoria DROP tree_id');
        $this->addSql('ALTER TABLE categoria DROP tree_parent_id');
        $this->addSql('ALTER TABLE categoria ADD CONSTRAINT FK_4E10122D727ACA70 FOREIGN KEY (parent_id) REFERENCES categoria (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_4E10122D727ACA70 ON categoria (parent_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE categoria DROP CONSTRAINT FK_4E10122D727ACA70');
        $this->addSql('DROP INDEX IDX_4E10122D727ACA70');
        $this->addSql('ALTER TABLE categoria ADD tree_id INT NOT NULL');
        $this->addSql('ALTER TABLE categoria ADD tree_parent_id INT DEFAULT NULL');
    }
}
