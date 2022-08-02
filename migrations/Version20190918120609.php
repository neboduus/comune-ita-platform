<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190918120609 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE failure_login_attempt_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE failure_login_attempt (id INT NOT NULL, ip VARCHAR(45) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, data TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX ip ON failure_login_attempt (ip)');
    }

    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE failure_login_attempt_id_seq CASCADE');
        $this->addSql('DROP TABLE failure_login_attempt');
    }
}
