<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210611085801 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ente ADD linkable_application_meetings BOOLEAN DEFAULT \'true\' NOT NULL');
        $this->addSql('CREATE TABLE application_meetings (application_id UUID NOT NULL, meeting_id UUID NOT NULL, PRIMARY KEY(application_id, meeting_id))');
        $this->addSql('CREATE INDEX IDX_FBC58DF13E030ACD ON application_meetings (application_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FBC58DF167433D9C ON application_meetings (meeting_id)');
        $this->addSql('ALTER TABLE application_meetings ADD CONSTRAINT FK_FBC58DF13E030ACD FOREIGN KEY (application_id) REFERENCES pratica (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE application_meetings ADD CONSTRAINT FK_FBC58DF167433D9C FOREIGN KEY (meeting_id) REFERENCES meeting (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE ente DROP linkable_application_meetings');
        $this->addSql('DROP TABLE application_meetings');
    }
}
