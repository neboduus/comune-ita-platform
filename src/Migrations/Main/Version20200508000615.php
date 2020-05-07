<?php

declare(strict_types=1);

namespace Application\Main\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200508000615 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE tenant (id INT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(100) NOT NULL, db_host VARCHAR(255) NOT NULL, db_port INT NOT NULL, db_name VARCHAR(255) NOT NULL, db_user VARCHAR(255) NOT NULL, db_password VARCHAR(255) NOT NULL, host VARCHAR(255) DEFAULT NULL, path_info_prefix VARCHAR(255) DEFAULT NULL, protocollo_handler VARCHAR(255) NOT NULL, codice_meccanografico VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_4e59c462989d9b62 ON tenant (slug)');
        $this->addSql('CREATE UNIQUE INDEX uniq_4e59c462558350ba ON tenant (codice_meccanografico)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE tenant');
    }
}
