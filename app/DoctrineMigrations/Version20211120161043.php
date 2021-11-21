<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211120161043 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE service_group_recipient (service_group_id UUID NOT NULL, recipient_id UUID NOT NULL, PRIMARY KEY(service_group_id, recipient_id))');
        $this->addSql('CREATE INDEX IDX_8AB6D74C722827A ON service_group_recipient (service_group_id)');
        $this->addSql('CREATE INDEX IDX_8AB6D74CE92F8F78 ON service_group_recipient (recipient_id)');
        $this->addSql('ALTER TABLE service_group_recipient ADD CONSTRAINT FK_8AB6D74C722827A FOREIGN KEY (service_group_id) REFERENCES service_group (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_group_recipient ADD CONSTRAINT FK_8AB6D74CE92F8F78 FOREIGN KEY (recipient_id) REFERENCES recipient (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_group ADD topics_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE service_group ADD CONSTRAINT FK_C4B2A922BF06A414 FOREIGN KEY (topics_id) REFERENCES categoria (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C4B2A922BF06A414 ON service_group (topics_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE service_group_recipient');
        $this->addSql('ALTER TABLE service_group DROP CONSTRAINT FK_C4B2A922BF06A414');
        $this->addSql('DROP INDEX IDX_C4B2A922BF06A414');
        $this->addSql('ALTER TABLE service_group DROP topics_id');
    }
}
