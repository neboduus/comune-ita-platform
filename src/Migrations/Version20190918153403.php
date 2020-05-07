<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190918153403 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE audit_log (id UUID NOT NULL, type_id VARCHAR(200) NOT NULL, type VARCHAR(200) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, event_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, "utente" VARCHAR(255) NOT NULL, impersonating_user VARCHAR(255) DEFAULT NULL, ip VARCHAR(20) DEFAULT NULL, level VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema) :void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE audit_log');
    }
}
