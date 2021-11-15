<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211111153052 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_meetings DROP CONSTRAINT FK_FBC58DF13E030ACD');
        $this->addSql('ALTER TABLE application_meetings DROP CONSTRAINT FK_FBC58DF167433D9C');
        $this->addSql('ALTER TABLE application_meetings ADD CONSTRAINT FK_FBC58DF13E030ACD FOREIGN KEY (application_id) REFERENCES pratica (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE application_meetings ADD CONSTRAINT FK_FBC58DF167433D9C FOREIGN KEY (meeting_id) REFERENCES meeting (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE application_meetings DROP CONSTRAINT fk_fbc58df13e030acd');
        $this->addSql('ALTER TABLE application_meetings DROP CONSTRAINT fk_fbc58df167433d9c');
        $this->addSql('ALTER TABLE application_meetings ADD CONSTRAINT fk_fbc58df13e030acd FOREIGN KEY (application_id) REFERENCES pratica (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE application_meetings ADD CONSTRAINT fk_fbc58df167433d9c FOREIGN KEY (meeting_id) REFERENCES meeting (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
