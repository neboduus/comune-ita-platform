<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210330091417 extends AbstractMigration
{
  public function up(Schema $schema) : void
  {
    // this up() migration is auto-generated, please modify it to your needs
    $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

    $this->addSql('CREATE SEQUENCE cron_job_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
    $this->addSql('CREATE SEQUENCE cron_report_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
    $this->addSql('CREATE TABLE cron_job (id INT NOT NULL, name VARCHAR(191) NOT NULL, command VARCHAR(1024) NOT NULL, schedule VARCHAR(191) NOT NULL, description VARCHAR(191) NOT NULL, enabled BOOLEAN NOT NULL, PRIMARY KEY(id))');
    $this->addSql('CREATE UNIQUE INDEX un_name ON cron_job (name)');
    $this->addSql('CREATE TABLE cron_report (id INT NOT NULL, job_id INT DEFAULT NULL, run_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, run_time DOUBLE PRECISION NOT NULL, exit_code INT NOT NULL, output TEXT NOT NULL, PRIMARY KEY(id))');
    $this->addSql('CREATE INDEX IDX_B6C6A7F5BE04EA9 ON cron_report (job_id)');
    $this->addSql('ALTER TABLE cron_report ADD CONSTRAINT FK_B6C6A7F5BE04EA9 FOREIGN KEY (job_id) REFERENCES cron_job (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
  }

  public function down(Schema $schema) : void
  {
    // this down() migration is auto-generated, please modify it to your needs
    $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

    $this->addSql('ALTER TABLE cron_report DROP CONSTRAINT FK_B6C6A7F5BE04EA9');
    $this->addSql('DROP SEQUENCE cron_job_id_seq CASCADE');
    $this->addSql('DROP SEQUENCE cron_report_id_seq CASCADE');
    $this->addSql('DROP TABLE cron_job');
    $this->addSql('DROP TABLE cron_report');
  }
}