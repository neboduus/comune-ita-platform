<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200610214449 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica ADD parent_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE pratica ADD CONSTRAINT FK_448253AC727ACA70 FOREIGN KEY (parent_id) REFERENCES pratica (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_448253AC727ACA70 ON pratica (parent_id)');
        // update existent related applications
        $this->addSql("UPDATE pratica t1 SET parent_id=sq.parent FROM ( SELECT (t2.dematerialized_forms -> 'data' ->> 'related_applications')::uuid as parent, t2.id as child FROM   pratica t2 ) AS sq WHERE  t1.id=sq.child;");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE pratica DROP CONSTRAINT FK_448253AC727ACA70');
        $this->addSql('DROP INDEX IDX_448253AC727ACA70');
        $this->addSql('ALTER TABLE pratica DROP parent_id');
    }
}
